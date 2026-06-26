<?php

namespace App\Services\Carriers;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;

class DhlCarrierDriver extends AbstractCarrierDriver
{
    protected function driverKey(): string
    {
        return 'dhl';
    }

    protected function credentialRules(): array
    {
        return [
            'api_key' => 'API key',
            'api_secret' => 'API secret',
            'account_number' => 'Account number',
        ];
    }

    protected function authorizeRequest(B2BShippingProvider $provider, array $headers = []): PendingRequest
    {
        return $this->http($provider, $headers)->withBasicAuth((string) $provider->api_key, (string) $provider->api_secret);
    }

    public function validateCredentials(B2BShippingProvider $provider): array
    {
        return $this->validateConfigured($provider);
    }

    public function testConnection(B2BShippingProvider $provider): array
    {
        $configured = $this->validateConfigured($provider);
        if (!($configured['success'] ?? false)) {
            return $configured;
        }

        return $this->success('connected', 'DHL credentials validated and connection settings are ready.');
    }

    public function trackShipment(B2BShippingProvider $provider, B2BShipment $shipment): array
    {
        $configured = $this->validateConfigured($provider);
        if (!($configured['success'] ?? false)) {
            return $configured;
        }

        if (blank($shipment->tracking_number)) {
            return $this->failure('not_configured', 'Tracking number is required before DHL tracking can run.');
        }

        $result = $this->send($provider, 'GET', '/shipments/' . urlencode((string) $shipment->tracking_number) . '/tracking');
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);
        $events = collect(Arr::get($body, 'shipments.0.events', []))
            ->map(fn ($event) => [
                'status' => $this->normalizeStatus((string) Arr::get($event, 'description', Arr::get($event, 'statusCode', ''))),
                'carrier_status' => Arr::get($event, 'description', Arr::get($event, 'statusCode')),
                'title' => Arr::get($event, 'description'),
                'description' => Arr::get($event, 'remarks.0'),
                'city' => Arr::get($event, 'location.address.addressLocality'),
                'country' => Arr::get($event, 'location.address.countryCode'),
                'location' => trim((string) Arr::get($event, 'location.address.addressLocality') . ', ' . (string) Arr::get($event, 'location.address.countryCode'), ', '),
                'event_at' => $this->parseTimestamp(Arr::get($event, 'timestamp')),
                'carrier_event' => Arr::get($event, 'statusCode'),
            ])
            ->values()
            ->all();

        $latestEvent = $events ? end($events) : null;

        return $this->success('tracked', 'DHL shipment tracking updated.', [
            'tracking_number' => $shipment->tracking_number,
            'carrier_reference' => Arr::get($body, 'shipments.0.id'),
            'carrier_service' => Arr::get($body, 'shipments.0.service'),
            'carrier_status' => Arr::get($latestEvent, 'carrier_status'),
            'normalized_status' => $latestEvent['status'] ?? null,
            'tracking_url' => 'https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id=' . urlencode((string) $shipment->tracking_number),
            'events' => $events,
            'tracked_at' => now()->toIso8601String(),
            'current_location' => Arr::get($latestEvent, 'city'),
            'current_country' => Arr::get($latestEvent, 'country'),
            'estimated_delivery' => Arr::get($body, 'shipments.0.estimatedTimeOfDelivery'),
            'proof_of_delivery_url' => Arr::get($body, 'shipments.0.documents.0.url'),
            'last_response' => $body,
        ]);
    }

    public function createShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        $configured = $this->validateConfigured($provider);
        if (!($configured['success'] ?? false)) {
            return $configured;
        }

        $shipper = $this->companyAddress($payload, 'shipper');
        $recipient = $this->companyAddress($payload, 'recipient');
        $package = $this->packagePayload($shipment, $payload);

        $request = [
            'plannedShippingDateAndTime' => Arr::get($payload, 'planned_shipping_at', now()->addHour()->toIso8601String()),
            'pickup' => [
                'isRequested' => (bool) Arr::get($payload, 'pickup_requested', false),
            ],
            'productCode' => Arr::get($payload, 'product_code', 'P'),
            'accounts' => [[
                'typeCode' => 'shipper',
                'number' => (string) $provider->account_number,
            ]],
            'customerDetails' => [
                'shipperDetails' => $shipper,
                'receiverDetails' => $recipient,
            ],
            'content' => [
                'packages' => [[
                    'weight' => $package['weight'] ?? 1,
                    'dimensions' => [
                        'length' => $package['length'] ?? 10,
                        'width' => $package['width'] ?? 10,
                        'height' => $package['height'] ?? 10,
                    ],
                    'customerReferences' => [[
                        'typeCode' => 'CU',
                        'value' => $shipment->shipment_number,
                    ]],
                ]],
                'description' => Arr::get($payload, 'description', $shipment->notes ?: 'B2B shipment'),
                'declaredValue' => $package['declared_value'],
                'declaredValueCurrency' => $package['currency'] ?? 'USD',
                'isCustomsDeclarable' => true,
            ],
            'outputImageProperties' => [
                'printerDPI' => 300,
                'encodingFormat' => strtoupper((string) Arr::get($payload, 'label_format', 'PDF')),
            ],
        ];

        $result = $this->send($provider, 'POST', '/shipments', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);
        $trackingNumber = Arr::get($body, 'shipmentTrackingNumber');
        $label = Arr::get($body, 'documents.0.content');
        $labelFormat = strtoupper((string) Arr::get($body, 'documents.0.typeCode', Arr::get($payload, 'label_format', 'PDF')));

        return $this->success('created', 'DHL shipment created successfully.', [
            'tracking_number' => $trackingNumber,
            'carrier_reference' => Arr::get($body, 'shipmentTrackingNumber'),
            'carrier_service' => Arr::get($body, 'products.0.productName'),
            'carrier_status' => 'Label Created',
            'normalized_status' => 'label_created',
            'tracking_url' => $trackingNumber ? 'https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id=' . urlencode((string) $trackingNumber) : null,
            'estimated_delivery' => Arr::get($body, 'estimatedDeliveryDate'),
            'pickup_confirmation' => Arr::get($body, 'dispatchConfirmationNumber'),
            'label' => $label,
            'label_format' => $labelFormat,
            'pickup_status' => Arr::get($request, 'pickup.isRequested') ? 'scheduled' : null,
            'pickup_date' => Arr::get($payload, 'pickup_date'),
            'events' => [[
                'status' => 'label_created',
                'carrier_status' => 'Label Created',
                'title' => 'Label created',
                'description' => 'Shipment was created in DHL.',
                'event_at' => now()->toIso8601String(),
            ]],
            'last_response' => $body,
            'request_payload' => $request,
        ]);
    }

    public function cancelShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        $configured = $this->validateConfigured($provider);
        if (!($configured['success'] ?? false)) {
            return $configured;
        }

        $trackingNumber = $shipment->tracking_number ?: Arr::get($payload, 'tracking_number');
        if (blank($trackingNumber)) {
            return $this->failure('not_configured', 'Tracking number is required before DHL shipment cancellation can run.');
        }

        $result = $this->send($provider, 'POST', '/shipments/' . urlencode((string) $trackingNumber) . '/cancel', [
            'json' => [
                'reason' => Arr::get($payload, 'reason', 'Cancelled from B2B portal'),
            ],
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        return $this->success('cancelled', 'DHL shipment cancelled successfully.', [
            'normalized_status' => 'cancelled',
            'carrier_status' => 'Cancelled',
            'events' => [[
                'status' => 'cancelled',
                'carrier_status' => 'Cancelled',
                'title' => 'Shipment cancelled',
                'description' => Arr::get($payload, 'reason', 'Cancelled from B2B portal'),
                'event_at' => now()->toIso8601String(),
            ]],
            'last_response' => $result['body'],
        ]);
    }

    public function requestPickup(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        $configured = $this->validateConfigured($provider);
        if (!($configured['success'] ?? false)) {
            return $configured;
        }

        $request = [
            'plannedPickupDateAndTime' => Arr::get($payload, 'pickup_date', now()->addDay()->setTime(10, 0)->toIso8601String()),
            'customerDetails' => [
                'shipperDetails' => $this->companyAddress($payload, 'shipper'),
            ],
            'accounts' => [[
                'typeCode' => 'shipper',
                'number' => (string) $provider->account_number,
            ]],
            'shipmentDetails' => [[
                'accountTypeCode' => 'shipper',
                'productCode' => Arr::get($payload, 'product_code', 'P'),
            ]],
        ];

        $result = $this->send($provider, 'POST', '/pickups', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        return $this->success('pickup_scheduled', 'DHL pickup scheduled successfully.', [
            'pickup_status' => 'scheduled',
            'pickup_date' => Arr::get($request, 'plannedPickupDateAndTime'),
            'pickup_confirmation' => Arr::get($result['body'], 'dispatchConfirmationNumber'),
            'events' => [[
                'status' => 'pickup_scheduled',
                'carrier_status' => 'Pickup Scheduled',
                'title' => 'Pickup scheduled',
                'description' => 'Carrier pickup was scheduled.',
                'event_at' => now()->toIso8601String(),
            ]],
            'last_response' => $result['body'],
        ]);
    }

    public function createLabel(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        if ($shipment->latest_label_path && $shipment->latest_label_format) {
            return $this->success('label_ready', 'Stored DHL label is available.', [
                'label_path' => $shipment->latest_label_path,
                'label_format' => $shipment->latest_label_format,
            ]);
        }

        return $this->unsupported('DHL labels are returned during shipment creation. Re-create the shipment payload if no label was stored.');
    }

    public function getShippingRates(B2BShippingProvider $provider, array $payload = []): array
    {
        $configured = $this->validateConfigured($provider);
        if (!($configured['success'] ?? false)) {
            return $configured;
        }

        $request = [
            'customerDetails' => [
                'shipperDetails' => $this->companyAddress($payload, 'shipper'),
                'receiverDetails' => $this->companyAddress($payload, 'recipient'),
            ],
            'accounts' => [[
                'typeCode' => 'shipper',
                'number' => (string) $provider->account_number,
            ]],
            'plannedShippingDateAndTime' => Arr::get($payload, 'planned_shipping_at', now()->addHour()->toIso8601String()),
            'unitOfMeasurement' => 'metric',
            'isCustomsDeclarable' => true,
            'monetaryAmount' => [
                [
                    'typeCode' => 'declaredValue',
                    'value' => Arr::get($payload, 'declared_value', 0),
                    'currency' => Arr::get($payload, 'currency', 'USD'),
                ],
            ],
            'packages' => [[
                'weight' => Arr::get($payload, 'weight', 1),
                'dimensions' => [
                    'length' => Arr::get($payload, 'length', 10),
                    'width' => Arr::get($payload, 'width', 10),
                    'height' => Arr::get($payload, 'height', 10),
                ],
            ]],
        ];

        $result = $this->send($provider, 'POST', '/rates', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $products = collect(Arr::get($result['body'], 'products', []))
            ->map(fn ($product) => [
                'service_type' => Arr::get($product, 'productCode'),
                'service_name' => Arr::get($product, 'productName'),
                'currency' => Arr::get($product, 'totalPrice.0.currencyType'),
                'amount' => Arr::get($product, 'totalPrice.0.price'),
                'estimated_delivery' => Arr::get($product, 'deliveryCapabilities.estimatedDeliveryDateAndTime'),
            ])
            ->values()
            ->all();

        return $this->success('rated', 'DHL rates retrieved successfully.', [
            'rates' => $products,
            'request_payload' => $request,
            'last_response' => $result['body'],
        ]);
    }

    public function webhook(B2BShippingProvider $provider, array $payload, ?string $signature = null): array
    {
        $trackingNumber = Arr::get($payload, 'trackingNumber', Arr::get($payload, 'id'));
        $status = Arr::get($payload, 'status.description', Arr::get($payload, 'description'));

        return $this->success('processed', 'DHL webhook normalized.', [
            'tracking_number' => $trackingNumber,
            'carrier_status' => $status,
            'normalized_status' => $this->normalizeStatus((string) $status),
            'events' => [[
                'status' => $this->normalizeStatus((string) $status),
                'carrier_status' => $status,
                'title' => $status ?: 'Carrier event',
                'description' => Arr::get($payload, 'remarks.0'),
                'city' => Arr::get($payload, 'location.address.addressLocality'),
                'country' => Arr::get($payload, 'location.address.countryCode'),
                'location' => trim((string) Arr::get($payload, 'location.address.addressLocality') . ', ' . (string) Arr::get($payload, 'location.address.countryCode'), ', '),
                'event_at' => $this->parseTimestamp(Arr::get($payload, 'timestamp')) ?? now()->toIso8601String(),
                'carrier_event' => Arr::get($payload, 'status.statusCode'),
            ]],
            'last_response' => $payload,
        ]);
    }

    public function normalizeStatus(string $status, array $context = []): ?string
    {
        $normalized = strtolower(trim(str_replace(['-', ' '], '_', $status)));

        return match (true) {
            str_contains($normalized, 'label') => 'label_created',
            str_contains($normalized, 'pickup') && str_contains($normalized, 'schedule') => 'pickup_scheduled',
            str_contains($normalized, 'picked') => 'picked_up',
            str_contains($normalized, 'transit') => 'in_transit',
            str_contains($normalized, 'customs') && str_contains($normalized, 'export') => 'export_customs',
            str_contains($normalized, 'customs') && str_contains($normalized, 'import') => 'import_customs',
            str_contains($normalized, 'hold') => 'customs_hold',
            str_contains($normalized, 'delivery') && str_contains($normalized, 'out') => 'out_for_delivery',
            str_contains($normalized, 'delivered') => 'delivered',
            str_contains($normalized, 'return') => 'returned',
            str_contains($normalized, 'cancel') => 'cancelled',
            str_contains($normalized, 'exception') || str_contains($normalized, 'failure') => 'exception',
            default => null,
        };
    }
}
