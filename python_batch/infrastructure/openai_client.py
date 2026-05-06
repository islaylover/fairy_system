from typing import Any, Dict, List, Tuple

import requests


class OpenAIAPIError(RuntimeError):
    def __init__(self, status_code: int, body: str):
        super().__init__(f"OpenAI error {status_code}: {body[:400]}")
        self.status_code = status_code
        self.body = body or ""


def extract_output_text(resp: Dict[str, Any]) -> str:
    texts: List[str] = []
    for item in resp.get("output", []):
        if item.get("type") == "message":
            for c in item.get("content", []):
                if c.get("type") == "output_text":
                    texts.append(c.get("text", ""))
    return "\n".join(texts).strip()


def call_openai(
    api_key: str,
    model: str,
    messages: List[Dict[str, str]], 
    timeout: int,
) -> Tuple[str, Dict[str, Any]]:
    url = "https://api.openai.com/v1/responses"
    headers = {
        "Authorization": f"Bearer {api_key}",
        "Content-Type": "application/json",
    }
    payload = {
        "model": model,
        "input": messages,
    }

    r = requests.post(url, headers=headers, json=payload, timeout=timeout)
    if r.status_code != 200:
        raise OpenAIAPIError(r.status_code, r.text)

    j = r.json()
    return extract_output_text(j), (j.get("usage") or {})
