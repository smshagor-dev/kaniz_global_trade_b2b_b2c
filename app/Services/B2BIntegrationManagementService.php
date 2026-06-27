<?php

namespace App\Services;

use App\Models\B2BFreightForwarder;
use App\Models\B2BInsuranceProvider;
use App\Models\B2BShippingProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class B2BIntegrationManagementService
{
    public const EVENT_OPTIONS = [
        'shipment_created',
        'pickup_scheduled',
        'pickup_completed',
        'in_transit',
        'customs_hold',
        'customs_cleared',
        'out_for_delivery',
        'delivered',
        'returned',
        'exception',
    ];

    public function ensureWebhookSecret(Model $model): string
    {
        if (filled($model->webhook_secret)) {
            return (string) $model->webhook_secret;
        }

        $secret = 'whsec_' . Str::random(40);
        $model->update($model->filterPersistable(['webhook_secret' => $secret]));

        return $secret;
    }

    public function regenerateWebhookSecret(Model $model): string
    {
        $secret = 'whsec_' . Str::random(40);
        $model->update($model->filterPersistable([
            'webhook_secret' => $secret,
            'webhook_verified_at' => null,
        ]));

        return $secret;
    }

    public function buildPayload(Model $model, string $type): array
    {
        $configured = $this->credentialsConfigured($model);
        $slug = $this->routeIdentifier($model);
        $docs = $this->documentation($model, $type);

        return [
            'configured' => $configured,
            'connection_status' => $this->connectionStatus($model),
            'environment_label' => $this->environmentLabel($model),
            'urls' => $configured ? $this->urls($model, $type, $slug) : [],
            'url_labels' => $this->urlLabels($type),
            'events' => $this->enabledEvents($model),
            'docs' => $docs,
            'health' => $this->healthMetrics($model),
            'secret_preview' => $model->webhook_secret ? Str::mask((string) $model->webhook_secret, '*', 6) : null,
            'supports_secret' => true,
        ];
    }

    public function recordConnectionResult(Model $model, array $result, int $responseTimeMs, ?int $httpStatus = null): void
    {
        $successful = (bool) ($result['success'] ?? false);
        $previousSuccesses = (int) ($model->successful_requests ?? 0);
        $previousAvg = (int) ($model->average_response_time_ms ?? 0);
        $newSuccessCount = $successful ? $previousSuccesses + 1 : $previousSuccesses;
        $newAverage = $successful
            ? (int) round((($previousAvg * $previousSuccesses) + $responseTimeMs) / max(1, $newSuccessCount))
            : $previousAvg;

        $updates = [
            'last_api_status' => $successful ? 'connected' : ($result['status'] ?? 'failed'),
            'last_api_http_status' => $httpStatus,
            'last_api_response_time_ms' => $responseTimeMs,
            'last_api_called_at' => now(),
            'last_api_success_at' => $successful ? now() : $model->last_api_success_at,
            'last_api_failure_at' => $successful ? $model->last_api_failure_at : now(),
            'successful_requests' => $newSuccessCount,
            'failed_requests' => $successful ? (int) ($model->failed_requests ?? 0) : ((int) ($model->failed_requests ?? 0) + 1),
            'average_response_time_ms' => $newAverage,
        ];

        $model->update($model->filterPersistable($updates));
    }

    public function recordWebhookReceived(Model $model, bool $verified = false): void
    {
        $model->update($model->filterPersistable([
            'last_webhook_received_at' => now(),
            'webhook_verified_at' => $verified ? now() : $model->webhook_verified_at,
        ]));
    }

    public function touchLastSync(Model $model): void
    {
        $model->update($model->filterPersistable(['last_sync_at' => now()]));
    }

    public function updateEvents(Model $model, array $events): void
    {
        $normalized = array_values(array_intersect(self::EVENT_OPTIONS, $events));
        $model->update($model->filterPersistable(['integration_events' => $normalized]));
    }

    public function sampleWebhookPayload(Model $model, string $type, ?string $channel = null): array
    {
        if ($type === 'insurance') {
            return [
                'event' => $channel ?: 'policy.updated',
                'provider' => $this->routeIdentifier($model),
                'policy_number' => 'IP-SAMPLE-001',
                'claim_number' => 'IC-SAMPLE-001',
                'status' => 'processed',
                'occurred_at' => now()->toIso8601String(),
                'description' => 'Sample insurance webhook event.',
            ];
        }

        $channelStatus = match ($channel) {
            'tracking' => $type === 'freight' ? 'Vessel Arrived' : 'In Transit',
            'pickup' => 'Pickup Scheduled',
            default => $type === 'freight' ? 'Delivered' : 'Delivered',
        };

        if ($type === 'freight') {
            return [
                'container_number' => 'SIMU1234567',
                'bill_of_lading_number' => 'BL-SAMPLE-001',
                'booking_number' => 'BK-SAMPLE-001',
                'event' => $channelStatus,
                'port_location' => 'Sample Port',
                'description' => 'Sample freight webhook event.',
                'event_at' => now()->toIso8601String(),
            ];
        }

        return [
            'trackingNumber' => 'TRACK-SAMPLE-001',
            'status' => [
                'description' => $channelStatus,
                'statusCode' => Str::upper(str_replace(' ', '_', $channelStatus)),
            ],
            'description' => 'Sample carrier webhook event.',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function connectionStatus(Model $model): string
    {
        if (!$this->credentialsConfigured($model)) {
            return 'not_configured';
        }

        if (($model->last_api_status ?? null) === 'authentication_failed') {
            return 'authentication_failed';
        }

        if (($model->last_api_status ?? null) === 'invalid_credentials') {
            return 'invalid_credentials';
        }

        if (blank($model->webhook_verified_at) && filled($model->webhook_secret)) {
            return 'webhook_not_verified';
        }

        if (($model->last_api_status ?? null) === 'connected') {
            return 'connected';
        }

        return $this->isSandbox($model) ? 'sandbox' : 'production';
    }

    public function healthMetrics(Model $model): array
    {
        $successes = (int) ($model->successful_requests ?? 0);
        $failures = (int) ($model->failed_requests ?? 0);
        $total = $successes + $failures;
        $successRate = $total > 0 ? round(($successes / $total) * 100, 2) : 0.0;

        return [
            'last_api_call' => $model->last_api_called_at,
            'last_webhook_received' => $model->last_webhook_received_at,
            'api_uptime' => $successRate,
            'failed_requests' => $failures,
            'success_rate' => $successRate,
            'average_response_time_ms' => (int) ($model->average_response_time_ms ?? 0),
            'last_successful_connection' => $model->last_api_success_at,
            'last_failed_connection' => $model->last_api_failure_at,
            'last_sync_time' => $model->last_sync_at,
            'http_status' => $model->last_api_http_status,
            'response_time_ms' => $model->last_api_response_time_ms,
        ];
    }

    public function documentation(Model $model, string $type): array
    {
        $driver = $type === 'freight'
            ? ($model->driver ?: 'custom')
            : (($model->api_driver ?: 'manual'));

        return [
            'required_headers' => ['Content-Type: application/json', 'Accept: application/json'],
            'signature_header' => 'X-Webhook-Signature',
            'authentication_method' => $this->authenticationMethod($model, $type),
            'sample_payload' => $this->sampleWebhookPayload($model, $type),
            'sample_response' => ['message' => 'Webhook processed successfully.'],
            'required_events' => $this->enabledEvents($model),
            'provider_driver' => $driver,
        ];
    }

    public function urls(Model $model, string $type, string|int $identifier): array
    {
        if ($type === 'insurance') {
            return [
                'webhook_url' => route('b2b.insurance-webhooks.handle', ['provider' => $identifier]),
                'callback_url' => route('admin.b2b.insurance.dashboard'),
                'policy_webhook_url' => route('b2b.insurance-webhooks.handle', ['provider' => $identifier]),
                'claim_webhook_url' => route('b2b.insurance-webhooks.handle', ['provider' => $identifier]),
                'test_connection_url' => route('admin.b2b.insurance.providers.test', ['providerId' => $model->id]),
            ];
        }

        if ($type === 'freight') {
            return [
                'webhook_url' => route('b2b.freight-webhooks.handle', ['forwarder' => $identifier]),
                'callback_url' => route('b2b.container-tracking.track'),
                'tracking_webhook_url' => route('b2b.freight-webhooks.tracking', ['forwarder' => $identifier]),
                'shipment_webhook_url' => route('b2b.freight-webhooks.shipment', ['forwarder' => $identifier]),
                'pickup_webhook_url' => route('b2b.freight-webhooks.pickup', ['forwarder' => $identifier]),
                'test_connection_url' => route('admin.b2b.freight-forwarders.test', ['id' => $model->id]),
            ];
        }

        return [
            'webhook_url' => route('b2b.carrier-webhooks.handle', ['provider' => $identifier]),
            'callback_url' => route('b2b.shipments.index'),
            'tracking_webhook_url' => route('b2b.carrier-webhooks.tracking', ['provider' => $identifier]),
            'shipment_webhook_url' => route('b2b.carrier-webhooks.shipment', ['provider' => $identifier]),
            'pickup_webhook_url' => route('b2b.carrier-webhooks.pickup', ['provider' => $identifier]),
            'test_connection_url' => route('admin.b2b.shipping-providers.test', ['id' => $model->id]),
        ];
    }

    public function credentialsConfigured(Model $model): bool
    {
        if ($model instanceof B2BInsuranceProvider) {
            return $model->credentialsConfigured();
        }

        if ($model instanceof B2BShippingProvider) {
            return $model->credentialsConfigured();
        }

        if ($model instanceof B2BFreightForwarder) {
            return $model->credentialsConfigured();
        }

        return false;
    }

    public function enabledEvents(Model $model): array
    {
        $events = $model->integration_events;
        if (is_array($events) && $events !== []) {
            return $events;
        }

        return self::EVENT_OPTIONS;
    }

    public function environmentLabel(Model $model): string
    {
        return $this->isSandbox($model) ? 'sandbox' : 'production';
    }

    protected function authenticationMethod(Model $model, string $type): string
    {
        if ($type === 'insurance') {
            if (filled($model->username) && filled($model->password)) {
                return 'Basic Auth';
            }

            if (filled($model->api_key) && filled($model->api_secret)) {
                return 'API Key / Secret';
            }

            return filled($model->api_key) ? 'Bearer Token / API Key' : 'Manual';
        }

        if ($type === 'freight') {
            return filled($model->oauth_token) ? 'OAuth Token' : 'API Key / Secret';
        }

        return match ($model->api_driver) {
            'dhl', 'ups' => 'Basic Auth + Account Number',
            'fedex' => 'OAuth Client Credentials',
            'aramex' => 'Username / Password + API Credentials',
            default => filled($model->api_key) ? 'API Key / Secret' : 'Manual',
        };
    }

    protected function routeIdentifier(Model $model): string|int
    {
        return $model->id;
    }

    protected function isSandbox(Model $model): bool
    {
        return method_exists($model, 'isSandbox') ? $model->isSandbox() : false;
    }

    protected function urlLabels(string $type): array
    {
        if ($type === 'insurance') {
            return [
                'webhook_url' => 'Webhook URL',
                'callback_url' => 'Dashboard URL',
                'policy_webhook_url' => 'Policy Webhook URL',
                'claim_webhook_url' => 'Claim Webhook URL',
                'test_connection_url' => 'Test Connection URL',
            ];
        }

        return [
            'webhook_url' => 'Webhook URL',
            'callback_url' => 'Callback URL',
            'tracking_webhook_url' => 'Tracking Webhook URL',
            'shipment_webhook_url' => 'Shipment Webhook URL',
            'pickup_webhook_url' => 'Pickup Webhook URL',
            'test_connection_url' => 'Test Connection URL',
        ];
    }
}
