import os
from typing import Any, Dict, Optional

import requests


class LaravelBatchApiError(RuntimeError):
    def __init__(self, status_code: int, body: str):
        super().__init__(f"Laravel Batch API error {status_code}: {body[:400]}")
        self.status_code = status_code
        self.body = body or ""


class LaravelBatchApiClient:
    """
    Laravel Batch APIを呼び出すためのclient。

    Python batchからDB直接更新を外していくため、claim/complete/failのAPI呼び出しを
    このクラスに集約する。
    """

    def __init__(
        self,
        base_url: str,
        batch_key: str,
        verify_tls: bool = False,
        timeout: int = 30,
        session: Optional[requests.Session] = None,
    ):
        if not base_url:
            raise ValueError("base_url is required")
        if not batch_key:
            raise ValueError("batch_key is required")

        self.base_url = base_url.rstrip("/")
        self.batch_key = batch_key
        self.verify_tls = verify_tls
        self.timeout = timeout
        self.session = session or requests.Session()

    @classmethod
    def from_env(cls) -> "LaravelBatchApiClient":
        return cls(
            base_url=os.getenv("BATCH_BASE_URL", "https://nginx"),
            batch_key=os.getenv("BATCH_API_KEY", ""),
            verify_tls=_env_bool("BATCH_VERIFY_TLS", False),
            timeout=_env_int("BATCH_API_TIMEOUT", 30),
        )

    def claim(self, batch_size: int) -> Dict[str, Any]:
        """
        未処理リクエストをclaimし、OpenAI APIへ渡すmessagesを取得する。
        """
        return self._post(
            "/api/batch/openai-requests/claim",
            {"batch_size": batch_size},
        )

    def global_limit(self) -> Dict[str, Any]:
        """
        Batch全体の実行上限をLaravel APIへ確認する。
        """
        return self._get("/api/batch/openai-requests/limits/global")

    def request_limit(self, request_id: int) -> Dict[str, Any]:
        """
        claim済みrequest単位の実行上限をLaravel APIへ確認する。
        """
        return self._get(f"/api/batch/openai-requests/{request_id}/limits/request")

    def complete(
        self,
        request_id: int,
        result_text: str,
        prompt_tokens: int,
        completion_tokens: int,
        total_tokens: int,
    ) -> Dict[str, Any]:
        """
        OpenAI API成功結果をLaravel APIへ保存する。
        """
        return self._post(
            f"/api/batch/openai-requests/{request_id}/complete",
            {
                "result_text": result_text,
                "prompt_tokens": prompt_tokens,
                "completion_tokens": completion_tokens,
                "total_tokens": total_tokens,
            },
        )

    def fail(self, request_id: int, error_text: str) -> Dict[str, Any]:
        """
        OpenAI API失敗結果をLaravel APIへ保存する。
        """
        return self._post(
            f"/api/batch/openai-requests/{request_id}/fail",
            {"error_text": error_text},
        )

    def _post(self, path: str, payload: Dict[str, Any]) -> Dict[str, Any]:
        response = self.session.post(
            f"{self.base_url}{path}",
            headers={
                "X-BATCH-KEY": self.batch_key,
                "Accept": "application/json",
                "Content-Type": "application/json",
            },
            json=payload,
            timeout=self.timeout,
            verify=self.verify_tls,
            # POSTが301/302されるとrequestsがGETに変換するため、Batch更新系APIでは追従しない。
            allow_redirects=False,
        )

        if response.status_code < 200 or response.status_code >= 300:
            raise LaravelBatchApiError(response.status_code, response.text)

        return response.json()

    def _get(self, path: str) -> Dict[str, Any]:
        response = self.session.get(
            f"{self.base_url}{path}",
            headers={
                "X-BATCH-KEY": self.batch_key,
                "Accept": "application/json",
            },
            timeout=self.timeout,
            verify=self.verify_tls,
            allow_redirects=True,
        )

        if response.status_code < 200 or response.status_code >= 300:
            raise LaravelBatchApiError(response.status_code, response.text)

        return response.json()


def _env_bool(key: str, default: bool) -> bool:
    value = os.getenv(key)
    if value is None:
        return default
    return value.lower() in ("1", "true", "yes", "on")


def _env_int(key: str, default: int) -> int:
    value = os.getenv(key)
    return int(value) if value and value.strip().isdigit() else default
