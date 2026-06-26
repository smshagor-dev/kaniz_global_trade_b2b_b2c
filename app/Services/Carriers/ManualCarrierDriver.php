<?php

namespace App\Services\Carriers;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;

class ManualCarrierDriver implements CarrierTrackingInterface
{
    public function validateCredentials(B2BShippingProvider $provider): array
    {
        return [
            'success' => true,
            'status' => 'manual',
            'message' => 'Manual provider does not require credentials.',
        ];
    }

    public function testConnection(B2BShippingProvider $provider): array
    {
        return [
            'success' => true,
            'status' => 'manual',
            'message' => 'Manual provider does not require API connectivity.',
        ];
    }

    public function trackShipment(B2BShippingProvider $provider, B2BShipment $shipment): array
    {
        return [
            'success' => true,
            'status' => 'manual',
            'message' => 'Manual tracking keeps the existing shipment timeline unchanged.',
            'carrier_status' => $shipment->carrier_status ?: $shipment->status,
            'normalized_status' => $shipment->status,
            'tracking_url' => $shipment->tracking_url,
            'events' => [],
        ];
    }

    public function createShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return [
            'success' => true,
            'status' => 'manual',
            'message' => 'Manual provider uses supplier-entered shipment details.',
            'tracking_number' => $shipment->tracking_number,
            'carrier_reference' => $shipment->carrier_reference,
        ];
    }

    public function cancelShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return [
            'success' => true,
            'status' => 'manual',
            'message' => 'Manual shipment was marked for internal cancellation only.',
        ];
    }

    public function requestPickup(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return [
            'success' => true,
            'status' => 'manual',
            'message' => 'Manual provider does not schedule API pickups.',
        ];
    }

    public function createLabel(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return [
            'success' => false,
            'status' => 'unsupported',
            'message' => 'Manual provider does not generate labels.',
        ];
    }

    public function getShippingRates(B2BShippingProvider $provider, array $payload = []): array
    {
        return [
            'success' => false,
            'status' => 'unsupported',
            'message' => 'Manual provider does not return live rates.',
        ];
    }

    public function webhook(B2BShippingProvider $provider, array $payload, ?string $signature = null): array
    {
        return [
            'success' => false,
            'status' => 'manual',
            'message' => 'Manual providers do not accept carrier webhooks.',
        ];
    }

    public function normalizeStatus(string $status, array $context = []): ?string
    {
        return $context['fallback_status'] ?? null;
    }
}
