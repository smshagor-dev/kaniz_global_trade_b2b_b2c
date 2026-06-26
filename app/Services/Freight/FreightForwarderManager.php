<?php

namespace App\Services\Freight;

use App\Models\B2BContainerShipment;
use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuote;
use InvalidArgumentException;

class FreightForwarderManager
{
    public function validateCredentials(B2BFreightForwarder $forwarder): array
    {
        return $this->driver($forwarder)->validateCredentials($forwarder);
    }

    public function testConnection(B2BFreightForwarder $forwarder): array
    {
        return $this->driver($forwarder)->testConnection($forwarder);
    }

    public function requestQuote(B2BFreightForwarder $forwarder, array $payload): array
    {
        return $this->driver($forwarder)->requestQuote($forwarder, $payload);
    }

    public function createBooking(B2BFreightForwarder $forwarder, B2BFreightQuote $quote, array $payload = []): array
    {
        return $this->driver($forwarder)->createBooking($forwarder, $quote, $payload);
    }

    public function trackContainer(B2BFreightForwarder $forwarder, B2BContainerShipment $shipment): array
    {
        return $this->driver($forwarder)->trackContainer($forwarder, $shipment);
    }

    public function webhook(B2BFreightForwarder $forwarder, array $payload, ?string $signature = null): array
    {
        return $this->driver($forwarder)->webhook($forwarder, $payload, $signature);
    }

    public function driver(B2BFreightForwarder $forwarder): FreightForwarderInterface
    {
        $driverClass = config('b2b_freight.drivers.' . $forwarder->driver);
        if (!$driverClass || !class_exists($driverClass)) {
            throw new InvalidArgumentException('Unsupported freight forwarder driver: ' . ($forwarder->driver ?: 'undefined'));
        }

        return app($driverClass);
    }
}
