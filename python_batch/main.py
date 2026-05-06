import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

import os
import sys
import time
import logging
import traceback

from infrastructure.db import db_connect, env_int
from infrastructure.laravel_config import (
    fetch_batch_config,
    build_prompt_map,
    build_pricing_map,
    get_daily_max_tokens,
    get_monthly_user_limit_usd,
    get_monthly_global_limit_usd,
)
from usecase.process_requests import process_once, FatalBatchError


def sleep(sec: float) -> None:
    time.sleep(sec)


def main() -> None:
    """
    【エントリポイント】
    バッチ処理の起点。

    - Laravel API から設定を取得（Single Source of Truth）
    - インフラ依存の初期化
    - ユースケースの実行ループを制御
    """
    batch_base_url = os.getenv("BATCH_BASE_URL", "https://nginx")
    batch_key = os.getenv("BATCH_API_KEY", "")
    verify_tls = os.getenv("BATCH_VERIFY_TLS", "false").lower() in ("1", "true")

    api_key = os.getenv("OPENAI_API_KEY") or ""
    default_model = os.getenv("OPENAI_MODEL", "gpt-4o")

    timeout = env_int("OPENAI_TIMEOUT", 60)
    batch_size = env_int("BATCH_SIZE", 1)
    max_per_run = env_int("MAX_PER_RUN", 200)

    chat_history_max_messages = env_int("OPENAI_HISTORY_MAX_MESSAGES", 6)

    assert batch_key, "BATCH_API_KEY not set"
    assert api_key, "OPENAI_API_KEY not set"

    cfg = fetch_batch_config(batch_base_url, batch_key, verify_tls)
    prompt_map = build_prompt_map(cfg)
    pricing_map = build_pricing_map(cfg)
    daily_max_tokens = get_daily_max_tokens(cfg)
    monthly_user_limit_usd = get_monthly_user_limit_usd(cfg)
    monthly_global_limit_usd = get_monthly_global_limit_usd(cfg)
    logging.warning("CFG token_limits=%s", cfg.get("token_limits"))
    logging.warning("CFG pricing_models=%s", list(pricing_map.keys()))
    logging.warning("CFG prompt_types=%s", list(prompt_map.keys()))

    conn = db_connect()
    try:
        total = 0
        while True:
            # 未処理リクエストを最大 batch_size 件取得し、OpenAI API連携処理を実行する
            # 戻り値 n は、今回の process_once() で処理した件数
            # ※正常終了だけでなく、上限超過やエラーにより failed にした件数も含む
            n = process_once(
                conn=conn,
                prompt_map=prompt_map,
                pricing_map=pricing_map,
                daily_max_tokens=daily_max_tokens,
                monthly_user_limit_usd=monthly_user_limit_usd,
                monthly_global_limit_usd=monthly_global_limit_usd,
                api_key=api_key,
                default_model=default_model,
                timeout=timeout,
                batch_size=batch_size,
                chat_history_max_messages=chat_history_max_messages,
            )
            total += n

            if n == 0:
                logging.info("No pending requests (or daily limit reached). Exit.")
                break

            if total >= max_per_run:
                logging.info("Reached MAX_PER_RUN=%s. Exit.", max_per_run)
                break

            sleep(float(os.getenv("SLEEP_LOOP_SECONDS", "0.2")))

        logging.info("Batch finished. processed=%s", total)

    finally:
        conn.close()


if __name__ == "__main__":
    try:
        main()
        sys.exit(0)
    except FatalBatchError as e:
        logging.error("FATAL: %s", e)
        sys.exit(1)
    except Exception as e:
        logging.error("ERROR: %s\n%s", e, traceback.format_exc())
        sys.exit(1)
