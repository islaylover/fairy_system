import logging
import traceback
from typing import Any, Dict, List
from domain.cost import estimate_cost_usd
from infrastructure.openai_client import call_openai, OpenAIAPIError
from infrastructure import db as repo


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


def build_limit_message(scope: str, ym: str, used_usd: str, limit_usd: str) -> str:
    return f"USAGE_LIMIT_EXCEEDED scope={scope} year_month={ym} used_usd={used_usd} limit_usd={limit_usd}"


def _extract_limits_for_user(
    monthly_user_limit_usd: str,
    monthly_global_limit_usd: str,
) -> Dict[str, str]:
    """
    文字列をそのまま扱う（Decimal変換は repo.is_over_limit 内で実施）
    """
    return {
        "monthly_user_limit_usd": str(monthly_user_limit_usd or "0"),
        "monthly_global_limit_usd": str(monthly_global_limit_usd or "0"),
    }


def process_once(
    conn,
    prompt_map: Dict[str, str],
    pricing_map: Dict[str, Dict[str, float]],
    daily_max_tokens: int,
    monthly_user_limit_usd: str,
    monthly_global_limit_usd: str,
    api_key: str,
    default_model: str,
    timeout: int,
    batch_size: int,
    chat_history_max_messages: int,
) -> int:
    """
    【ユースケース層】
    未処理の ChatGPT リクエストを一定件数処理するバッチユースケース。

    方針：
    - daily_max_tokens は「バッチ全体の当日上限」なので最初に止める
    - 月次上限（global / user）は DB（monthly_usages）を参照して止める
    - global上限は claim前に止める（無駄に processing にしない）
    - user上限は各リクエストごとにチェック（ユーザー別に止めたい）
    """
    if daily_max_tokens > 0:
        with conn.cursor() as cur:
            used_today = repo.get_used_tokens_today(cur)

        if used_today >= daily_max_tokens:
            logging.warning(
                "Daily token limit reached (JST). used_today=%s >= limit=%s. Stop processing.",
                used_today, daily_max_tokens
            )
            return 0

    limits = _extract_limits_for_user(monthly_user_limit_usd, monthly_global_limit_usd)

    processed = 0
    reqs: List[Dict[str, Any]] = []

    # 1) pending -> processing（短トランザクション）
    with conn.cursor() as cur:
        try:
            ym = repo.current_year_month_jst(cur)
            # 今月のOpen AI API総使用額($)取得
            global_used = repo.get_global_monthly_cost_usd(cur, ym)

            if repo.is_over_limit(global_used, monthly_global_limit_usd):
              msg = build_limit_message("global", ym, global_used, limits["monthly_global_limit_usd"])
              logging.warning("Monthly global USD limit reached. %s", msg)
              conn.rollback()
              return 0

            # 「未処理(status:0)」のリクエスト一覧を取得
            reqs = repo.claim_pending(cur, batch_size)
            if not reqs:
                conn.rollback()
                return 0

            for r in reqs:
                # 未処理のリクエストのステータスを「処理中(status:1)」に変更　
                repo.mark_processing(cur, int(r["id"]))

            conn.commit()
        except Exception:
            conn.rollback()
            raise

    # 2) OpenAI -> done / failed
    for r in reqs:
        req_id = int(r["id"])
        user_id = int(r["user_id"])
        conversation_id = int(r.get("conversation_id") or 0)
        model = r.get("model") or default_model
        req_type = r["request_type"]
        src = r["source_text"]

        # ----------------------------
        # 2-0) user（月次）上限チェック
        # ----------------------------
        # ※ここは「processing にした後」なので、超過なら failed に落としてログも残す
        with conn.cursor() as cur:
            try:
                ym = repo.current_year_month_jst(cur)

                # リクエストしたユーザーの月の総利用額($)取得
                user_used = repo.get_user_monthly_cost_usd(cur, user_id, ym)

                if repo.is_over_limit(user_used, limits["monthly_user_limit_usd"]):
                    msg = build_limit_message("user", ym, user_used, limits["monthly_user_limit_usd"])
                    logging.warning("Monthly user USD limit reached. user_id=%s %s", user_id, msg)

                    # 結果として処理できないので failed 扱い（pending に戻したいなら方針変更）
                    repo.insert_request_log(cur, req_id, repo.ROLE_ASSISTANT, msg)
                    repo.mark_failed(cur, req_id, repo.safe_truncate(msg))
                    conn.commit()
                    logging.warning(
                        "LIMITS: daily_max_tokens=%s user=%s global=%s",
                        daily_max_tokens,
                        limits["monthly_user_limit_usd"],
                        limits["monthly_global_limit_usd"],
                    )
                    processed += 1
                    continue

                conn.rollback()  # SELECTだけでも autocommit=False なので念のため明示
            except Exception:
                conn.rollback()
                raise

        system_prompt = prompt_map.get(req_type)
        if not system_prompt:
            msg = repo.safe_truncate(f"Unknown request_type: {req_type}")
            with conn.cursor() as cur:
                repo.insert_request_log(cur, req_id, repo.ROLE_ASSISTANT, msg)
                repo.mark_failed(cur, req_id, msg)
                conn.commit()
            processed += 1
            continue

        with conn.cursor() as cur:
            try:
                repo.insert_request_log(cur, req_id, repo.ROLE_SYSTEM, system_prompt)
                repo.insert_request_log(cur, req_id, repo.ROLE_USER, src)
                conn.commit()
            except Exception:
                conn.rollback()
                raise

        # OpenAIに投げる messages を作る
        messages: List[Dict[str, str]] = [{"role": repo.ROLE_SYSTEM, "content": system_prompt}]

        # conversation_id >= 1 のときだけ過去ログを混ぜる
        if conversation_id >= 1 and chat_history_max_messages > 0:
            with conn.cursor() as cur:
                history = repo.fetch_conversation_history(
                    cur=cur,
                    user_id=user_id,
                    conversation_id=conversation_id,
                    current_request_id=req_id,
                    limit=chat_history_max_messages,
                )
            for h in history:
                messages.append({"role": h["role"], "content": h["message"]})

        # 未処理のリクエストは最後に追加
        messages.append({"role": repo.ROLE_USER, "content": src})

        try:
            result_text, usage = call_openai(
                api_key=api_key,
                model=model,
                messages=messages,
                timeout=timeout,
            )

            input_tokens = usage.get("input_tokens")
            output_tokens = usage.get("output_tokens")
            total_tokens = usage.get("total_tokens")

            estimated_cost = estimate_cost_usd(
                model=model,
                input_tokens=input_tokens,
                output_tokens=output_tokens,
                pricing_map=pricing_map,
            )

            with conn.cursor() as cur:
                # logs
                repo.insert_request_log(cur, req_id, repo.ROLE_ASSISTANT, result_text)

                # requests を done に更新（確定値）
                repo.mark_done(
                    cur,
                    req_id,
                    result_text,
                    input_tokens,
                    output_tokens,
                    total_tokens,
                    estimated_cost,
                )

                # 追加：usage_ledgers + monthly_usages を計上
                # （request_id UNIQUE で二重計上防止。重複なら monthly も増やさない）
                repo.record_usage_after_done(
                    cur=cur,
                    request_id=req_id,
                    user_id=user_id,
                    prompt_tokens=input_tokens,
                    completion_tokens=output_tokens,
                    total_tokens=total_tokens,
                    estimated_cost_usd=estimated_cost,
                )

                conn.commit()

        except OpenAIAPIError as e:
            err = f"{type(e).__name__}: {e}\n{traceback.format_exc()}"
            with conn.cursor() as cur:
                repo.insert_request_log(cur, req_id, repo.ROLE_ASSISTANT, err)
                repo.mark_failed(cur, req_id, repo.safe_truncate(err))
                conn.commit()

            if is_fatal_openai_error(e):
                logging.error("Fatal OpenAI error detected. Stop batch. req_id=%s status=%s", req_id, e.status_code)
                raise FatalBatchError(str(e))

        except Exception as e:
            err = f"{type(e).__name__}: {e}\n{traceback.format_exc()}"
            with conn.cursor() as cur:
                repo.insert_request_log(cur, req_id, repo.ROLE_ASSISTANT, err)
                repo.mark_failed(cur, req_id, repo.safe_truncate(err))
                conn.commit()

        processed += 1

    return processed
