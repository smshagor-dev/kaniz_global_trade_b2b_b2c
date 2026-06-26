<?php

namespace App\Services\Carriers;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UpsCarrierDriver extends AbstractCarrierDriver
{
    protected function driverKey(): string
    {
        return 'ups';
    }

    protected function credentialRules(): array
    {
        return [
            'api_key' => 'Client id',
            'api_secret' => 'Client secret',
            'account_number' => 'Account number',
        ];
    }

    protected function apiVersion(): string
    {
        return (string) config('b2b_carriers.carriers.ups.api_version', 'v1');
    }

    protected function authorizeRequest(B2BShippingProvider $provider, array $headers = []): PendingRequest
    {
        $token = $provider->oauth_token ?: $this->fetchToken($provider);

        return $this->http($provider, array_merge($headers, [
            'Authorization' => 'Bearer ' . $token,
            'transId' => (string) Str::uuid(),
            'transactionSrc' => 'KanizGlobalTradeB2B',
        ]));
    }

    protected function fetchToken(B2BShippingProvider $provider): string
    {
        $response = Http::withBasicAuth((string) $provider->api_key, (string) $provider->api_secret)
            ->asForm()
            ->acceptJson()
            ->timeout((int) config('b2b_carriers.http.timeout', 20))
            ->post($this->qualifyUri($provider, '/security/v1/oauth/token'), [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('UPS authentication failed.');
        }

        $token = (string) Arr::get($response->json(), 'access_token');
        if ($token === '') {
            throw new \RuntimeException('UPS access token was not returned.');
        }

        $provider->forceFill($provider->filterPersistable(['oauth_token' => $token]))->save();

        return $token;
    }

    public function validateCredentials(B2BShippingProvider $provider): array
    {
        $configured = $this->validateConfigured($provider);
        if (!($configured['success'] ?? false)) {
            return $configured;
        }

        try {
            $this->fetchToken($provider);
        } catch (\Throwable $exception) {
            return $this->failure('authentication_failed', $exception->getMessage());
        }

        return $this->success('validated', 'UPS credentials validated successfully.');
    }

    public function testConnection(B2BShippingProvider $provider): array
    {
        return $this->validateCredentials($provider);
    }

    public function trackShipment(B2BShippingProvider $provider, B2BShipment $shipment): array
    {
        if (blank($shipment->tracking_number)) {
            return $this->failure('not_configured', 'Tracking number is required before UPS tracking can run.');
        }

        $result = $this->send($provider, 'GET', '/api/track/' . $this->apiVersion() . '/details/' . urlencode((string) $shipment->tracking_number));
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);
        $package = Arr::get($body, 'trackResponse.shipment.0.package.0', []);
        $events = collect(Arr::get($package, 'activity', []))
            ->map(fn ($event) => [
                'status' => $this->normalizeStatus((string) Arr::get($event, 'status.description', '')),
                'carrier_status' => Arr::get($event, 'status.description'),
                'title' => Arr::get($event, 'status.description'),
                'description' => Arr::get($event, 'activityLocation.description'),
                'city' => Arr::get($event, 'activityLocation.address.city'),
                'country' => Arr::get($event, 'activityLocation.address.countryCode'),
                'location' => trim((string) Arr::get($event, 'activityLocation.address.city') . ', ' . (string) Arr::get($event, 'activityLocation.address.countryCode'), ', '),
                'event_at' => $this->parseTimestamp(Arr::get($event, 'date') . ' ' . Arr::get($event, 'time')),
                'carrier_event' => Arr::get($event, 'status.code'),
            ])
            ->values()
            ->all();

        $latestEvent = $events ? end($events) : null;

        return $this->success('tracked', 'UPS shipment tracking updated.', [
            'tracking_number' => $shipment->tracking_number,
            'carrier_reference' => Arr::get($package, 'trackingNumber'),
            'carrier_service' => Arr::get($body, 'trackResponse.shipment.0.service.description'),
            'carrier_status' => Arr::get($latestEvent, 'carrier_status'),
            'normalized_status' => $latestEvent['status'] ?? null,
            'events' => $events,
            'tracked_at' => now()->toIso8601String(),
            'current_location' => Arr::get($latestEvent, 'city'),
            'current_country' => Arr::get($latestEvent, 'country'),
            'signed_receiver' => Arr::get($package, 'deliveryInformation.receivedBy.name'),
            'proof_of_delivery_url' => Arr::get($package, 'deliveryInformation.pod.url'),
            'last_response' => $body,
        ]);
    }

    public function createShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        $shipper = $this->companyAddress($payload, 'shipper');
        $recipient = $this->companyAddress($payload, 'recipient');
        $package = $this->packagePayload($shipment, $payload);

        $request = [
            'ShipmentRequest' => [
                'Shipment' => [
                    'Description' => Arr::get($payload, 'description', $shipment->notes ?: 'B2B shipment'),
                    'Shipper' => $shipper,
                    'ShipTo' => $recipient,
                    'PaymentInformation' => [
                        'ShipmentCharge' => [
                            'Type' => '01',
                            'BillShipper' => [
                                'AccountNumber' => (string) $provider->account_number,
                            ],
                        ],
                    ],
                    'Service' => [
                        'Code' => Arr::get($payload, 'service_code', '65'),
                    ],
                    'Package' => [[
                        'PackagingType' => ['Code' => Arr::get($payload, 'packaging_code', '02')],
                        'PackageWeight' => [
                            'UnitOfMeasurement' => ['Code' => 'KGS'],
                            'Weight' => (string) ($package['weight'] ?? 1),
                        ],
                        'Dimensions' => [
                            'UnitOfMeasurement' => ['Code' => 'CM'],
                            'Length' => (string) ($package['length'] ?? 10),
                            'Width' => (string) ($package['width'] ?? 10),
                            'Height' => (string) ($package['height'] ?? 10),
                        ],
                    ]],
                ],
                'LabelSpecification' => [
                    'LabelImageFormat' => [
                        'Code' => strtoupper((string) Arr::get($payload, 'label_format', 'PDF')),
                    ],
                ],
            ],
        ];

        $result = $this->send($provider, 'POST', '/api/shipments/' . $this->apiVersion() . '/ship', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);
        $shipmentResults = Arr::get($body, 'ShipmentResponse.ShipmentResults', []);

        return $this->success('created', 'UPS shipment created successfully.', [
            'tracking_number' => Arr::get($shipmentResults, 'PackageResults.0.TrackingNumber'),
            'carrier_reference' => Arr::get($shipmentResults, 'ShipmentIdentificationNumber'),
            'carrier_service' => Arr::get($shipmentResults, 'Service.Code'),
            'carrier_status' => 'Label Created',
            'normalized_status' => 'label_created',
            'label' => Arr::get($shipmentResults, 'PackageResults.0.ShippingLabel.GraphicImage'),
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
            return $this->failure('not_configured', 'Tracking number is required before UPS shipment cancellation can run.');
        }

        $result = $this->send($provider, 'DELETE', '/api/shipments/' . $this->apiVersion() . '/void/cancel/' . urlencode((string) $trackingNumber), [
            'json' => [],
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        return $this->success('cancelled', 'UPS shipment cancelled successfully.', [
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
            'PickupCreationRequest' => [
                'RatePickupIndicator' => 'N',
                'Shipper' => $this->companyAddress($payload, 'shipper'),
                'PickupDateInfo' => [
                    'CloseTime' => Arr::get($payload, 'close_time', '1800'),
                    'ReadyTime' => Arr::get($payload, 'ready_time', '1000'),
                    'PickupDate' => now()->addDay()->format('Ymd'),
                ],
            ],
        ];

        $result = $this->send($provider, 'POST', '/api/pickupcreation/' . $this->apiVersion() . '/pickups', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        return $this->success('pickup_scheduled', 'UPS pickup scheduled successfully.', [
            'pickup_status' => 'scheduled',
            'pickup_date' => Arr::get($request, 'PickupCreationRequest.PickupDateInfo.PickupDate'),
            'pickup_confirmation' => Arr::get($result['body'], 'PickupCreationResponse.PRN'),
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
            return $this->success('label_ready', 'Stored UPS label is available.', [
                'label_path' => $shipment->latest_label_path,
                'label_format' => $shipment->latest_label_format,
            ]);
        }

        return $this->unsupported('UPS labels are returned during shipment creation. Re-run shipment creation if no label was stored.');
    }

    public function getShippingRates(B2BShippingProvider $provider, array $payload = []): array
    {
        $request = [
            'RateRequest' => [
                'Shipment' => [
                    'Shipper' => $this->companyAddress($payload, 'shipper'),
                    'ShipTo' => $this->companyAddress($payload, 'recipient'),
                    'Package' => [[
                        'PackagingType' => ['Code' => Arr::get($payload, 'packaging_code', '02')],
                        'PackageWeight' => [
                            'UnitOfMeasurement' => ['Code' => 'KGS'],
                            'Weight' => (string) Arr::get($payload, 'weight', 1),
                        ],
                    ]],
                ],
            ],
        ];

        $result = $this->send($provider, 'POST', '/api/rating/' . $this->apiVersion() . '/Shop', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $rates = collect(Arr::get($result['body'], 'RateResponse.RatedShipment', []))
            ->map(fn ($detail) => [
                'service_type' => Arr::get($detail, 'Service.Code'),
                'service_name' => Arr::get($detail, 'Service.Description'),
                'currency' => Arr::get($detail, 'TotalCharges.CurrencyCode'),
                'amount' => Arr::get($detail, 'TotalCharges.MonetaryValue'),
            ])
            ->values()
            ->all();

        return $this->success('rated', 'UPS rates retrieved successfully.', [
            'rates' => $rates,
            'request_payload' => $request,
            'last_response' => $result['body'],
        ]);
    }

    public function webhook(B2BShippingProvider $provider, array $payload, ?string $signature = null): array
    {
        $status = Arr::get($payload, 'status.description', Arr::get($payload, 'description'));

        return $this->success('processed', 'UPS webhook normalized.', [
            'tracking_number' => Arr::get($payload, 'trackingNumber'),
            'carrier_status' => $status,
            'normalized_status' => $this->normalizeStatus((string) $status),
            'events' => [[
                'status' => $this->normalizeStatus((string) $status),
                'carrier_status' => $status,
                'title' => $status ?: 'Carrier event',
                'description' => Arr::get($payload, 'activityLocation.description'),
                'city' => Arr::get($payload, 'activityLocation.address.city'),
                'country' => Arr::get($payload, 'activityLocation.address.countryCode'),
                'location' => trim((string) Arr::get($payload, 'activityLocation.address.city') . ', ' . (string) Arr::get($payload, 'activityLocation.address.countryCode'), ', '),
                'event_at' => $this->parseTimestamp(Arr::get($payload, 'date') . ' ' . Arr::get($payload, 'time')) ?? now()->toIso8601String(),
                'carrier_event' => Arr::get($payload, 'status.code'),
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
            str_contains($normalized, 'transit') || str_contains($normalized, 'departed') => 'in_transit',
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
