<?php

namespace App\Services\Currency\Drivers;

use App\Models\CurrencyApiSetting;
use App\Services\Currency\ExchangeRateProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExchangeRateApiDriver implements ExchangeRateProviderInterface
{
    public function fetchRates(CurrencyApiSetting $settings, string $baseCurrencyCode, array $currencyCodes = []): array
    {
        $apiKey = $settings->getApiKey() ?: env('EXCHANGE_RATE_API_KEY');
        if (!$apiKey) {
            throw new RuntimeException('ExchangeRate-API key is not configured.');
        }

        $response = Http::timeout(15)
            ->retry(2, 500)
            ->acceptJson()
            ->get(sprintf(
                'https://v6.exchangerate-api.com/v6/%s/latest/%s',
                $apiKey,
                strtoupper($baseCurrencyCode)
            ));

        if (!$response->successful()) {
            throw new RuntimeException('Exchange rate provider request failed with HTTP '.$response->status().'.');
        }

        $payload = $response->json();
        if (($payload['result'] ?? null) !== 'success') {
            throw new RuntimeException((string) ($payload['error-type'] ?? 'Exchange rate provider returned an invalid response.'));
        }

        $rates = $payload['conversion_rates'] ?? [];
        if ($currencyCodes !== []) {
            $rates = array_intersect_key($rates, array_flip(array_map('strtoupper', $currencyCodes)));
        }

        return [
            'base_currency_code' => strtoupper((string) ($payload['base_code'] ?? $baseCurrencyCode)),
            'rates' => $rates,
            'raw' => $payload,
            'fetched_at' => now(),
        ];
    }
}
