<?php

namespace App\Services\Freight;

use App\Models\B2BContainerShipment;
use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuote;
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

abstract class AbstractFreightForwarderDriver implements FreightForwarderInterface
{
    abstract protected function driverKey(): string;

    protected function providerLabel(): string
    {
        return (string) (config('b2b_freight.providers.' . $this->driverKey() . '.name') ?? strtoupper($this->driverKey()));
    }

    protected function credentialRules(): array
    {
        return [];
    }

    protected function baseUrl(B2BFreightForwarder $forwarder): string
    {
        if (filled($forwarder->api_base_url)) {
            return rtrim((string) $forwarder->api_base_url, '/');
        }

        $config = config('b2b_freight.providers.' . $this->driverKey(), []);
        $key = $forwarder->isSandbox() ? 'sandbox_url' : 'production_url';

        return rtrim((string) ($config[$key] ?? ''), '/');
    }

    protected function http(B2BFreightForwarder $forwarder, array $headers = []): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout((int) config('b2b_freight.http.timeout', 30))
            ->connectTimeout((int) config('b2b_freight.http.connect_timeout', 10))
            ->retry(
                (int) config('b2b_freight.http.retry_times', 2),
                (int) config('b2b_freight.http.retry_sleep_ms', 500),
                function (Throwable $exception) {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && in_array($exception->response?->status(), [408, 409, 423, 425, 429, 500, 502, 503, 504], true));
                }
            )
            ->withHeaders(array_filter(array_merge([
                'User-Agent' => 'KanizGlobalTradeB2B-Freight/1.0',
            ], $headers), fn ($value) => filled($value)));
    }

    protected function authorizeRequest(B2BFreightForwarder $forwarder, array $headers = []): PendingRequest
    {
        return $this->http($forwarder, $headers);
    }

    protected function send(B2BFreightForwarder $forwarder, string $method, string $uri, array $options = []): array
    {
        if ($this->circuitOpen($forwarder)) {
            return $this->failure('carrier_unavailable', 'Freight forwarder circuit breaker is open. Please retry later.');
        }

        try {
            $request = $this->authorizeRequest($forwarder, $options['headers'] ?? []);
            $response = match (strtoupper($method)) {
                'GET' => $request->get($this->qualifyUri($forwarder, $uri), $options['query'] ?? []),
                'DELETE' => $request->delete($this->qualifyUri($forwarder, $uri), $options['json'] ?? []),
                'PUT' => $request->put($this->qualifyUri($forwarder, $uri), $options['json'] ?? []),
                'PATCH' => $request->patch($this->qualifyUri($forwarder, $uri), $options['json'] ?? []),
                default => $request->post($this->qualifyUri($forwarder, $uri), $options['json'] ?? []),
            };
        } catch (ConnectionException) {
            $this->registerFailure($forwarder);

            return $this->failure('carrier_unavailable', 'Freight forwarder endpoint is temporarily unreachable.');
        } catch (Throwable $exception) {
            $this->registerFailure($forwarder);

            return $this->failure('carrier_unavailable', $exception->getMessage());
        }

        if ($response->successful()) {
            $this->clearCircuit($forwarder);

            return [
                'success' => true,
                'response' => $response,
                'body' => $this->decodeResponse($response),
            ];
        }

        $this->registerFailure($forwarder);

        return $this->mapHttpFailure($response);
    }

    protected function decodeResponse(Response $response): mixed
    {
        $contentType = strtolower((string) $response->header('content-type'));

        return str_contains($contentType, 'json') ? $response->json() : $response->body();
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
            ?? 'Freight forwarder request failed.';

        return $this->failure($status, $message, [
            'http_status' => $response->status(),
            'response_body' => is_array($body) ? $body : $response->body(),
        ]);
    }

    protected function qualifyUri(B2BFreightForwarder $forwarder, string $uri): string
    {
        if (Str::startsWith($uri, 'http://') || Str::startsWith($uri, 'https://')) {
            return $uri;
        }

        return $this->baseUrl($forwarder) . '/' . ltrim($uri, '/');
    }

    protected function validateConfigured(B2BFreightForwarder $forwarder): array
    {
        foreach ($this->credentialRules() as $field => $label) {
            if (!filled($forwarder->{$field})) {
                return $this->failure('not_configured', sprintf('%s credentials are missing or incomplete.', $this->providerLabel()));
            }
        }

        return $this->success('validated', 'Credentials are configured.');
    }

    protected function success(string $status, string $message, array $extra = []): array
    {
        return array_merge([
            'success' => true,
            'status' => $status,
            'message' => $message,
        ], $extra);
    }

    protected function failure(string $status, string $message, array $extra = []): array
    {
        return array_merge([
            'success' => false,
            'status' => $status,
            'message' => $message,
        ], $extra);
    }

    protected function unsupported(string $message): array
    {
        return $this->failure('unsupported', $message);
    }

    protected function circuitCacheKey(B2BFreightForwarder $forwarder, string $suffix): string
    {
        return sprintf('b2b-freight:%s:%s:%s', $this->driverKey(), $forwarder->id, $suffix);
    }

    protected function circuitOpen(B2BFreightForwarder $forwarder): bool
    {
        $until = Cache::get($this->circuitCacheKey($forwarder, 'open-until'));

        return $until instanceof Carbon ? $until->isFuture() : false;
    }

    protected function clearCircuit(B2BFreightForwarder $forwarder): void
    {
        Cache::forget($this->circuitCacheKey($forwarder, 'failures'));
        Cache::forget($this->circuitCacheKey($forwarder, 'open-until'));
    }

    protected function registerFailure(B2BFreightForwarder $forwarder): void
    {
        $key = $this->circuitCacheKey($forwarder, 'failures');
        $failures = (int) Cache::increment($key);
        Cache::put($key, $failures, now()->addMinutes(30));

        if ($failures >= (int) config('b2b_freight.http.circuit_threshold', 5)) {
            $cooldown = (int) config('b2b_freight.http.circuit_cooldown_seconds', 300);
            Cache::put($this->circuitCacheKey($forwarder, 'open-until'), now()->addSeconds($cooldown), now()->addSeconds($cooldown));
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

    protected function quotePayload(B2BFreightQuote $quote): array
    {
        return [
            'origin_country' => $quote->origin_country,
            'destination_country' => $quote->destination_country,
            'freight_mode' => $quote->freight_mode,
            'service_type' => $quote->service_type,
            'container_type' => $quote->container_type,
            'container_count' => $quote->container_count,
            'cargo_weight' => $quote->cargo_weight,
            'cargo_volume' => $quote->cargo_volume,
            'hs_code' => $quote->hs_code,
            'goods_description' => $quote->goods_description,
        ];
    }
}
