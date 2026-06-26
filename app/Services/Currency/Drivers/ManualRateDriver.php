<?php

namespace App\Services\Currency\Drivers;

use App\Models\CurrencyApiSetting;
use App\Services\Currency\ExchangeRateProviderInterface;

class ManualRateDriver implements ExchangeRateProviderInterface
{
    public function fetchRates(CurrencyApiSetting $settings, string $baseCurrencyCode, array $currencyCodes = []): array
    {
        $rates = $settings->custom_rates ?? [];
        if ($currencyCodes !== []) {
            $rates = array_intersect_key($rates, array_flip(array_map('strtoupper', $currencyCodes)));
        }

        return [
            'base_currency_code' => strtoupper($baseCurrencyCode),
            'rates' => $rates,
            'raw' => ['source' => 'manual'],
            'fetched_at' => now(),
        ];
    }
}
