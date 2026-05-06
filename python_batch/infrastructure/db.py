import os
from typing import Any, Dict, List, Optional

import pymysql
from pymysql.cursors import DictCursor
from pymysql.err import IntegrityError

from decimal import Decimal, ROUND_DOWN


STATUS_PENDING    = 0
STATUS_PROCESSING = 1
STATUS_DONE       = 2
STATUS_FAILED     = 9

ROLE_SYSTEM    = "system"
ROLE_USER      = "user"
ROLE_ASSISTANT = "assistant"


USD_SCALE_5 = Decimal("0.00001")  # scale=5


def env_int(key: str, default: int) -> int:
    v = os.getenv(key)
    return int(v) if v and v.strip().isdigit() else default


def db_connect() -> pymysql.connections.Connection:
    return pymysql.connect(
        host=os.getenv("DB_HOST", "mysql"),
        port=env_int("DB_PORT", 3306),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_DATABASE", ""),
        charset="utf8mb4",
        cursorclass=DictCursor,
        autocommit=False,
    )


def safe_truncate(s: Optional[str], limit: int = 65000) -> str:
    if not s:
        return ""
    return s if len(s) <= limit else s[:limit]


def format_usd_5(v: Optional[float]) -> Optional[str]:
    """
    float を decimal(?,5) へ安全に入れるための文字列化（固定小数5桁）
    """
    if v is None:
        return None
    d = Decimal(str(v)).quantize(USD_SCALE_5, rounding=ROUND_DOWN)
    return format(d, "f")  # fixed notation


def claim_pending(cur, batch_size: int) -> List[Dict[str, Any]]:
    cur.execute(
        """
        SELECT id, user_id, conversation_id, model, request_type, source_text
        FROM requests
        WHERE status=%s
        ORDER BY id
        LIMIT %s
        FOR UPDATE SKIP LOCKED
        """,
        (STATUS_PENDING, batch_size),
    )
    return cur.fetchall() or []


def fetch_conversation_history(
    cur,
    user_id: int,
    conversation_id: int,
    current_request_id: int,
    limit: int,
) -> List[Dict[str, Any]]:
    cur.execute(
        """
        SELECT rl.role, rl.message
        FROM request_logs rl
        JOIN requests r ON r.id = rl.request_id
        WHERE r.user_id = %s
          AND r.conversation_id = %s
          AND r.status = %s
          AND r.id < %s
          AND rl.role IN (%s, %s)
        ORDER BY rl.id DESC
        LIMIT %s
        """,
        (
            user_id,
            conversation_id,
            STATUS_DONE,
            current_request_id,
            ROLE_USER,
            ROLE_ASSISTANT,
            limit,
        ),
    )
    rows = cur.fetchall() or []
    return list(reversed(rows))


def mark_processing(cur, req_id: int) -> None:
    cur.execute(
        """
        UPDATE requests
        SET status=%s, updated_at=NOW()
        WHERE id=%s
        """,
        (STATUS_PROCESSING, req_id),
    )


def mark_done(
    cur,
    req_id: int,
    result_text: str,
    prompt_tokens: Optional[int],
    completion_tokens: Optional[int],
    total_tokens: Optional[int],
    estimated_cost_usd: Optional[float],
) -> None:
    cur.execute(
        """
        UPDATE requests
        SET
          status=%s,
          result_text=%s,
          prompt_tokens=%s,
          completion_tokens=%s,
          total_tokens=%s,
          estimated_cost_usd=%s,
          updated_at=NOW()
        WHERE id=%s
        """,
        (
            STATUS_DONE,
            result_text,
            prompt_tokens,
            completion_tokens,
            total_tokens,
            format_usd_5(estimated_cost_usd),
            req_id,
        ),
    )


def mark_failed(cur, req_id: int, error_text: str) -> None:
    cur.execute(
        """
        UPDATE requests
        SET status=%s, result_text=%s, updated_at=NOW()
        WHERE id=%s
        """,
        (STATUS_FAILED, error_text, req_id),
    )


def insert_request_log(cur, request_id: int, role: str, message: str) -> None:
    cur.execute(
        """
        INSERT INTO request_logs (request_id, role, message, created_at, updated_at)
        VALUES (%s, %s, %s, NOW(), NOW())
        """,
        (request_id, role, safe_truncate(message)),
    )


def get_used_tokens_today(cur) -> int:
    """
    MySQLがJST運用になっている前提：
      DATE(updated_at)=CURDATE() が日本時間の「今日」
    """
    cur.execute(
        """
        SELECT COALESCE(SUM(total_tokens), 0) AS used
        FROM requests
        WHERE status=%s
          AND DATE(updated_at) = CURDATE()
        """,
        (STATUS_DONE,),
    )
    row = cur.fetchone() or {}
    return int(row.get("used") or 0)


# ----------------------------
# Usage accounting
# ----------------------------

def insert_usage_ledger_once(
    cur,
    request_id: int,
    user_id: int,
    prompt_tokens: int,
    completion_tokens: int,
    total_tokens: int,
    estimated_cost_usd_str: str,
) -> bool:
    """
    usage_ledgers に確定値を記録する（request_id UNIQUEで二重計上防止）
    追加できたら True / 既に存在なら False
    """
    try:
        cur.execute(
            """
            INSERT INTO `usage_ledgers`
              (`request_id`, `user_id`, `year_month`,
               `prompt_tokens`, `completion_tokens`, `total_tokens`, `estimated_cost_usd`,
               `created_at`, `updated_at`)
            VALUES
              (%s, %s, DATE_FORMAT(NOW(), '%%Y-%%m'),
               %s, %s, %s, %s,
               NOW(), NOW())
            """,
            (
                request_id,
                user_id,
                prompt_tokens,
                completion_tokens,
                total_tokens,
                estimated_cost_usd_str,
            ),
        )
        return True
    except IntegrityError as e:
        # duplicate key (MySQL errno=1062)
        if getattr(e, "args", None) and len(e.args) > 0 and e.args[0] == 1062:
            return False
        raise


def upsert_add_monthly_usage(
    cur,
    user_id: int,
    prompt_tokens: int,
    completion_tokens: int,
    total_tokens: int,
    estimated_cost_usd_str: str,
) -> None:
    cur.execute(
        """
        INSERT INTO `monthly_usages`
          (`user_id`, `year_month`, `prompt_tokens`, `completion_tokens`, `total_tokens`,
           `estimated_cost_usd`, `requests_done_count`, `created_at`, `updated_at`)
        VALUES
          (%s, DATE_FORMAT(NOW(), '%%Y-%%m'), %s, %s, %s, %s, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          `prompt_tokens` = `prompt_tokens` + VALUES(`prompt_tokens`),
          `completion_tokens` = `completion_tokens` + VALUES(`completion_tokens`),
          `total_tokens` = `total_tokens` + VALUES(`total_tokens`),
          `estimated_cost_usd` = `estimated_cost_usd` + VALUES(`estimated_cost_usd`),
          `requests_done_count` = `requests_done_count` + 1,
          `updated_at` = NOW()
        """,
        (user_id, prompt_tokens, completion_tokens, total_tokens, estimated_cost_usd_str),
    )


def record_usage_after_done(
    cur,
    request_id: int,
    user_id: int,
    prompt_tokens: Optional[int],
    completion_tokens: Optional[int],
    total_tokens: Optional[int],
    estimated_cost_usd: Optional[float],
) -> bool:
    """
    Done後に usage_ledgers と monthly_usages を更新する。
    二重計上で ledger が insert できなければ monthly も更新しない。

    Returns:
      True  = 今回新規に計上した
      False = 既に計上済み
    """
    pt = int(prompt_tokens or 0)
    ct = int(completion_tokens or 0)
    tt = int(total_tokens or 0)

    cost_str = format_usd_5(estimated_cost_usd)
    if cost_str is None:
        cost_str = "0.00000"

    inserted = insert_usage_ledger_once(
        cur=cur,
        request_id=request_id,
        user_id=user_id,
        prompt_tokens=pt,
        completion_tokens=ct,
        total_tokens=tt,
        estimated_cost_usd_str=cost_str,
    )

    if not inserted:
        return False

    upsert_add_monthly_usage(
        cur=cur,
        user_id=user_id,
        prompt_tokens=pt,
        completion_tokens=ct,
        total_tokens=tt,
        estimated_cost_usd_str=cost_str,
    )
    return True



def current_year_month_jst(cur) -> str:
    cur.execute("SELECT DATE_FORMAT(NOW(), '%Y-%m') AS ym")
    row = cur.fetchone() or {}
    return str(row.get("ym") or "")

def get_user_monthly_cost_usd(cur, user_id: int, year_month: str) -> str:
    cur.execute(
        """
        SELECT COALESCE(estimated_cost_usd, 0) AS cost
        FROM monthly_usages
        WHERE user_id=%s AND `year_month`=%s
        LIMIT 1
        """,
        (user_id, year_month),
    )
    row = cur.fetchone() or {}
    return str(row.get("cost") or "0.00000")

def get_global_monthly_cost_usd(cur, year_month: str) -> str:
    cur.execute(
        """
        SELECT COALESCE(SUM(estimated_cost_usd), 0) AS cost
        FROM monthly_usages
        WHERE `year_month`=%s
        """,
        (year_month,),
    )
    print("[DEBUG] get_global_monthly_cost_usd called. ym=", year_month)
    row = cur.fetchone() or {}
    return str(row.get("cost") or "0.00000")

def is_over_limit(used_usd_str: str, limit_usd_str: str) -> bool:
    # limit <= 0 => unlimited
    limit = Decimal(str(limit_usd_str or "0"))
    if limit <= 0:
        return False
    used = Decimal(str(used_usd_str or "0"))
    return used >= limit

def mark_pending(cur, req_id: int) -> None:
    cur.execute(
        """
        UPDATE requests
        SET status=%s, updated_at=NOW()
        WHERE id=%s
        """,
        (STATUS_PENDING, req_id),
    )