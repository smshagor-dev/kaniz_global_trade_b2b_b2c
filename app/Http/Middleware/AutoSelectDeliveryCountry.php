<?php

namespace App\Http\Middleware;

use App\Models\Country;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class AutoSelectDeliveryCountry
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('delivery_country_source') === 'manual'
            && $request->session()->has('delivery_country_id')) {
            return $next($request);
        }

        $country = $this->resolveCountryFromRequest($request);
        if ($country) {
            $request->session()->put([
                'delivery_country_id' => $country->id,
                'delivery_country_code' => strtoupper((string) $country->code),
                'delivery_country_name' => $country->name,
                'delivery_country_source' => 'auto',
            ]);
        }

        return $next($request);
    }

    protected function resolveCountryFromRequest(Request $request): ?Country
    {
        $countryCode = $this->resolveCountryCode($request);
        if (!$countryCode) {
            return selected_delivery_country();
        }

        return Country::where('code', strtoupper($countryCode))->first()
            ?: selected_delivery_country();
    }

    protected function resolveCountryCode(Request $request): ?string
    {
        foreach (['CF-IPCountry', 'X-Country-Code'] as $header) {
            $value = strtoupper(trim((string) $request->header($header, '')));
            if ($this->isUsableCountryCode($value)) {
                return $value;
            }
        }

        $ip = (string) $request->ip();
        if ($ip === '' || in_array($ip, ['127.0.0.1', '::1'], true) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return 'US';
        }

        return Cache::remember('ip-country-code:' . $ip, now()->addHours(12), function () use ($ip) {
            try {
                $response = Http::timeout(4)
                    ->acceptJson()
                    ->get("https://ipwho.is/{$ip}");

                if (!$response->successful()) {
                    return null;
                }

                $payload = $response->json();
                $code = strtoupper((string) ($payload['country_code'] ?? ''));

                return $this->isUsableCountryCode($code) ? $code : null;
            } catch (\Throwable $exception) {
                return null;
            }
        });
    }

    protected function isUsableCountryCode(?string $code): bool
    {
        return is_string($code)
            && strlen($code) === 2
            && !in_array($code, ['XX', 'T1'], true);
    }
}
