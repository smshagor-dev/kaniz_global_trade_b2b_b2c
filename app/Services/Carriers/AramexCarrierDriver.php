<?php

namespace App\Services\Carriers;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use Illuminate\Support\Arr;

class AramexCarrierDriver extends AbstractCarrierDriver
{
    protected function driverKey(): string
    {
        return 'aramex';
    }

    protected function credentialRules(): array
    {
        return [
            'username' => 'Username',
            'password' => 'Password',
            'account_number' => 'Account number',
            'api_key' => 'Account pin',
            'api_secret' => 'Account entity',
        ];
    }

    protected function clientInfo(B2BShippingProvider $provider): array
    {
        return [
            'UserName' => (string) $provider->username,
            'Password' => (string) $provider->password,
            'Version' => 'v1.0',
            'AccountNumber' => (string) $provider->account_number,
            'AccountPin' => (string) $provider->api_key,
            'AccountEntity' => (string) $provider->api_secret,
            'AccountCountryCode' => (string) ($provider->environment ?: 'AE'),
        ];
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

        return $this->success('connected', 'Aramex credentials validated and connection settings are ready.');
    }

    public function trackShipment(B2BShippingProvider $provider, B2BShipment $shipment): array
    {
        if (blank($shipment->tracking_number)) {
            return $this->failure('not_configured', 'Tracking number is required before Aramex tracking can run.');
        }

        $request = [
            'ClientInfo' => $this->clientInfo($provider),
            'Shipments' => [(string) $shipment->tracking_number],
            'GetLastTrackingUpdateOnly' => false,
        ];

        $result = $this->send($provider, 'POST', '/Tracking/Service_1_0.svc/json/TrackShipments', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);
        $events = collect(Arr::get($body, 'TrackingResults.0.TrackingResult', []))
            ->map(fn ($event) => [
                'status' => $this->normalizeStatus((string) Arr::get($event, 'UpdateDescription', Arr::get($event, 'UpdateCode', ''))),
                'carrier_status' => Arr::get($event, 'UpdateDescription', Arr::get($event, 'UpdateCode')),
                'title' => Arr::get($event, 'UpdateDescription'),
                'description' => Arr::get($event, 'Comments'),
                'city' => Arr::get($event, 'UpdateLocation'),
                'country' => Arr::get($event, 'UpdateCountryCode'),
                'location' => trim((string) Arr::get($event, 'UpdateLocation') . ', ' . (string) Arr::get($event, 'UpdateCountryCode'), ', '),
                'event_at' => $this->parseTimestamp(Arr::get($event, 'UpdateDateTime')),
                'carrier_event' => Arr::get($event, 'UpdateCode'),
            ])
            ->values()
            ->all();

        $latestEvent = $events ? end($events) : null;

        return $this->success('tracked', 'Aramex shipment tracking updated.', [
            'tracking_number' => $shipment->tracking_number,
            'carrier_reference' => $shipment->carrier_reference,
            'carrier_status' => Arr::get($latestEvent, 'carrier_status'),
            'normalized_status' => Arr::get($latestEvent, 'status'),
            'events' => $events,
            'tracked_at' => now()->toIso8601String(),
            'current_location' => Arr::get($latestEvent, 'city'),
            'current_country' => Arr::get($latestEvent, 'country'),
            'last_response' => $body,
        ]);
    }

    public function createShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        $package = $this->packagePayload($shipment, $payload);

        $request = [
            'ClientInfo' => $this->clientInfo($provider),
            'LabelInfo' => [
                'ReportID' => 9729,
                'ReportType' => strtoupper((string) Arr::get($payload, 'label_format', 'PDF')),
            ],
            'Shipments' => [[
                'Reference1' => $shipment->shipment_number,
                'Shipper' => $this->companyAddress($payload, 'shipper'),
                'Consignee' => $this->companyAddress($payload, 'recipient'),
                'ShippingDateTime' => now()->toIso8601String(),
                'Details' => [
                    'ActualWeight' => [
                        'Unit' => 'KG',
                        'Value' => (float) ($package['weight'] ?? 1),
                    ],
                    'Dimensions' => [
                        'Length' => (float) ($package['length'] ?? 10),
                        'Width' => (float) ($package['width'] ?? 10),
                        'Height' => (float) ($package['height'] ?? 10),
                        'Unit' => 'CM',
                    ],
                    'ProductGroup' => Arr::get($payload, 'product_group', 'EXP'),
                    'ProductType' => Arr::get($payload, 'product_type', 'PPX'),
                    'PaymentType' => 'P',
                    'NumberOfPieces' => (int) Arr::get($payload, 'pieces', 1),
                    'DescriptionOfGoods' => Arr::get($payload, 'description', $shipment->notes ?: 'B2B shipment'),
                    'GoodsOriginCountry' => Arr::get($payload, 'origin_country', $shipment->origin_country),
                    'DeclaredValue' => (float) ($package['declared_value'] ?? 0),
                    'DeclaredValueCurrencyCode' => $package['currency'] ?? 'USD',
                ],
            ]],
        ];

        $result = $this->send($provider, 'POST', '/ShippingAPI.V2/Shipping/Service_1_0.svc/json/CreateShipments', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);
        $shipmentResult = Arr::get($body, 'Shipments.0', []);

        return $this->success('created', 'Aramex shipment created successfully.', [
            'tracking_number' => Arr::get($shipmentResult, 'ID'),
            'carrier_reference' => Arr::get($shipmentResult, 'Reference1'),
            'carrier_service' => Arr::get($shipmentResult, 'Details.ProductType'),
            'carrier_status' => 'Label Created',
            'normalized_status' => 'label_created',
            'label' => Arr::get($body, 'Shipments.0.ShipmentLabel.LabelURL'),
            'label_is_url' => true,
            'label_format' => strtoupper((string) Arr::get($payload, 'label_format', 'PDF')),
            'events' => [[
                'status' => 'label_created',
                'carrier_status' => 'Label Created',
                'title' => 'Label created',
                'event_at' => now()->toIso8601String(),
            ]],
            'last_response' => $body,
            'request_payload' => $request,
        ]);
    }

    public function cancelShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        $trackingNumber = $shipment->tracking_number ?: Arr::get($payload, 'tracking_number');
        if (blank($trackingNumber)) {
            return $this->failure('not_configured', 'Tracking number is required before Aramex shipment cancellation can run.');
        }

        $result = $this->send($provider, 'POST', '/ShippingAPI.V2/Shipping/Service_1_0.svc/json/CancelShipments', [
            'json' => [
                'ClientInfo' => $this->clientInfo($provider),
                'Shipments' => [(string) $trackingNumber],
            ],
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        return $this->success('cancelled', 'Aramex shipment cancelled successfully.', [
            'normalized_status' => 'cancelled',
            'carrier_status' => 'Cancelled',
            'events' => [[
                'status' => 'cancelled',
                'carrier_status' => 'Cancelled',
                'title' => 'Shipment cancelled',
                'event_at' => now()->toIso8601String(),
            ]],
            'last_response' => $result['body'],
        ]);
    }

    public function requestPickup(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        $request = [
            'ClientInfo' => $this->clientInfo($provider),
            'Pickup' => [
                'PickupDate' => Arr::get($payload, 'pickup_date', now()->addDay()->toIso8601String()),
                'ReadyTime' => Arr::get($payload, 'ready_time', '10:00'),
                'LastPickupTime' => Arr::get($payload, 'close_time', '18:00'),
                'ClosingTime' => Arr::get($payload, 'close_time', '18:00'),
                'Reference1' => $shipment->shipment_number,
                'Shipments' => [$shipment->tracking_number],
            ],
        ];

        $result = $this->send($provider, 'POST', '/ShippingAPI.V2/Shipping/Service_1_0.svc/json/CreatePickup', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        return $this->success('pickup_scheduled', 'Aramex pickup scheduled successfully.', [
            'pickup_status' => 'scheduled',
            'pickup_date' => Arr::get($request, 'Pickup.PickupDate'),
            'pickup_confirmation' => Arr::get($result['body'], 'ProcessedPickup.Reference1'),
            'events' => [[
                'status' => 'pickup_scheduled',
                'carrier_status' => 'Pickup Scheduled',
                'title' => 'Pickup scheduled',
                'event_at' => now()->toIso8601String(),
            ]],
            'last_response' => $result['body'],
        ]);
    }

    public function createLabel(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        if ($shipment->latest_label_path && $shipment->latest_label_format) {
            return $this->success('label_ready', 'Stored Aramex label is available.', [
                'label_path' => $shipment->latest_label_path,
                'label_format' => $shipment->latest_label_format,
            ]);
        }

        return $this->unsupported('Aramex labels are returned during shipment creation. Re-run shipment creation if no label was stored.');
    }

    public function getShippingRates(B2BShippingProvider $provider, array $payload = []): array
    {
        $request = [
            'ClientInfo' => $this->clientInfo($provider),
            'OriginAddress' => $this->companyAddress($payload, 'shipper'),
            'DestinationAddress' => $this->companyAddress($payload, 'recipient'),
            'ShipmentDetails' => [
                'ActualWeight' => [
                    'Unit' => 'KG',
                    'Value' => (float) Arr::get($payload, 'weight', 1),
                ],
                'ProductGroup' => Arr::get($payload, 'product_group', 'EXP'),
                'ProductType' => Arr::get($payload, 'product_type', 'PPX'),
                'PaymentType' => 'P',
                'NumberOfPieces' => (int) Arr::get($payload, 'pieces', 1),
            ],
        ];

        $result = $this->send($provider, 'POST', '/RateCalculator/Service_1_0.svc/json/CalculateRate', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        return $this->success('rated', 'Aramex rates retrieved successfully.', [
            'rates' => [[
                'service_type' => Arr::get($request, 'ShipmentDetails.ProductType'),
                'service_name' => Arr::get($request, 'ShipmentDetails.ProductGroup'),
                'currency' => Arr::get($result['body'], 'TotalAmount.CurrencyCode'),
                'amount' => Arr::get($result['body'], 'TotalAmount.Value'),
            ]],
            'request_payload' => $request,
            'last_response' => $result['body'],
        ]);
    }

    public function webhook(B2BShippingProvider $provider, array $payload, ?string $signature = null): array
    {
        $status = Arr::get($payload, 'UpdateDescription', Arr::get($payload, 'status'));

        return $this->success('processed', 'Aramex webhook normalized.', [
            'tracking_number' => Arr::get($payload, 'WaybillNumber'),
            'carrier_status' => $status,
            'normalized_status' => $this->normalizeStatus((string) $status),
            'events' => [[
                'status' => $this->normalizeStatus((string) $status),
                'carrier_status' => $status,
                'title' => $status ?: 'Carrier event',
                'description' => Arr::get($payload, 'Comments'),
                'city' => Arr::get($payload, 'UpdateLocation'),
                'country' => Arr::get($payload, 'UpdateCountryCode'),
                'location' => trim((string) Arr::get($payload, 'UpdateLocation') . ', ' . (string) Arr::get($payload, 'UpdateCountryCode'), ', '),
                'event_at' => $this->parseTimestamp(Arr::get($payload, 'UpdateDateTime')) ?? now()->toIso8601String(),
                'carrier_event' => Arr::get($payload, 'UpdateCode'),
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
            str_contains($normalized, 'hub') => 'arrived_hub',
            str_contains($normalized, 'export') && str_contains($normalized, 'custom') => 'export_customs',
            str_contains($normalized, 'import') && str_contains($normalized, 'custom') => 'import_customs',
            str_contains($normalized, 'hold') => 'customs_hold',
            str_contains($normalized, 'out') && str_contains($normalized, 'delivery') => 'out_for_delivery',
            str_contains($normalized, 'deliver') => 'delivered',
            str_contains($normalized, 'return') => 'returned',
            str_contains($normalized, 'cancel') => 'cancelled',
            str_contains($normalized, 'exception') || str_contains($normalized, 'delay') => 'exception',
            default => null,
        };
    }
}
