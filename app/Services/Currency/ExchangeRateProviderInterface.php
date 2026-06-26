<?php

namespace App\Services\Currency;

use App\Models\CurrencyApiSetting;

interface ExchangeRateProviderInterface
{
    public function fetchRates(CurrencyApiSetting $settings, string $baseCurrencyCode, array $currencyCodes = []): array;
}
