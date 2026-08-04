import logging
import traceback
from typing import Any, Dict, List
from infrastructure.laravel_batch_api_client import LaravelBatchApiClient
from infrastructure.openai_client import call_openai, OpenAIAPIError


class FatalBatchError(RuntimeError):
    pass


def is_fatal_openai_error(e: OpenAIAPIError) -> bool:
    body_lower = (e.body or "").lower()

    if e.status_code in (401, 403):
        return True

    if e.status_code == 400:
        if "model" in body_lower and (
            "not found" in body_lower
            or "does not exist" in body_lower
            or "invalid model" in body_lower
        ):
            return True

    if "insufficient_quota" in body_lower:
        return True
    if "quota" in body_lower and ("exceeded" in body_lower or "insufficient" in body_lower):
        return True
    if "billing" in body_lower and ("hard limit" in body_lower or "limit" in body_lower):
        return True

    return False


def process_once(
    api_key: str,
    default_model: str,
    timeout: int,
    batch_size: int,
    batch_api_client: LaravelBatchApiClient,
) -> int:
    """
    【ユースケース層】
    未処理の ChatGPT リクエストを一定件数処理するバッチユースケース。

    方針：
    - daily/global上限はclaim前にLaravel APIで確認する
    - user上限はclaim後、各requestごとにLaravel APIで確認する
    - Python batchはMySQLへ直接接続せず、OpenAI実行とAPI連携だけを担当する
    """
    global_limit = batch_api_client.global_limit()
    if not bool(global_limit.get("allowed")):
        logging.warning("Batch global limit reached. %s", global_limit.get("message"))
        return 0

    claimed = batch_api_client.claim(batch_size=batch_size).get("requests") or []
    if not claimed:
        return 0

    processed = 0
    for request in claimed:
        req_id = int(request["request_id"])
        model = request.get("model") or default_model
        messages = _normalize_messages(request.get("messages") or [])
        if not messages:
            msg = safe_truncate("Laravel claim API returned empty messages.")
            batch_api_client.fail(req_id, msg)
            processed += 1
            continue

        request_limit = batch_api_client.request_limit(req_id)
        if not bool(request_limit.get("allowed")):
            msg = safe_truncate(str(request_limit.get("message") or "Request usage limit exceeded."))
            logging.warning("Request limit reached. request_id=%s %s", req_id, msg)
            batch_api_client.fail(req_id, msg)
            processed += 1
            continue

        try:
            result_text, usage = call_openai(
                api_key=api_key,
                model=model,
                messages=messages,
                timeout=timeout,
            )
        except OpenAIAPIError as e:
            err = f"{type(e).__name__}: {e}\n{traceback.format_exc()}"
            batch_api_client.fail(req_id, safe_truncate(err))

            if is_fatal_openai_error(e):
                logging.error("Fatal OpenAI error detected. Stop batch. req_id=%s status=%s", req_id, e.status_code)
                raise FatalBatchError(str(e))

            processed += 1
            continue

        except Exception as e:
            err = f"{type(e).__name__}: {e}\n{traceback.format_exc()}"
            batch_api_client.fail(req_id, safe_truncate(err))

            processed += 1
            continue

        input_tokens = usage.get("input_tokens")
        output_tokens = usage.get("output_tokens")
        total_tokens = usage.get("total_tokens")

        batch_api_client.complete(
            request_id=req_id,
            result_text=result_text,
            prompt_tokens=int(input_tokens or 0),
            completion_tokens=int(output_tokens or 0),
            total_tokens=int(total_tokens or 0),
        )

        processed += 1

    return processed


def _normalize_messages(messages: List[Dict[str, Any]]) -> List[Dict[str, str]]:
    return [
        {
            "role": str(message["role"]),
            "content": str(message["content"]),
        }
        for message in messages
    ]


def safe_truncate(value: str, limit: int = 65000) -> str:
    return value if len(value) <= limit else value[:limit]
