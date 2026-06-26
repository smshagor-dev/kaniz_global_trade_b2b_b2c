<?php

namespace App\Services\Freight;

use App\Models\B2BContainerShipment;
use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuote;

interface FreightForwarderInterface
{
    public function requestQuote(B2BFreightForwarder $forwarder, array $payload): array;

    public function createBooking(B2BFreightForwarder $forwarder, B2BFreightQuote $quote, array $payload = []): array;

    public function trackContainer(B2BFreightForwarder $forwarder, B2BContainerShipment $shipment): array;

    public function validateCredentials(B2BFreightForwarder $forwarder): array;

    public function testConnection(B2BFreightForwarder $forwarder): array;

    public function webhook(B2BFreightForwarder $forwarder, array $payload, ?string $signature = null): array;

    public function normalizeEvent(string $event, array $context = []): ?string;
}
