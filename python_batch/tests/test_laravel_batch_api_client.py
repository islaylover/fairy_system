import unittest
from unittest.mock import Mock

from infrastructure.laravel_batch_api_client import (
    LaravelBatchApiClient,
    LaravelBatchApiError,
)


class LaravelBatchApiClientTest(unittest.TestCase):
    def test_claim_posts_batch_size(self):
        session = Mock()
        session.post.return_value = _response(200, {"requests": []})
        client = LaravelBatchApiClient(
            base_url="https://nginx/",
            batch_key="secret",
            verify_tls=False,
            timeout=10,
            session=session,
        )

        result = client.claim(batch_size=2)

        self.assertEqual({"requests": []}, result)
        session.post.assert_called_once_with(
            "https://nginx/api/batch/openai-requests/claim",
            headers={
                "X-BATCH-KEY": "secret",
                "Accept": "application/json",
                "Content-Type": "application/json",
            },
            json={"batch_size": 2},
            timeout=10,
            verify=False,
            allow_redirects=False,
        )

    def test_complete_posts_success_payload(self):
        session = Mock()
        session.post.return_value = _response(200, {"status": "completed", "usage_recorded": True})
        client = LaravelBatchApiClient("https://nginx", "secret", session=session)

        result = client.complete(
            request_id=123,
            result_text="answer",
            prompt_tokens=10,
            completion_tokens=20,
            total_tokens=30,
        )

        self.assertEqual({"status": "completed", "usage_recorded": True}, result)
        self.assertEqual(
            {
                "result_text": "answer",
                "prompt_tokens": 10,
                "completion_tokens": 20,
                "total_tokens": 30,
            },
            session.post.call_args.kwargs["json"],
        )

    def test_global_limit_gets_global_limit_endpoint(self):
        session = Mock()
        session.get.return_value = _response(200, {"allowed": True})
        client = LaravelBatchApiClient("https://nginx", "secret", session=session)

        result = client.global_limit()

        self.assertEqual({"allowed": True}, result)
        session.get.assert_called_once_with(
            "https://nginx/api/batch/openai-requests/limits/global",
            headers={
                "X-BATCH-KEY": "secret",
                "Accept": "application/json",
            },
            timeout=30,
            verify=False,
            allow_redirects=True,
        )

    def test_request_limit_gets_request_limit_endpoint(self):
        session = Mock()
        session.get.return_value = _response(200, {"allowed": False, "scope": "user"})
        client = LaravelBatchApiClient("https://nginx", "secret", session=session)

        result = client.request_limit(123)

        self.assertEqual({"allowed": False, "scope": "user"}, result)
        self.assertEqual(
            "https://nginx/api/batch/openai-requests/123/limits/request",
            session.get.call_args.args[0],
        )

    def test_fail_posts_error_payload(self):
        session = Mock()
        session.post.return_value = _response(200, {"status": "failed"})
        client = LaravelBatchApiClient("https://nginx", "secret", session=session)

        result = client.fail(request_id=123, error_text="error")

        self.assertEqual({"status": "failed"}, result)
        self.assertEqual(
            "https://nginx/api/batch/openai-requests/123/fail",
            session.post.call_args.args[0],
        )
        self.assertEqual({"error_text": "error"}, session.post.call_args.kwargs["json"])

    def test_raises_when_response_is_not_successful(self):
        session = Mock()
        session.post.return_value = _response(500, {"message": "server error"}, "server error")
        client = LaravelBatchApiClient("https://nginx", "secret", session=session)

        with self.assertRaises(LaravelBatchApiError) as cm:
            client.claim(batch_size=1)

        self.assertEqual(500, cm.exception.status_code)
        self.assertEqual("server error", cm.exception.body)

    def test_requires_base_url(self):
        with self.assertRaises(ValueError):
            LaravelBatchApiClient("", "secret")

    def test_requires_batch_key(self):
        with self.assertRaises(ValueError):
            LaravelBatchApiClient("https://nginx", "")


def _response(status_code, json_body, text=None):
    response = Mock()
    response.status_code = status_code
    response.text = text if text is not None else str(json_body)
    response.json.return_value = json_body
    return response


if __name__ == "__main__":
    unittest.main()
