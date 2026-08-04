import unittest
from unittest.mock import Mock, patch

from usecase import process_requests


class ProcessRequestsWithClaimApiTest(unittest.TestCase):
    def test_process_once_uses_claim_api_messages_for_openai(self):
        batch_api_client = Mock()
        batch_api_client.global_limit.return_value = {"allowed": True}
        batch_api_client.request_limit.return_value = {"allowed": True}
        messages = [
            {"role": "system", "content": "system prompt"},
            {"role": "user", "content": "hello"},
        ]
        batch_api_client.claim.return_value = {
            "requests": [
                {
                    "request_id": 123,
                    "model": "gpt-4o",
                    "messages": messages,
                }
            ]
        }

        with patch.object(
            process_requests,
            "call_openai",
            return_value=("answer", {"input_tokens": 10, "output_tokens": 20, "total_tokens": 30}),
        ) as call_openai:
            processed = process_requests.process_once(
                api_key="openai-key",
                default_model="fallback-model",
                timeout=60,
                batch_size=1,
                batch_api_client=batch_api_client,
            )

        self.assertEqual(1, processed)
        batch_api_client.global_limit.assert_called_once_with()
        batch_api_client.claim.assert_called_once_with(batch_size=1)
        batch_api_client.request_limit.assert_called_once_with(123)
        call_openai.assert_called_once_with(
            api_key="openai-key",
            model="gpt-4o",
            messages=messages,
            timeout=60,
        )
        batch_api_client.complete.assert_called_once_with(
            request_id=123,
            result_text="answer",
            prompt_tokens=10,
            completion_tokens=20,
            total_tokens=30,
        )

    def test_process_once_returns_zero_when_global_limit_is_exceeded(self):
        batch_api_client = Mock()
        batch_api_client.global_limit.return_value = {
            "allowed": False,
            "message": "USAGE_LIMIT_EXCEEDED scope=global",
        }

        with patch.object(process_requests, "call_openai") as call_openai:
            processed = process_requests.process_once(
                api_key="openai-key",
                default_model="fallback-model",
                timeout=60,
                batch_size=1,
                batch_api_client=batch_api_client,
            )

        self.assertEqual(0, processed)
        batch_api_client.claim.assert_not_called()
        call_openai.assert_not_called()

    def test_process_once_returns_zero_when_claim_api_returns_empty_requests(self):
        batch_api_client = Mock()
        batch_api_client.global_limit.return_value = {"allowed": True}
        batch_api_client.claim.return_value = {"requests": []}

        with patch.object(process_requests, "call_openai") as call_openai:
            processed = process_requests.process_once(
                api_key="openai-key",
                default_model="fallback-model",
                timeout=60,
                batch_size=1,
                batch_api_client=batch_api_client,
            )

        self.assertEqual(0, processed)
        batch_api_client.claim.assert_called_once_with(batch_size=1)
        call_openai.assert_not_called()

    def test_complete_api_error_is_not_saved_as_failed_by_python(self):
        batch_api_client = Mock()
        batch_api_client.global_limit.return_value = {"allowed": True}
        batch_api_client.request_limit.return_value = {"allowed": True}
        batch_api_client.claim.return_value = {
            "requests": [
                {
                    "request_id": 123,
                    "model": "gpt-4o",
                    "messages": [{"role": "user", "content": "hello"}],
                }
            ]
        }
        batch_api_client.complete.side_effect = RuntimeError("complete api failed")

        with patch.object(
            process_requests,
            "call_openai",
            return_value=("answer", {"input_tokens": 10, "output_tokens": 20, "total_tokens": 30}),
        ):
            with self.assertRaises(RuntimeError):
                process_requests.process_once(
                    api_key="openai-key",
                    default_model="fallback-model",
                    timeout=60,
                    batch_size=1,
                    batch_api_client=batch_api_client,
                )

        batch_api_client.fail.assert_not_called()

    def test_empty_messages_are_saved_by_fail_api(self):
        batch_api_client = Mock()
        batch_api_client.global_limit.return_value = {"allowed": True}
        batch_api_client.claim.return_value = {
            "requests": [
                {
                    "request_id": 123,
                    "model": "gpt-4o",
                    "messages": [],
                }
            ]
        }

        with patch.object(process_requests, "call_openai") as call_openai:
            processed = process_requests.process_once(
                api_key="openai-key",
                default_model="fallback-model",
                timeout=60,
                batch_size=1,
                batch_api_client=batch_api_client,
            )

        self.assertEqual(1, processed)
        batch_api_client.fail.assert_called_once_with(
            123,
            "Laravel claim API returned empty messages.",
        )
        call_openai.assert_not_called()

    def test_user_monthly_limit_error_is_saved_by_fail_api(self):
        batch_api_client = Mock()
        batch_api_client.global_limit.return_value = {"allowed": True}
        batch_api_client.request_limit.return_value = {
            "allowed": False,
            "message": "USAGE_LIMIT_EXCEEDED scope=user year_month=2026-08 used_usd=1.00000 limit_usd=1.00000",
        }
        batch_api_client.claim.return_value = {
            "requests": [
                {
                    "request_id": 123,
                    "model": "gpt-4o",
                    "messages": [{"role": "user", "content": "hello"}],
                }
            ]
        }

        with patch.object(process_requests, "call_openai") as call_openai:
            processed = process_requests.process_once(
                api_key="openai-key",
                default_model="fallback-model",
                timeout=60,
                batch_size=1,
                batch_api_client=batch_api_client,
            )

        self.assertEqual(1, processed)
        batch_api_client.fail.assert_called_once()
        self.assertIn("USAGE_LIMIT_EXCEEDED scope=user", batch_api_client.fail.call_args.args[1])
        call_openai.assert_not_called()

    def test_openai_api_error_is_saved_by_fail_api(self):
        batch_api_client = Mock()
        batch_api_client.global_limit.return_value = {"allowed": True}
        batch_api_client.request_limit.return_value = {"allowed": True}
        batch_api_client.claim.return_value = {
            "requests": [
                {
                    "request_id": 123,
                    "model": "gpt-4o",
                    "messages": [{"role": "user", "content": "hello"}],
                }
            ]
        }

        with patch.object(
            process_requests,
            "call_openai",
            side_effect=process_requests.OpenAIAPIError(500, "openai error"),
        ):
            processed = process_requests.process_once(
                api_key="openai-key",
                default_model="fallback-model",
                timeout=60,
                batch_size=1,
                batch_api_client=batch_api_client,
            )

        self.assertEqual(1, processed)
        batch_api_client.fail.assert_called_once()
        self.assertIn("OpenAIAPIError", batch_api_client.fail.call_args.args[1])

    def test_unexpected_openai_error_is_saved_by_fail_api(self):
        batch_api_client = Mock()
        batch_api_client.global_limit.return_value = {"allowed": True}
        batch_api_client.request_limit.return_value = {"allowed": True}
        batch_api_client.claim.return_value = {
            "requests": [
                {
                    "request_id": 123,
                    "model": "gpt-4o",
                    "messages": [{"role": "user", "content": "hello"}],
                }
            ]
        }

        with patch.object(
            process_requests,
            "call_openai",
            side_effect=RuntimeError("unexpected"),
        ):
            processed = process_requests.process_once(
                api_key="openai-key",
                default_model="fallback-model",
                timeout=60,
                batch_size=1,
                batch_api_client=batch_api_client,
            )

        self.assertEqual(1, processed)
        batch_api_client.fail.assert_called_once()
        self.assertIn("RuntimeError", batch_api_client.fail.call_args.args[1])

    def test_fail_api_error_is_not_saved_directly_by_python(self):
        batch_api_client = Mock()
        batch_api_client.global_limit.return_value = {"allowed": True}
        batch_api_client.request_limit.return_value = {"allowed": True}
        batch_api_client.claim.return_value = {
            "requests": [
                {
                    "request_id": 123,
                    "model": "gpt-4o",
                    "messages": [{"role": "user", "content": "hello"}],
                }
            ]
        }
        batch_api_client.fail.side_effect = RuntimeError("fail api failed")

        with patch.object(
            process_requests,
            "call_openai",
            side_effect=RuntimeError("unexpected"),
        ):
            with self.assertRaises(RuntimeError):
                process_requests.process_once(
                    api_key="openai-key",
                    default_model="fallback-model",
                    timeout=60,
                    batch_size=1,
                    batch_api_client=batch_api_client,
                )


if __name__ == "__main__":
    unittest.main()
