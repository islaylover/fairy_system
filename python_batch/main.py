import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

import os
import sys
import time
import logging
import traceback

from infrastructure.laravel_config import (
    fetch_batch_config,
)
from infrastructure.laravel_batch_api_client import LaravelBatchApiClient
from usecase.process_requests import process_once, FatalBatchError


def sleep(sec: float) -> None:
    time.sleep(sec)


def env_int(key: str, default: int) -> int:
    value = os.getenv(key)
    return int(value) if value and value.strip().isdigit() else default


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
    batch_api_timeout = env_int("BATCH_API_TIMEOUT", 30)
    batch_size = env_int("BATCH_SIZE", 1)
    max_per_run = env_int("MAX_PER_RUN", 200)

    assert batch_key, "BATCH_API_KEY not set"
    assert api_key, "OPENAI_API_KEY not set"

    cfg = fetch_batch_config(batch_base_url, batch_key, verify_tls)
    logging.warning("CFG token_limits=%s", cfg.get("token_limits"))
    logging.warning("CFG models=%s", [model.get("id") for model in (cfg.get("models") or [])])
    batch_api_client = LaravelBatchApiClient(
        base_url=batch_base_url,
        batch_key=batch_key,
        verify_tls=verify_tls,
        timeout=batch_api_timeout,
    )

    total = 0
    while True:
        # 未処理リクエストを最大 batch_size 件取得し、OpenAI API連携処理を実行する
        # 戻り値 n は、今回の process_once() で処理した件数
        # ※正常終了だけでなく、上限超過やエラーにより failed にした件数も含む
        n = process_once(
            api_key=api_key,
            default_model=default_model,
            timeout=timeout,
            batch_size=batch_size,
            batch_api_client=batch_api_client,
        )
        total += n

        if n == 0:
            logging.info("No pending requests (or limit reached). Exit.")
            break

        if total >= max_per_run:
            logging.info("Reached MAX_PER_RUN=%s. Exit.", max_per_run)
            break

        sleep(float(os.getenv("SLEEP_LOOP_SECONDS", "0.2")))

    logging.info("Batch finished. processed=%s", total)


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
