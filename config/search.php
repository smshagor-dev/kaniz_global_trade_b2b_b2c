<?php

return [
    'provider' => env('SEARCH_PROVIDER', 'database'),
    'fallback_provider' => env('SEARCH_FALLBACK_PROVIDER', 'database'),
    'index_name' => env('SEARCH_INDEX_NAME', 'marketplace'),
    'queue' => env('SEARCH_QUEUE', 'default'),
    'queue_connection' => env('SEARCH_QUEUE_CONNECTION', 'database'),
    'default_limit' => 20,
    'autocomplete_limit' => 8,
    'public_types' => [
        'product',
        'wholesale_product',
        'company',
        'brand',
        'category',
        'rfq',
        'hs_code',
        'port',
        'country',
        'city',
        'freight_forwarder',
    ],
    'languages' => ['en', 'bn', 'ar', 'zh', 'ru', 'fr', 'es', 'de'],
    'synonyms' => [
        'bl' => ['bill of lading', 'bol'],
        'bol' => ['bill of lading', 'bl'],
        'rfq' => ['request for quotation', 'quote request'],
        'po' => ['purchase order'],
        'sku' => ['stock keeping unit'],
        'hs' => ['harmonized system', 'harmonised system'],
        'fcl' => ['full container load'],
        'lcl' => ['less than container load'],
    ],
    'providers' => [
        'database' => [],
        'opensearch' => [
            'base_url' => env('OPENSEARCH_BASE_URL'),
            'username' => env('OPENSEARCH_USERNAME'),
            'password' => env('OPENSEARCH_PASSWORD'),
        ],
        'elasticsearch' => [
            'base_url' => env('ELASTICSEARCH_BASE_URL'),
            'username' => env('ELASTICSEARCH_USERNAME'),
            'password' => env('ELASTICSEARCH_PASSWORD'),
        ],
        'meilisearch' => [
            'base_url' => env('MEILISEARCH_BASE_URL'),
            'api_key' => env('MEILISEARCH_API_KEY'),
        ],
    ],
    'reindex' => [
        'max_sync_chunks' => env('SEARCH_REINDEX_MAX_SYNC_CHUNKS', 25),
        'max_queue_chunks' => env('SEARCH_REINDEX_MAX_QUEUE_CHUNKS', 100),
    ],
];
