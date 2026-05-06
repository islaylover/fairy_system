from typing import Dict, Optional

def estimate_cost_usd(
    model: str,
    input_tokens: Optional[int],
    output_tokens: Optional[int],
    pricing_map: Dict[str, Dict[str, float]],
) -> Optional[float]:
    """
【ドメイン層】
    OpenAI リクエストのトークン使用量から、概算の利用コスト（USD）を算出する。

    この関数は「コスト計算」という業務ルールのみを扱い、
    DB、API、ログなどのインフラ処理には一切依存しない。

    Args:
        model (str):
            モデルID（例: "gpt-4o"）
        input_tokens (Optional[int]):
            入力トークン数
        output_tokens (Optional[int]):
            出力トークン数
        pricing_map (Dict[str, Dict[str, float]]):
            モデルごとの価格定義。
            形式：
            {
                "model_id": {
                    "input": float,   # 入力トークン 100万件あたりのUSD
                    "output": float,  # 出力トークン 100万件あたりのUSD
                }
            }

    Returns:
        Optional[float]:
            計算されたコスト（USD、小数点5桁で丸め）。
            価格情報やトークン数が不足している場合は None を返す。
    """
    p = pricing_map.get(model)
    if not p or input_tokens is None or output_tokens is None:
        return None

    cost = (input_tokens / 1_000_000.0) * p["input"] + (output_tokens / 1_000_000.0) * p["output"]
    return round(cost, 5)