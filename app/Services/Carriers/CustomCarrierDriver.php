<?php

namespace App\Services\Carriers;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;

class CustomCarrierDriver implements CarrierTrackingInterface
{
    protected function genericUnsupported(string $message): array
    {
        return [
            'success' => false,
            'status' => 'unsupported',
            'message' => $message,
        ];
    }

    public function testConnection(B2BShippingProvider $provider): array
    {
        if (!$provider->credentialsConfigured()) {
            return $this->notConfigured('Custom carrier provider credentials are incomplete.');
        }

        return [
            'success' => true,
            'status' => 'connected',
            'message' => 'Custom carrier plugin configuration is present.',
        ];
    }

    public function trackShipment(B2BShippingProvider $provider, B2BShipment $shipment): array
    {
        if (!$provider->credentialsConfigured()) {
            return $this->notConfigured('Custom carrier provider credentials are incomplete.');
        }

        return $this->genericUnsupported('Custom carrier tracking requires a custom plugin implementation.');
    }

    public function createShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->genericUnsupported('Custom carrier shipment creation requires a custom plugin implementation.');
    }

    public function cancelShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->genericUnsupported('Custom carrier cancellation requires a custom plugin implementation.');
    }

    public function requestPickup(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->genericUnsupported('Custom carrier pickup requests require a custom plugin implementation.');
    }

    public function createLabel(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->genericUnsupported('Custom carrier labels require a custom plugin implementation.');
    }

    public function getShippingRates(B2BShippingProvider $provider, array $payload = []): array
    {
        return $this->genericUnsupported('Custom carrier live rates require a custom plugin implementation.');
    }

    public function validateCredentials(B2BShippingProvider $provider): array
    {
        return $provider->credentialsConfigured()
            ? ['success' => true, 'status' => 'validated', 'message' => 'Custom carrier configuration is present.']
            : $this->notConfigured('Custom carrier provider credentials are incomplete.');
    }

    public function webhook(B2BShippingProvider $provider, array $payload, ?string $signature = null): array
    {
        return $this->genericUnsupported('Custom carrier webhooks require a custom plugin implementation.');
    }

    public function normalizeStatus(string $status, array $context = []): ?string
    {
        return null;
    }

    protected function notConfigured(string $message): array
    {
        return [
            'success' => false,
            'status' => 'not_configured',
            'message' => $message,
        ];
    }

    protected function unsupported(string $message): array
    {
        return [
            'success' => false,
            'status' => 'unsupported',
            'message' => $message,
        ];
    }
}
