from typing import Any, Dict

import requests


def fetch_batch_config(base_url: str, batch_key: str, verify_tls: bool) -> Dict[str, Any]:
    """
    Get config data used for calling OpenAI API.

    Args:
        base_url (str):    Laravel APIのBASE URL  ex 'https://hogehoge.com'
        batch_key (str):   Laravel APIと疎通時に使うシークレットキー
        verify_tls: bool): Python --> Laravel API疎通時のTLS（Transport Layer Security）検証するか否か
    Returns:
        Dict[str, Any]:

    """
    url = f"{base_url.rstrip('/')}/api/batch/chatgpt/config"
    headers = {"X-BATCH-KEY": batch_key}

    r = requests.get(
        url,
        headers=headers,
        timeout=30,
        verify=verify_tls,
        allow_redirects=True,
    )
    if r.status_code != 200:
        raise RuntimeError(f"Config fetch failed: {r.status_code} {r.text[:300]}")
    return r.json()
