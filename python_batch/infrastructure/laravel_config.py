from typing import Any, Dict

import requests


def fetch_batch_config(base_url: str, batch_key: str, verify_tls: bool) -> Dict[str, Any]:
    """
    Get config data that are used for calling OpenAI API and used for caliculating cost

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


def build_prompt_map(cfg: Dict[str, Any]) -> Dict[str, str]:
    return {
        x["id"]: x["prompt"]
        for x in (cfg.get("request_types") or [])
        if x.get("id") and x.get("prompt")
    }


def build_pricing_map(cfg: Dict[str, Any]) -> Dict[str, Dict[str, float]]:
    """
    cfg['models']:
      { id, price_per_million_tokens: { input: float, output: float } }
    -> { model_id: { input: float, output: float } }
    """
    pricing_map: Dict[str, Dict[str, float]] = {}
    for m in (cfg.get("models") or []):
        model_id = m.get("id")
        p = m.get("price_per_million_tokens") or {}
        if not model_id:
            continue
        if p.get("input") is None or p.get("output") is None:
            continue
        pricing_map[model_id] = {"input": float(p["input"]), "output": float(p["output"])}
    return pricing_map


def get_daily_max_tokens(cfg: Dict[str, Any]) -> int:
    tl = cfg.get("token_limits") or {}
    return int(tl.get("daily_max_tokens") or 0)  # 0 = unlimited


def get_monthly_user_limit_usd(cfg: Dict[str, Any]) -> str:
    tl = cfg.get("token_limits") or {}
    # 0 or "0" => unlimited 扱い
    return str(tl.get("monthly_user_limit_usd") or "0")


def get_monthly_global_limit_usd(cfg: Dict[str, Any]) -> str:
    tl = cfg.get("token_limits") or {}
    return str(tl.get("monthly_global_limit_usd") or "0")
