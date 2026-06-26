<?php

namespace App\Services\Freight;

use App\Models\B2BContainerShipment;
use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuote;
use Illuminate\Support\Arr;

class GenericFreightDriver extends AbstractFreightForwarderDriver
{
    public function __construct(protected string $key)
    {
    }

    protected function driverKey(): string
    {
        return $this->key;
    }

    protected function credentialRules(): array
    {
        return [
            'api_key' => 'API key',
            'api_secret' => 'API secret',
        ];
    }

    public function validateCredentials(B2BFreightForwarder $forwarder): array
    {
        return $this->validateConfigured($forwarder);
    }

    public function testConnection(B2BFreightForwarder $forwarder): array
    {
        return $this->validateConfigured($forwarder);
    }

    public function requestQuote(B2BFreightForwarder $forwarder, array $payload): array
    {
        $configured = $this->validateConfigured($forwarder);
        if (!($configured['success'] ?? false)) {
            return $configured;
        }

        $request = [
            'origin_country' => Arr::get($payload, 'origin_country'),
            'destination_country' => Arr::get($payload, 'destination_country'),
            'freight_mode' => Arr::get($payload, 'freight_mode'),
            'service_type' => Arr::get($payload, 'service_type'),
            'container_type' => Arr::get($payload, 'container_type'),
            'container_count' => Arr::get($payload, 'container_count', 1),
            'cargo_weight' => Arr::get($payload, 'cargo_weight'),
            'cargo_volume' => Arr::get($payload, 'cargo_volume'),
            'goods_description' => Arr::get($payload, 'goods_description'),
        ];

        $result = $this->send($forwarder, 'POST', '/freight/quotes', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);

        return $this->success('quoted', $this->providerLabel() . ' freight quote received.', [
            'freight_cost' => Arr::get($body, 'freight_cost', Arr::get($body, 'amount')),
            'insurance_cost' => Arr::get($body, 'insurance_cost', 0),
            'customs_estimate' => Arr::get($body, 'customs_estimate', 0),
            'total_cost' => Arr::get($body, 'total_cost', Arr::get($body, 'amount')),
            'currency' => Arr::get($body, 'currency', 'USD'),
            'estimated_days' => Arr::get($body, 'estimated_days'),
            'service_type' => Arr::get($body, 'service_type', Arr::get($request, 'service_type')),
            'response_payload' => $body,
            'request_payload' => $request,
        ]);
    }

    public function createBooking(B2BFreightForwarder $forwarder, B2BFreightQuote $quote, array $payload = []): array
    {
        $configured = $this->validateConfigured($forwarder);
        if (!($configured['success'] ?? false)) {
            return $configured;
        }

        $request = array_merge($this->quotePayload($quote), $payload);
        $result = $this->send($forwarder, 'POST', '/freight/bookings', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);

        return $this->success('booked', $this->providerLabel() . ' booking created.', [
            'booking_number' => Arr::get($body, 'booking_number'),
            'bill_of_lading_number' => Arr::get($body, 'bill_of_lading_number'),
            'container_number' => Arr::get($body, 'container_number'),
            'seal_number' => Arr::get($body, 'seal_number'),
            'vessel_name' => Arr::get($body, 'vessel_name'),
            'voyage_number' => Arr::get($body, 'voyage_number'),
            'etd' => Arr::get($body, 'etd'),
            'eta' => Arr::get($body, 'eta'),
            'status' => Arr::get($body, 'status', 'booked'),
            'response_payload' => $body,
            'request_payload' => $request,
        ]);
    }

    public function trackContainer(B2BFreightForwarder $forwarder, B2BContainerShipment $shipment): array
    {
        $configured = $this->validateConfigured($forwarder);
        if (!($configured['success'] ?? false)) {
            return $configured;
        }

        $reference = $shipment->container_number ?: $shipment->bill_of_lading_number ?: $shipment->booking_number;
        if (blank($reference)) {
            return $this->failure('not_configured', 'Container number, bill of lading number, or booking number is required for freight tracking.');
        }

        $result = $this->send($forwarder, 'GET', '/freight/track/' . urlencode((string) $reference));
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);
        $events = collect(Arr::get($body, 'events', []))
            ->map(fn ($event) => [
                'event_type' => $this->normalizeEvent((string) Arr::get($event, 'event')),
                'event' => Arr::get($event, 'event'),
                'port_location' => Arr::get($event, 'port_location'),
                'vessel_name' => Arr::get($event, 'vessel_name'),
                'voyage_number' => Arr::get($event, 'voyage_number'),
                'description' => Arr::get($event, 'description'),
                'source_provider' => Arr::get($event, 'source_provider', $forwarder->name),
                'event_at' => $this->parseTimestamp(Arr::get($event, 'event_at')),
            ])
            ->filter(fn ($event) => filled($event['event_type']))
            ->values()
            ->all();

        $latest = $events ? end($events) : null;

        return $this->success('tracked', $this->providerLabel() . ' container tracking updated.', [
            'status' => $latest['event_type'] ?? Arr::get($body, 'status'),
            'eta' => Arr::get($body, 'eta'),
            'ata' => Arr::get($body, 'ata'),
            'events' => $events,
            'response_payload' => $body,
        ]);
    }

    public function webhook(B2BFreightForwarder $forwarder, array $payload, ?string $signature = null): array
    {
        $event = Arr::get($payload, 'event', Arr::get($payload, 'status'));

        return $this->success('processed', $this->providerLabel() . ' webhook normalized.', [
            'container_number' => Arr::get($payload, 'container_number'),
            'bill_of_lading_number' => Arr::get($payload, 'bill_of_lading_number'),
            'booking_number' => Arr::get($payload, 'booking_number'),
            'status' => $this->normalizeEvent((string) $event),
            'events' => [[
                'event_type' => $this->normalizeEvent((string) $event),
                'event' => $event,
                'port_location' => Arr::get($payload, 'port_location'),
                'vessel_name' => Arr::get($payload, 'vessel_name'),
                'voyage_number' => Arr::get($payload, 'voyage_number'),
                'description' => Arr::get($payload, 'description'),
                'source_provider' => $forwarder->name,
                'event_at' => $this->parseTimestamp(Arr::get($payload, 'event_at')) ?? now()->toIso8601String(),
            ]],
            'response_payload' => $payload,
        ]);
    }

    public function normalizeEvent(string $event, array $context = []): ?string
    {
        $normalized = strtolower(trim(str_replace(['-', ' '], '_', $event)));

        return match (true) {
            str_contains($normalized, 'gate_in') => 'gate_in',
            str_contains($normalized, 'loaded') && str_contains($normalized, 'vessel') => 'loaded_on_vessel',
            str_contains($normalized, 'depart') && str_contains($normalized, 'vessel') => 'vessel_departed',
            str_contains($normalized, 'transshipment') && str_contains($normalized, 'arriv') => 'transshipment_arrived',
            str_contains($normalized, 'transshipment') && str_contains($normalized, 'depart') => 'transshipment_departed',
            str_contains($normalized, 'arriv') && str_contains($normalized, 'vessel') => 'vessel_arrived',
            str_contains($normalized, 'customs') && str_contains($normalized, 'hold') => 'customs_hold',
            str_contains($normalized, 'customs') && str_contains($normalized, 'clear') => 'customs_cleared',
            str_contains($normalized, 'gate_out') => 'gate_out',
            str_contains($normalized, 'deliver') => 'delivered',
            str_contains($normalized, 'delay') => 'delayed',
            str_contains($normalized, 'exception') => 'exception',
            default => null,
        };
    }
}
