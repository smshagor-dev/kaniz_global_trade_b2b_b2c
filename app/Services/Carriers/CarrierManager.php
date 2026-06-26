<?php

namespace App\Services\Carriers;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use InvalidArgumentException;

class CarrierManager
{
    public function testConnection(B2BShippingProvider $provider): array
    {
        return $this->driver($provider)->testConnection($provider);
    }

    public function validateCredentials(B2BShippingProvider $provider): array
    {
        return $this->driver($provider)->validateCredentials($provider);
    }

    public function trackShipment(B2BShippingProvider $provider, B2BShipment $shipment): array
    {
        return $this->driver($provider)->trackShipment($provider, $shipment);
    }

    public function createShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->driver($provider)->createShipment($provider, $shipment, $payload);
    }

    public function cancelShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->driver($provider)->cancelShipment($provider, $shipment, $payload);
    }

    public function requestPickup(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->driver($provider)->requestPickup($provider, $shipment, $payload);
    }

    public function createLabel(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        return $this->driver($provider)->createLabel($provider, $shipment, $payload);
    }

    public function getShippingRates(B2BShippingProvider $provider, array $payload = []): array
    {
        return $this->driver($provider)->getShippingRates($provider, $payload);
    }

    public function webhook(B2BShippingProvider $provider, array $payload, ?string $signature = null): array
    {
        return $this->driver($provider)->webhook($provider, $payload, $signature);
    }

    public function driver(B2BShippingProvider $provider): CarrierTrackingInterface
    {
        if ($provider->provider_type === 'manual') {
            return app(ManualCarrierDriver::class);
        }

        $driverClass = config('b2b_carriers.drivers.' . $provider->api_driver);
        if (!$driverClass || !class_exists($driverClass)) {
            throw new InvalidArgumentException('Unsupported carrier driver: ' . ($provider->api_driver ?: 'undefined'));
        }

        return app($driverClass);
    }
}
