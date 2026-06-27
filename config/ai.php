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
            'label' => 'OpenAI',
            'base_url' => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],
        'claude' => [
            'label' => 'Claude',
            'base_url' => env('AI_CLAUDE_BASE_URL', 'https://api.anthropic.com/v1'),
        ],
        'deepseek' => [
            'label' => 'DeepSeek',
            'base_url' => env('AI_DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        ],
        'ollama' => [
            'label' => 'Ollama',
            'base_url' => env('AI_OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        ],
        'custom' => [
            'label' => 'Custom',
            'base_url' => env('AI_CUSTOM_BASE_URL'),
        ],
    ],
    'pricing' => [
        'gemini' => [],
        'openai' => [],
        'claude' => [],
        'deepseek' => [],
        'ollama' => [],
        'custom' => [],
    ],
];
