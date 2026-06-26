<?php

namespace App\Services\Carriers;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;

class UnsupportedCarrierDriver extends AbstractCarrierDriver
{
    protected function driverKey(): string
    {
        return 'unsupported';
    }

    protected function credentialRules(): array
    {
        return [];
    }

    public function trackShipment(B2BShippingProvider $provider, B2BShipment $shipment): array
    {
        return $this->unsupported(sprintf('%s integration is not available yet.', $provider->name));
    }

    public function createShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->unsupported(sprintf('%s shipment creation is not available yet.', $provider->name));
    }

    public function cancelShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->unsupported(sprintf('%s shipment cancellation is not available yet.', $provider->name));
    }

    public function requestPickup(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->unsupported(sprintf('%s pickup scheduling is not available yet.', $provider->name));
    }

    public function createLabel(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->unsupported(sprintf('%s label generation is not available yet.', $provider->name));
    }

    public function getShippingRates(B2BShippingProvider $provider, array $payload = []): array
    {
        return $this->unsupported(sprintf('%s live rates are not available yet.', $provider->name));
    }

    public function validateCredentials(B2BShippingProvider $provider): array
    {
        return $this->unsupported(sprintf('%s credentials cannot be validated yet.', $provider->name));
    }

    public function testConnection(B2BShippingProvider $provider): array
    {
        return $this->unsupported(sprintf('%s connection testing is not available yet.', $provider->name));
    }

    public function webhook(B2BShippingProvider $provider, array $payload, ?string $signature = null): array
    {
        return $this->unsupported(sprintf('%s webhooks are not available yet.', $provider->name));
    }

    public function normalizeStatus(string $status, array $context = []): ?string
    {
        return null;
    }
}
