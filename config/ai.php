<?php

return [
    'cache' => [
        'ttl_seconds' => env('AI_CACHE_TTL', 3600),
        'store' => env('AI_CACHE_STORE'),
    ],
    'limits' => [
        'max_tokens' => env('AI_MAX_TOKENS', 4096),
        'requests_per_minute' => env('AI_REQUESTS_PER_MINUTE', 20),
    ],
    'queue' => [
        'connection' => env('AI_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'queue' => env('AI_QUEUE_NAME', 'ai'),
    ],
    'providers' => [
        'gemini' => [
            'label' => 'Gemini',
            'base_url' => env('AI_GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],
        'openai' => [
            'label' => 'ChatGPT',
            'base_url' => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],
        'claude' => [
            'label' => 'Claude',
            'base_url' => env('AI_CLAUDE_BASE_URL', 'https://api.anthropic.com/v1'),
        ],
    ],
    'pricing' => [
        'gemini' => [],
        'openai' => [],
        'claude' => [],
    ],
];
