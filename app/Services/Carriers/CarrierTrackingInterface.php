<?php

namespace App\Services\Carriers;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;

interface CarrierTrackingInterface
{
    public function trackShipment(B2BShippingProvider $provider, B2BShipment $shipment): array;

    public function createShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array;

    public function cancelShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array;

    public function requestPickup(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array;

    public function createLabel(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array;

    public function getShippingRates(B2BShippingProvider $provider, array $payload = []): array;

    public function validateCredentials(B2BShippingProvider $provider): array;

    public function testConnection(B2BShippingProvider $provider): array;

    public function webhook(B2BShippingProvider $provider, array $payload, ?string $signature = null): array;

    public function normalizeStatus(string $status, array $context = []): ?string;
}
