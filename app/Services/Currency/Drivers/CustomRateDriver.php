<?php

namespace App\Services\Currency\Drivers;

use App\Models\CurrencyApiSetting;
use App\Services\Currency\ExchangeRateProviderInterface;

class CustomRateDriver implements ExchangeRateProviderInterface
{
    public function fetchRates(CurrencyApiSetting $settings, string $baseCurrencyCode, array $currencyCodes = []): array
    {
        $rates = collect($settings->custom_rates ?? [])
            ->mapWithKeys(fn ($rate, $code) => [strtoupper((string) $code) => (float) $rate])
            ->all();

        if ($currencyCodes !== []) {
            $rates = array_intersect_key($rates, array_flip(array_map('strtoupper', $currencyCodes)));
        }

        return [
            'base_currency_code' => strtoupper($baseCurrencyCode),
            'rates' => $rates,
            'raw' => ['source' => 'custom'],
            'fetched_at' => now(),
        ];
    }
}
