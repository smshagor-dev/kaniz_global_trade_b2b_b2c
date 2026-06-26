<?php

namespace App\Services\Freight;

use App\Models\B2BContainerShipment;
use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuote;

class CustomFreightDriver extends AbstractFreightForwarderDriver
{
    protected function driverKey(): string
    {
        return 'custom';
    }

    protected function credentialRules(): array
    {
        return [
            'api_key' => 'API key',
            'api_secret' => 'API secret',
        ];
    }

    public function requestQuote(B2BFreightForwarder $forwarder, array $payload): array
    {
        return $this->unsupported('Custom freight forwarder quote integration requires a custom plugin implementation.');
    }

    public function createBooking(B2BFreightForwarder $forwarder, B2BFreightQuote $quote, array $payload = []): array
    {
        return $this->unsupported('Custom freight forwarder booking integration requires a custom plugin implementation.');
    }

    public function trackContainer(B2BFreightForwarder $forwarder, B2BContainerShipment $shipment): array
    {
        return $this->unsupported('Custom freight forwarder tracking integration requires a custom plugin implementation.');
    }

    public function validateCredentials(B2BFreightForwarder $forwarder): array
    {
        return $this->validateConfigured($forwarder);
    }

    public function testConnection(B2BFreightForwarder $forwarder): array
    {
        $result = $this->validateConfigured($forwarder);

        return ($result['success'] ?? false)
            ? $this->success('connected', 'Custom freight forwarder configuration is present.')
            : $result;
    }

    public function webhook(B2BFreightForwarder $forwarder, array $payload, ?string $signature = null): array
    {
        return $this->unsupported('Custom freight forwarder webhooks require a custom plugin implementation.');
    }

    public function normalizeEvent(string $event, array $context = []): ?string
    {
        return null;
    }
}
