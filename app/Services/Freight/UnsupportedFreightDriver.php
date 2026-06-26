<?php

namespace App\Services\Freight;

use App\Models\B2BContainerShipment;
use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuote;

class UnsupportedFreightDriver extends AbstractFreightForwarderDriver
{
    public function __construct(protected ?string $name = null)
    {
    }

    protected function driverKey(): string
    {
        return 'unsupported';
    }

    protected function providerName(B2BFreightForwarder $forwarder): string
    {
        return $this->name ?: $forwarder->name;
    }

    public function requestQuote(B2BFreightForwarder $forwarder, array $payload): array
    {
        return $this->unsupported(sprintf('%s freight quote API is not available.', $this->providerName($forwarder)));
    }

    public function createBooking(B2BFreightForwarder $forwarder, B2BFreightQuote $quote, array $payload = []): array
    {
        return $this->unsupported(sprintf('%s booking API is not available.', $this->providerName($forwarder)));
    }

    public function trackContainer(B2BFreightForwarder $forwarder, B2BContainerShipment $shipment): array
    {
        return $this->unsupported(sprintf('%s container tracking API is not available.', $this->providerName($forwarder)));
    }

    public function validateCredentials(B2BFreightForwarder $forwarder): array
    {
        return $this->unsupported(sprintf('%s credential validation is not available.', $this->providerName($forwarder)));
    }

    public function testConnection(B2BFreightForwarder $forwarder): array
    {
        return $this->unsupported(sprintf('%s connection testing is not available.', $this->providerName($forwarder)));
    }

    public function webhook(B2BFreightForwarder $forwarder, array $payload, ?string $signature = null): array
    {
        return $this->unsupported(sprintf('%s webhooks are not available.', $this->providerName($forwarder)));
    }

    public function normalizeEvent(string $event, array $context = []): ?string
    {
        return null;
    }
}
