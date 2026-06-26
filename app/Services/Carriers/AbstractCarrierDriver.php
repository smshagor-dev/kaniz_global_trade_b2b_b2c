<?php

namespace App\Services\Carriers;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

abstract class AbstractCarrierDriver implements CarrierTrackingInterface
{
    abstract protected function driverKey(): string;

    abstract protected function credentialRules(): array;

    protected function baseUrl(B2BShippingProvider $provider): string
    {
        if (filled($provider->api_base_url)) {
            return rtrim((string) $provider->api_base_url, '/');
        }

        $config = config('b2b_carriers.carriers.' . $this->driverKey(), []);
        $key = $provider->isSandbox() ? 'sandbox_url' : 'production_url';

        return rtrim((string) ($config[$key] ?? ''), '/');
    }

    protected function http(B2BShippingProvider $provider, array $headers = []): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout((int) config('b2b_carriers.http.timeout', 20))
            ->connectTimeout((int) config('b2b_carriers.http.connect_timeout', 10))
            ->retry(
                (int) config('b2b_carriers.http.retry_times', 2),
                (int) config('b2b_carriers.http.retry_sleep_ms', 500),
                function (Throwable $exception, PendingRequest $request) {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && in_array($exception->response?->status(), [408, 409, 423, 425, 429, 500, 502, 503, 504], true));
                }
            )
            ->withHeaders(array_filter(array_merge([
                'User-Agent' => 'KanizGlobalTradeB2B/1.0',
            ], $headers), fn ($value) => filled($value)));
    }

    protected function send(
        B2BShippingProvider $provider,
        string $method,
        string $uri,
        array $options = []
    ): array {
        if ($this->circuitOpen($provider)) {
            return $this->failure('carrier_unavailable', 'Carrier circuit breaker is open. Please retry later.');
        }

        try {
            $request = $this->authorizeRequest($provider, $options['headers'] ?? []);
            $response = match (strtoupper($method)) {
                'GET' => $request->get($this->qualifyUri($provider, $uri), $options['query'] ?? []),
                'DELETE' => $request->delete($this->qualifyUri($provider, $uri), $options['json'] ?? []),
                'PUT' => $request->put($this->qualifyUri($provider, $uri), $options['json'] ?? []),
                'PATCH' => $request->patch($this->qualifyUri($provider, $uri), $options['json'] ?? []),
                default => $request->post($this->qualifyUri($provider, $uri), $options['json'] ?? []),
            };
        } catch (ConnectionException) {
            $this->registerFailure($provider);

            return $this->failure('carrier_unavailable', 'Carrier endpoint is temporarily unreachable.');
        } catch (Throwable $exception) {
            $this->registerFailure($provider);

            return $this->failure('carrier_unavailable', $exception->getMessage());
        }

        if ($response->successful()) {
            $this->clearCircuit($provider);

            return [
                'success' => true,
                'response' => $response,
                'body' => $this->decodeResponse($response),
            ];
        }

        $this->registerFailure($provider);

        return $this->mapHttpFailure($response);
    }

    protected function authorizeRequest(B2BShippingProvider $provider, array $headers = []): PendingRequest
    {
        return $this->http($provider, $headers);
    }

    protected function qualifyUri(B2BShippingProvider $provider, string $uri): string
    {
        if (Str::startsWith($uri, 'http://') || Str::startsWith($uri, 'https://')) {
            return $uri;
        }

        return $this->baseUrl($provider) . '/' . ltrim($uri, '/');
    }

    protected function decodeResponse(Response $response): array|string|null
    {
        $contentType = strtolower((string) $response->header('content-type'));
        if (str_contains($contentType, 'application/pdf') || str_contains($contentType, 'image/') || str_contains($contentType, 'application/zpl')) {
            return $response->body();
        }

        if (str_contains($contentType, 'json')) {
            return $response->json();
        }

        return $response->body();
    }

    protected function mapHttpFailure(Response $response): array
    {
        $status = match (true) {
            $response->status() === 401 || $response->status() === 403 => 'authentication_failed',
            $response->status() === 429 => 'rate_limited',
            $response->serverError() => 'carrier_unavailable',
            default => 'carrier_unavailable',
        };

        $body = $response->json();
        $message = Arr::get($body, 'message')
            ?? Arr::get($body, 'error.message')
            ?? Arr::get($body, 'errors.0.message')
            ?? Arr::get($body, 'notifications.0.message')
            ?? 'Carrier request failed.';

        return $this->failure($status, $message, [
            'http_status' => $response->status(),
            'response_body' => is_array($body) ? $body : $response->body(),
        ]);
    }

    protected function failure(string $status, string $message, array $extra = []): array
    {
        return array_merge([
            'success' => false,
            'status' => $status,
            'message' => $message,
        ], $extra);
    }

    protected function success(string $status, string $message, array $extra = []): array
    {
        return array_merge([
            'success' => true,
            'status' => $status,
            'message' => $message,
        ], $extra);
    }

    protected function unsupported(string $message): array
    {
        return $this->failure('unsupported', $message);
    }

    protected function notConfigured(string $message): array
    {
        return $this->failure('not_configured', $message);
    }

    protected function validateConfigured(B2BShippingProvider $provider): array
    {
        foreach ($this->credentialRules() as $field => $label) {
            if (!filled($provider->{$field})) {
                return $this->notConfigured(sprintf('%s credentials are missing or incomplete.', $this->providerLabel()));
            }
        }

        return $this->success('validated', 'Credentials are configured.');
    }

    protected function providerLabel(): string
    {
        return (string) (config('b2b_carriers.carriers.' . $this->driverKey() . '.name') ?? strtoupper($this->driverKey()));
    }

    protected function circuitCacheKey(B2BShippingProvider $provider, string $suffix): string
    {
        return sprintf('b2b-carrier:%s:%s:%s', $this->driverKey(), $provider->id, $suffix);
    }

    protected function circuitOpen(B2BShippingProvider $provider): bool
    {
        $until = Cache::get($this->circuitCacheKey($provider, 'open-until'));

        return $until instanceof Carbon ? $until->isFuture() : false;
    }

    protected function clearCircuit(B2BShippingProvider $provider): void
    {
        Cache::forget($this->circuitCacheKey($provider, 'failures'));
        Cache::forget($this->circuitCacheKey($provider, 'open-until'));
    }

    protected function registerFailure(B2BShippingProvider $provider): void
    {
        $key = $this->circuitCacheKey($provider, 'failures');
        $failures = (int) Cache::increment($key);
        Cache::put($key, $failures, now()->addMinutes(30));

        if ($failures >= (int) config('b2b_carriers.http.circuit_threshold', 5)) {
            Cache::put(
                $this->circuitCacheKey($provider, 'open-until'),
                now()->addSeconds((int) config('b2b_carriers.http.circuit_cooldown_seconds', 300)),
                now()->addSeconds((int) config('b2b_carriers.http.circuit_cooldown_seconds', 300))
            );
        }
    }

    protected function parseTimestamp(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }

    protected function packagePayload(B2BShipment $shipment, array $payload = []): array
    {
        return array_merge([
            'weight' => $shipment->total_weight,
            'length' => $shipment->package_length,
            'width' => $shipment->package_width,
            'height' => $shipment->package_height,
            'declared_value' => $shipment->declared_value,
            'insurance_amount' => $shipment->insurance_amount,
            'currency' => $shipment->currency ?: 'USD',
            'service_type' => $shipment->service_type,
            'priority' => $shipment->delivery_priority,
        ], $payload['package'] ?? []);
    }

    protected function companyAddress(array $payload, string $key, ?array $fallback = null): array
    {
        return array_filter(array_merge($fallback ?? [], $payload[$key] ?? []), fn ($value) => $value !== null && $value !== '');
    }
}
