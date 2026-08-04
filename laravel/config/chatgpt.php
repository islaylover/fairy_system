<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available Models (UI & API)
    |--------------------------------------------------------------------------
    */
    'models' => [
        'gpt-4o' => [
            'label' => 'GPT-4o',
            // $ / 1,000,000 tokens (Standard)
            'price_per_million_tokens' => ['input' => 2.5, 'output' => 10.0],
            'max_tokens' => 128000,
            'category' => 'gpt',
        ],
        'gpt-4' => [
            // $ / 1,000,000 tokens 100トークンあたり
            'price_per_million_tokens' => ['input' => 30.0, 'output' => 60.0],
            'max_tokens' => 8192,
            'category' => 'gpt',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Types
    |--------------------------------------------------------------------------
    */
    'request_type_prompts' => [
        'summary' => [
            'system_prompt' => 'あなたはプロの編集者です。以下の文章を日本語で200文字以内に要約してください。'
                .' 箇条書きは禁止し、重要な点を漏らさないでください。',
        ],
        'translate' => [
            'system_prompt' => 'あなたはプロの翻訳者です。以下の文章を自然で読みやすい日本語に翻訳してください。'
                .' 意味を変えず、直訳になりすぎないよう注意してください。',
        ],
        'rewrite' => [
            'system_prompt' => 'あなたは文章校正の専門家です。以下の文章を意味を変えずに、'
                .'より分かりやすく、読みやすい日本語に書き換えてください。',
        ],
        'format_table' => [
            'system_prompt' => 'あなたは情報整理の専門家です。以下の文章を分析し、'
                .'適切な項目に分けて Markdown の表形式で出力してください。',
        ],
        'freeform' => [
            'system_prompt' => 'あなたは親切で有能なアシスタントです。'
                .'ユーザーの意図を理解し、最適な回答を提供してください。',
        ],
    ],

    'history_max_messages' => (int) env('OPENAI_HISTORY_MAX_MESSAGES', 6),

    /*
    |--------------------------------------------------------------------------
    | Token Limits
    |--------------------------------------------------------------------------
    */
    'token_limits' => [
        'max_per_request' => 8000,
        'max_total' => 128000,
        'daily_max_tokens' => (int) env('CHATGPT_DAILY_MAX_TOKENS', 0), // 0=無制限
        // 会員（ユーザー）ごとの月上限
        'monthly_user_limit_usd' => env('CHATGPT_MONTHLY_USER_LIMIT_USD', '1.00000'),
        // システム全体（月上限）
        'monthly_global_limit_usd' => env('CHATGPT_MONTHLY_GLOBAL_LIMIT_USD', '10.00000'),
    ],
];
