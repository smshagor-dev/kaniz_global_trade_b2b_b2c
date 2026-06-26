<?php

return [
    'drivers' => [
        'maersk' => \App\Services\Freight\MaerskFreightDriver::class,
        'msc' => \App\Services\Freight\MscFreightDriver::class,
        'cma_cgm' => \App\Services\Freight\CmaCgmFreightDriver::class,
        'hapag_lloyd' => \App\Services\Freight\HapagLloydFreightDriver::class,
        'cosco' => \App\Services\Freight\CoscoFreightDriver::class,
        'evergreen' => \App\Services\Freight\EvergreenFreightDriver::class,
        'one' => \App\Services\Freight\OneFreightDriver::class,
        'dp_world' => \App\Services\Freight\DpWorldFreightDriver::class,
        'freightos' => \App\Services\Freight\FreightosDriver::class,
        'flexport' => \App\Services\Freight\FlexportDriver::class,
        'custom' => \App\Services\Freight\CustomFreightDriver::class,
    ],
    'providers' => [
        'maersk' => [
            'name' => 'Maersk',
            'sandbox_url' => env('B2B_MAERSK_SANDBOX_URL', 'https://api.maersk.com'),
            'production_url' => env('B2B_MAERSK_PRODUCTION_URL', 'https://api.maersk.com'),
        ],
        'msc' => [
            'name' => 'MSC',
            'sandbox_url' => env('B2B_MSC_SANDBOX_URL', 'https://api.msc.com'),
            'production_url' => env('B2B_MSC_PRODUCTION_URL', 'https://api.msc.com'),
        ],
        'cma_cgm' => [
            'name' => 'CMA CGM',
            'sandbox_url' => env('B2B_CMA_CGM_SANDBOX_URL', 'https://api.cma-cgm.com'),
            'production_url' => env('B2B_CMA_CGM_PRODUCTION_URL', 'https://api.cma-cgm.com'),
        ],
        'hapag_lloyd' => [
            'name' => 'Hapag-Lloyd',
            'sandbox_url' => env('B2B_HAPAG_LLOYD_SANDBOX_URL', 'https://api.hapag-lloyd.com'),
            'production_url' => env('B2B_HAPAG_LLOYD_PRODUCTION_URL', 'https://api.hapag-lloyd.com'),
        ],
        'cosco' => [
            'name' => 'COSCO',
            'sandbox_url' => env('B2B_COSCO_SANDBOX_URL', 'https://api.coscoshipping.com'),
            'production_url' => env('B2B_COSCO_PRODUCTION_URL', 'https://api.coscoshipping.com'),
        ],
        'evergreen' => [
            'name' => 'Evergreen',
            'sandbox_url' => env('B2B_EVERGREEN_SANDBOX_URL', 'https://api.evergreen-shipping.com'),
            'production_url' => env('B2B_EVERGREEN_PRODUCTION_URL', 'https://api.evergreen-shipping.com'),
        ],
        'one' => [
            'name' => 'ONE',
            'sandbox_url' => env('B2B_ONE_SANDBOX_URL', 'https://api.one-line.com'),
            'production_url' => env('B2B_ONE_PRODUCTION_URL', 'https://api.one-line.com'),
        ],
        'dp_world' => [
            'name' => 'DP World',
            'sandbox_url' => env('B2B_DP_WORLD_SANDBOX_URL', 'https://api.dpworld.com'),
            'production_url' => env('B2B_DP_WORLD_PRODUCTION_URL', 'https://api.dpworld.com'),
        ],
        'freightos' => [
            'name' => 'Freightos',
            'sandbox_url' => env('B2B_FREIGHTOS_SANDBOX_URL', 'https://ship.freightos.com'),
            'production_url' => env('B2B_FREIGHTOS_PRODUCTION_URL', 'https://ship.freightos.com'),
        ],
        'flexport' => [
            'name' => 'Flexport',
            'sandbox_url' => env('B2B_FLEXPORT_SANDBOX_URL', 'https://api.flexport.com'),
            'production_url' => env('B2B_FLEXPORT_PRODUCTION_URL', 'https://api.flexport.com'),
        ],
        'custom' => [
            'name' => 'Custom Freight Forwarder',
        ],
    ],
    'http' => [
        'timeout' => (int) env('B2B_FREIGHT_TIMEOUT', 30),
        'connect_timeout' => (int) env('B2B_FREIGHT_CONNECT_TIMEOUT', 10),
        'retry_times' => (int) env('B2B_FREIGHT_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('B2B_FREIGHT_RETRY_SLEEP_MS', 500),
        'circuit_threshold' => (int) env('B2B_FREIGHT_CIRCUIT_THRESHOLD', 5),
        'circuit_cooldown_seconds' => (int) env('B2B_FREIGHT_CIRCUIT_COOLDOWN', 300),
    ],
];
