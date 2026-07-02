<?php

namespace App\Services\Currency;

use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\CurrencyApiSetting;
use App\Models\CurrencyExchangeRate;
use App\Models\CurrencyRateHistory;
use App\Services\Currency\Drivers\CustomRateDriver;
use App\Services\Currency\Drivers\ExchangeRateApiDriver;
use App\Services\Currency\Drivers\ManualRateDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CurrencyService
{
    public function baseCurrency(): Currency
    {
        if (!Schema::hasTable('currencies')) {
            return $this->fallbackCurrency();
        }

        $baseCurrencyId = get_setting('system_default_currency');
        $currency = $baseCurrencyId ? Currency::find($baseCurrencyId) : null;

        if (!$currency) {
            $currency = Currency::where('is_base_currency', true)->first();
        }

        return $currency ?: (Currency::first() ?: $this->fallbackCurrency());
    }

    public function defaultDisplayCurrency(): Currency
    {
        $code = $this->settings()?->default_display_currency_code;
        if ($code) {
            $currency = Currency::where('code', strtoupper($code))->first();
            if ($currency) {
                return $currency;
            }
        }

        return $this->baseCurrency();
    }

    public function resolveDisplayCurrency(?Request $request = null): Currency
    {
        $request = $request ?: request();
        $code = null;

        if (session()->has('currency_code')) {
            $code = session()->get('currency_code');
        }

        if ($request && $request->header('Currency-Code')) {
            $code = $request->header('Currency-Code');
        }

        if ($code) {
            $currency = Currency::where('code', strtoupper($code))->first();
            if ($currency) {
                return $currency;
            }
        }

        return $this->defaultDisplayCurrency();
    }

    public function convert(float $amount, string $fromCurrencyCode, string $toCurrencyCode): float
    {
        $fromRate = $this->rateFor($fromCurrencyCode);
        $toRate = $this->rateFor($toCurrencyCode);

        if ($fromRate <= 0 || $toRate <= 0) {
            return $amount;
        }

        $baseAmount = $amount / $fromRate;
        return round($baseAmount * $toRate, 8);
    }

    public function convertFromBase(float $amount, string $toCurrencyCode): float
    {
        return $this->convert($amount, $this->baseCurrency()->code, $toCurrencyCode);
    }

    public function convertToBase(float $amount, string $fromCurrencyCode): float
    {
        return $this->convert($amount, $fromCurrencyCode, $this->baseCurrency()->code);
    }

    public function convertForDisplay(float $amount, ?string $fromCurrencyCode = null, ?Request $request = null): float
    {
        $displayCurrency = $this->resolveDisplayCurrency($request);
        $fromCurrencyCode = $fromCurrencyCode ?: $this->baseCurrency()->code;

        return $this->convert($amount, $fromCurrencyCode, $displayCurrency->code);
    }

    public function format(
        float $amount,
        ?string $currencyCode = null,
        bool $withSymbol = true,
        bool $minimize = false
    ): string {
        $currency = $currencyCode
            ? Currency::where('code', strtoupper($currencyCode))->first()
            : $this->resolveDisplayCurrency();

        $currency = $currency ?: $this->baseCurrency();

        $decimals = $currency->decimal_places ?? (int) get_setting('no_of_decimals');
        $decimalSeparator = ($currency->decimal_separator ?? null) === 'comma'
            || (int) get_setting('decimal_separator') === 2
            ? ','
            : '.';
        $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';

        $formatted = number_format($amount, $decimals, $decimalSeparator, $thousandsSeparator);

        if ($minimize) {
            $formatted = $this->formatMinimizedAmount($amount, $decimals);
        }

        if (!$withSymbol) {
            return $formatted;
        }

        $symbol = $currency->symbol ?: $currency->code;
        $symbolPosition = $currency->symbol_position ?: $this->legacySymbolPosition();

        return match ($symbolPosition) {
            'prefix_spaced' => $symbol.' '.$formatted,
            'suffix' => $formatted.$symbol,
            'suffix_spaced' => $formatted.' '.$symbol,
            default => $symbol.$formatted,
        };
    }

    public function snapshot(?string $currencyCode = null): array
    {
        $currency = $currencyCode
            ? Currency::where('code', strtoupper($currencyCode))->first()
            : $this->resolveDisplayCurrency();

        $currency = $currency ?: $this->baseCurrency();
        $baseCurrency = $this->baseCurrency();

        return [
            'base_currency' => $baseCurrency->code,
            'currency' => $currency->code,
            'exchange_rate' => (float) $currency->exchange_rate,
            'decimal_places' => (int) ($currency->decimal_places ?? get_setting('no_of_decimals')),
            'symbol' => $currency->symbol,
            'symbol_position' => $currency->symbol_position ?: $this->legacySymbolPosition(),
            'captured_at' => now()->toIso8601String(),
        ];
    }

    public function settings(): ?CurrencyApiSetting
    {
        if (!Schema::hasTable('currency_api_settings')) {
            return null;
        }

        $settings = CurrencyApiSetting::query()
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($settings) {
            $this->persistEnvironmentApiKey($settings);
        }

        return $settings;
    }

    public function testConnection(): array
    {
        $settings = $this->settings();
        if (!$settings) {
            throw new RuntimeException('No active currency API settings found.');
        }

        $driver = $this->resolveDriver($settings->driver ?: $settings->provider);
        return $driver->fetchRates($settings, $settings->base_currency_code ?: $this->baseCurrency()->code);
    }

    public function shouldSyncNow(?CurrencyApiSetting $settings = null): bool
    {
        $settings = $settings ?: $this->settings();
        if (!$settings || !$settings->auto_sync_enabled) {
            return false;
        }

        if (!$settings->last_sync_at) {
            return true;
        }

        return match ($settings->sync_frequency) {
            'six_hours' => $settings->last_sync_at->lte(now()->subHours(6)),
            'weekly' => $settings->last_sync_at->lte(now()->subWeek()),
            'daily' => $settings->last_sync_at->lte(now()->subDay()),
            default => $settings->last_sync_at->lte(now()->subHour()),
        };
    }

    public function sync(bool $force = false): array
    {
        $settings = $this->settings();
        if (!$settings) {
            throw new RuntimeException('No active currency API settings found.');
        }

        if (!$force && !$this->shouldSyncNow($settings)) {
            return [
                'status' => 'skipped',
                'message' => 'Currency sync is not due yet.',
            ];
        }

        $baseCurrency = strtoupper($settings->base_currency_code ?: $this->baseCurrency()->code);
        $driver = $this->resolveDriver($settings->driver ?: $settings->provider);

        try {
            $payload = $driver->fetchRates($settings, $baseCurrency, []);
            $syncBatch = (string) now()->timestamp;

            DB::transaction(function () use ($payload, $settings, $syncBatch, $baseCurrency) {
                $rates = collect($payload['rates'] ?? [])
                    ->mapWithKeys(fn ($rate, $code) => [strtoupper((string) $code) => (float) $rate]);

                $rates[$baseCurrency] = 1.0;

                foreach ($rates as $currencyCode => $rate) {
                    $currency = Currency::query()->firstOrNew(['code' => $currencyCode]);
                    $currency->name = $currency->name ?: $currencyCode;
                    $currency->symbol = $currency->symbol ?: $currencyCode;
                    $currency->exchange_rate = $rate;
                    $currency->status = $currency->exists ? $currency->status : true;
                    $currency->save();

                    CurrencyExchangeRate::updateOrCreate(
                        [
                            'base_currency_code' => $baseCurrency,
                            'currency_code' => $currencyCode,
                        ],
                        [
                            'rate' => $rate,
                            'provider' => $settings->provider,
                            'is_manual_override' => false,
                            'synced_at' => now(),
                            'source_updated_at' => $payload['fetched_at'] ?? now(),
                            'meta' => ['driver' => $settings->driver ?: $settings->provider],
                        ]
                    );

                    CurrencyRateHistory::create([
                        'base_currency_code' => $baseCurrency,
                        'currency_code' => $currencyCode,
                        'rate' => $rate,
                        'provider' => $settings->provider,
                        'sync_batch' => $syncBatch,
                        'synced_at' => now(),
                        'meta' => ['driver' => $settings->driver ?: $settings->provider],
                    ]);

                }

                $settings->update([
                    'last_sync_at' => now(),
                    'last_sync_status' => 'success',
                    'last_error' => null,
                    'last_response' => $this->sanitizeProviderResponse($payload['raw'] ?? []),
                    'base_currency_code' => $baseCurrency,
                ]);

                $this->markBaseAndDefaultCurrencies($settings);
                Cache::forget('system_default_currency');
            });

            return [
                'status' => 'success',
                'message' => 'Currency sync completed successfully.',
                'rates_count' => count($payload['rates'] ?? []),
            ];
        } catch (\Throwable $exception) {
            $settings->update([
                'last_sync_at' => now(),
                'last_sync_status' => 'failed',
                'last_error' => $exception->getMessage(),
                'last_response' => ['error' => $exception->getMessage()],
            ]);

            return [
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function upsertSettings(array $data): CurrencyApiSetting
    {
        $settings = $this->settings() ?: new CurrencyApiSetting();
        $apiKey = $this->resolveApiKeyForStorage($settings, $data['api_key'] ?? null);

        $settings->fill([
            'provider' => $data['provider'],
            'driver' => $data['driver'] ?? $data['provider'],
            'base_currency_code' => strtoupper($data['base_currency_code']),
            'default_display_currency_code' => strtoupper($data['default_display_currency_code']),
            'sync_frequency' => $data['sync_frequency'],
            'auto_sync_enabled' => (bool) ($data['auto_sync_enabled'] ?? false),
            'is_active' => true,
            'credentials' => array_filter([
                'api_key' => $apiKey,
            ], fn ($value) => $value !== null && $value !== ''),
            'custom_rates' => $data['custom_rates'] ?? $settings->custom_rates,
        ]);

        $settings->save();
        $this->markBaseAndDefaultCurrencies($settings);
        Cache::forget('system_default_currency');

        return $settings;
    }

    public function rateFor(string $currencyCode): float
    {
        if (!Schema::hasTable('currencies')) {
            return 1.0;
        }

        $currency = Currency::where('code', strtoupper($currencyCode))->first();
        return (float) ($currency?->exchange_rate ?: 1);
    }

    protected function resolveDriver(string $driver): ExchangeRateProviderInterface
    {
        return match (strtolower($driver)) {
            'exchangerate-api', 'exchange_rate_api', 'exchange_rate_api_driver' => new ExchangeRateApiDriver(),
            'custom', 'custom_rate', 'customratedriver' => new CustomRateDriver(),
            default => new ManualRateDriver(),
        };
    }

    protected function resolveApiKeyForStorage(CurrencyApiSetting $settings, ?string $submittedApiKey): ?string
    {
        $submittedApiKey = is_string($submittedApiKey) ? trim($submittedApiKey) : null;

        if (!empty($submittedApiKey)) {
            return $submittedApiKey;
        }

        return $settings->getApiKey() ?: env('EXCHANGE_RATE_API_KEY');
    }

    protected function persistEnvironmentApiKey(CurrencyApiSetting $settings): void
    {
        if ($settings->getApiKey()) {
            return;
        }

        $envApiKey = env('EXCHANGE_RATE_API_KEY');
        if (!$envApiKey) {
            return;
        }

        $settings->forceFill([
            'credentials' => array_filter([
                'api_key' => trim((string) $envApiKey),
            ]),
        ])->save();
    }

    protected function legacySymbolPosition(): string
    {
        return match ((int) get_setting('symbol_format')) {
            2 => 'suffix',
            3 => 'prefix_spaced',
            4 => 'suffix_spaced',
            default => 'prefix',
        };
    }

    protected function formatMinimizedAmount(float $amount, int $decimals): string
    {
        if (abs($amount) >= 1000000000) {
            return number_format($amount / 1000000000, $decimals, '.', '').'B';
        }

        if (abs($amount) >= 1000000) {
            return number_format($amount / 1000000, $decimals, '.', '').'M';
        }

        return number_format($amount, $decimals, '.', '');
    }

    protected function sanitizeProviderResponse(array $payload): array
    {
        unset($payload['api_key'], $payload['credentials'], $payload['authorization']);
        return $payload;
    }

    protected function markBaseAndDefaultCurrencies(CurrencyApiSetting $settings): void
    {
        if (!Schema::hasTable('currencies')) {
            return;
        }

        Currency::query()->update([
            'is_base_currency' => false,
            'is_default_display_currency' => false,
        ]);

        Currency::query()
            ->where('code', strtoupper($settings->base_currency_code))
            ->update(['is_base_currency' => true]);

        Currency::query()
            ->where('code', strtoupper($settings->default_display_currency_code))
            ->update(['is_default_display_currency' => true]);

        $baseCurrency = Currency::where('code', strtoupper($settings->base_currency_code))->first();
        if ($baseCurrency && Schema::hasTable('business_settings')) {
            BusinessSetting::query()->updateOrInsert(
                ['type' => 'system_default_currency'],
                ['value' => $baseCurrency->id, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    protected function fallbackCurrency(): Currency
    {
        return new Currency([
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'exchange_rate' => 1,
            'decimal_places' => 2,
            'symbol_position' => 'prefix',
            'decimal_separator' => 'dot',
            'thousands_separator' => 'comma',
            'status' => true,
            'is_base_currency' => true,
            'is_default_display_currency' => true,
        ]);
    }
}
