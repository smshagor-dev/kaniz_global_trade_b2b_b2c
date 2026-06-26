<?php

namespace App\Services\Carriers;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class FedexCarrierDriver extends AbstractCarrierDriver
{
    protected function driverKey(): string
    {
        return 'fedex';
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
        $token = $provider->oauth_token ?: $this->fetchToken($provider);

        return $this->http($provider, array_merge($headers, [
            'Authorization' => 'Bearer ' . $token,
            'X-locale' => 'en_US',
        ]));
    }

    protected function fetchToken(B2BShippingProvider $provider): string
    {
        $request = Http::asForm()
            ->acceptJson()
            ->timeout((int) config('b2b_carriers.http.timeout', 20))
            ->retry((int) config('b2b_carriers.http.retry_times', 2), (int) config('b2b_carriers.http.retry_sleep_ms', 500))
            ->post($this->qualifyUri($provider, '/oauth/token'), [
                'grant_type' => 'client_credentials',
                'client_id' => (string) $provider->api_key,
                'client_secret' => (string) $provider->api_secret,
            ]);

        if (!$request->successful()) {
            throw new \RuntimeException('FedEx authentication failed.');
        }

        $token = (string) Arr::get($request->json(), 'access_token');
        if ($token === '') {
            throw new \RuntimeException('FedEx access token was not returned.');
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

        return $this->success('validated', 'FedEx credentials validated successfully.');
    }

    public function testConnection(B2BShippingProvider $provider): array
    {
        return $this->validateCredentials($provider);
    }

    public function trackShipment(B2BShippingProvider $provider, B2BShipment $shipment): array
    {
        if (blank($shipment->tracking_number)) {
            return $this->failure('not_configured', 'Tracking number is required before FedEx tracking can run.');
        }

        $result = $this->send($provider, 'POST', '/track/v1/trackingnumbers', [
            'json' => [
                'includeDetailedScans' => true,
                'trackingInfo' => [[
                    'trackingNumberInfo' => [
                        'trackingNumber' => (string) $shipment->tracking_number,
                    ],
                ]],
            ],
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);
        $details = Arr::get($body, 'output.completeTrackResults.0.trackResults.0', []);
        $events = collect(Arr::get($details, 'scanEvents', []))
            ->map(fn ($event) => [
                'status' => $this->normalizeStatus((string) Arr::get($event, 'eventDescription', Arr::get($event, 'derivedStatus', ''))),
                'carrier_status' => Arr::get($event, 'eventDescription', Arr::get($event, 'derivedStatus')),
                'title' => Arr::get($event, 'eventDescription'),
                'description' => Arr::get($event, 'exceptionDescription'),
                'city' => Arr::get($event, 'scanLocation.city'),
                'country' => Arr::get($event, 'scanLocation.countryCode'),
                'location' => trim((string) Arr::get($event, 'scanLocation.city') . ', ' . (string) Arr::get($event, 'scanLocation.countryCode'), ', '),
                'event_at' => $this->parseTimestamp(Arr::get($event, 'date')),
                'carrier_event' => Arr::get($event, 'derivedStatusCode'),
            ])
            ->values()
            ->all();

        $latestEvent = $events ? end($events) : null;

        return $this->success('tracked', 'FedEx shipment tracking updated.', [
            'tracking_number' => $shipment->tracking_number,
            'carrier_reference' => Arr::get($details, 'trackingNumberInfo.trackingNumber'),
            'carrier_service' => Arr::get($details, 'serviceDetail.type'),
            'carrier_status' => Arr::get($details, 'latestStatusDetail.statusByLocale')
                ?: Arr::get($latestEvent, 'carrier_status'),
            'normalized_status' => $this->normalizeStatus((string) (Arr::get($details, 'latestStatusDetail.statusByLocale') ?: Arr::get($latestEvent, 'carrier_status', ''))),
            'events' => $events,
            'tracked_at' => now()->toIso8601String(),
            'current_location' => Arr::get($details, 'latestStatusDetail.scanLocation.city'),
            'current_country' => Arr::get($details, 'latestStatusDetail.scanLocation.countryCode'),
            'estimated_delivery' => Arr::get($details, 'dateAndTimes.0.dateTime'),
            'signed_receiver' => Arr::get($details, 'deliveryDetails.receivedByName'),
            'proof_of_delivery_url' => Arr::get($details, 'deliveryDetails.proofOfDeliveryUrl'),
            'last_response' => $body,
        ]);
    }

    public function createShipment(B2BShippingProvider $provider, B2BShipment $shipment, array $payload = []): array
    {
        $shipper = $this->companyAddress($payload, 'shipper');
        $recipient = $this->companyAddress($payload, 'recipient');
        $package = $this->packagePayload($shipment, $payload);

        $request = [
            'labelResponseOptions' => 'LABEL',
            'requestedShipment' => [
                'shipDatestamp' => now()->toDateString(),
                'serviceType' => Arr::get($payload, 'service_type', $shipment->service_type ?: 'FEDEX_INTERNATIONAL_PRIORITY'),
                'packagingType' => Arr::get($payload, 'packaging_type', 'YOUR_PACKAGING'),
                'pickupType' => Arr::get($payload, 'pickup_type', 'USE_SCHEDULED_PICKUP'),
                'shipper' => $shipper,
                'recipients' => [$recipient],
                'shippingChargesPayment' => [
                    'paymentType' => 'SENDER',
                    'payor' => [
                        'responsibleParty' => [
                            'accountNumber' => [
                                'value' => (string) $provider->account_number,
                            ],
                        ],
                    ],
                ],
                'requestedPackageLineItems' => [[
                    'weight' => [
                        'units' => 'KG',
                        'value' => (float) ($package['weight'] ?? 1),
                    ],
                    'dimensions' => [
                        'length' => (int) ($package['length'] ?? 10),
                        'width' => (int) ($package['width'] ?? 10),
                        'height' => (int) ($package['height'] ?? 10),
                        'units' => 'CM',
                    ],
                ]],
                'customsClearanceDetail' => [
                    'dutiesPayment' => [
                        'paymentType' => 'SENDER',
                    ],
                    'customsValue' => [
                        'amount' => (float) ($package['declared_value'] ?? 0),
                        'currency' => $package['currency'] ?? 'USD',
                    ],
                ],
            ],
            'accountNumber' => [
                'value' => (string) $provider->account_number,
            ],
        ];

        $result = $this->send($provider, 'POST', '/ship/v1/shipments', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $body = Arr::wrap($result['body']);
        $completed = Arr::get($body, 'output.transactionShipments.0', []);
        $label = Arr::get($completed, 'pieceResponses.0.packageDocuments.0.encodedLabel')
            ?: Arr::get($completed, 'shipmentDocuments.0.encodedLabel');

        return $this->success('created', 'FedEx shipment created successfully.', [
            'tracking_number' => Arr::get($completed, 'masterTrackingNumber'),
            'carrier_reference' => Arr::get($completed, 'shipmentDocuments.0.trackingNumber') ?: Arr::get($completed, 'masterTrackingNumber'),
            'carrier_service' => Arr::get($completed, 'serviceType'),
            'carrier_status' => 'Label Created',
            'normalized_status' => 'label_created',
            'estimated_delivery' => Arr::get($completed, 'serviceCommitMessage.commitDate'),
            'label' => $label,
            'label_format' => strtoupper((string) Arr::get($payload, 'label_format', 'PDF')),
            'events' => [[
                'status' => 'label_created',
                'carrier_status' => 'Label Created',
                'title' => 'Label created',
                'description' => 'Shipment was created in FedEx.',
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
            return $this->failure('not_configured', 'Tracking number is required before FedEx shipment cancellation can run.');
        }

        $result = $this->send($provider, 'PUT', '/ship/v1/shipments/cancel', [
            'json' => [
                'accountNumber' => [
                    'value' => (string) $provider->account_number,
                ],
                'trackingNumber' => $trackingNumber,
                'deletionControl' => 'DELETE_ALL_PACKAGES',
            ],
        ]);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        return $this->success('cancelled', 'FedEx shipment cancelled successfully.', [
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
            'associatedAccountNumber' => [
                'value' => (string) $provider->account_number,
            ],
            'originDetail' => $this->companyAddress($payload, 'shipper'),
            'carrierCode' => Arr::get($payload, 'carrier_code', 'FDXE'),
            'packageCount' => (int) Arr::get($payload, 'package_count', 1),
            'totalWeight' => [
                'units' => 'KG',
                'value' => (float) ($shipment->total_weight ?: Arr::get($payload, 'weight', 1)),
            ],
            'readyDateTimestamp' => Arr::get($payload, 'pickup_date', now()->addDay()->setTime(10, 0)->toIso8601String()),
            'customerCloseTime' => Arr::get($payload, 'close_time', '18:00:00'),
        ];

        $result = $this->send($provider, 'POST', '/pickup/v1/pickups', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        return $this->success('pickup_scheduled', 'FedEx pickup scheduled successfully.', [
            'pickup_status' => 'scheduled',
            'pickup_date' => Arr::get($request, 'readyDateTimestamp'),
            'pickup_confirmation' => Arr::get($result['body'], 'output.pickupConfirmationCode'),
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
            return $this->success('label_ready', 'Stored FedEx label is available.', [
                'label_path' => $shipment->latest_label_path,
                'label_format' => $shipment->latest_label_format,
            ]);
        }

        return $this->unsupported('FedEx labels are returned during shipment creation. Re-run shipment creation if no label was stored.');
    }

    public function getShippingRates(B2BShippingProvider $provider, array $payload = []): array
    {
        $request = [
            'accountNumber' => [
                'value' => (string) $provider->account_number,
            ],
            'requestedShipment' => [
                'shipper' => $this->companyAddress($payload, 'shipper'),
                'recipient' => $this->companyAddress($payload, 'recipient'),
                'pickupType' => Arr::get($payload, 'pickup_type', 'USE_SCHEDULED_PICKUP'),
                'serviceType' => Arr::get($payload, 'service_type'),
                'preferredCurrency' => Arr::get($payload, 'currency', 'USD'),
                'requestedPackageLineItems' => [[
                    'weight' => [
                        'units' => 'KG',
                        'value' => (float) Arr::get($payload, 'weight', 1),
                    ],
                    'dimensions' => [
                        'length' => (int) Arr::get($payload, 'length', 10),
                        'width' => (int) Arr::get($payload, 'width', 10),
                        'height' => (int) Arr::get($payload, 'height', 10),
                        'units' => 'CM',
                    ],
                ]],
            ],
        ];

        $result = $this->send($provider, 'POST', '/rate/v1/rates/quotes', ['json' => $request]);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $rateReply = collect(Arr::get($result['body'], 'output.rateReplyDetails', []))
            ->map(fn ($detail) => [
                'service_type' => Arr::get($detail, 'serviceType'),
                'service_name' => Arr::get($detail, 'serviceName'),
                'currency' => Arr::get($detail, 'ratedShipmentDetails.0.totalNetCharge.currency'),
                'amount' => Arr::get($detail, 'ratedShipmentDetails.0.totalNetCharge.amount'),
                'estimated_delivery' => Arr::get($detail, 'commit.transitDays'),
            ])
            ->values()
            ->all();

        return $this->success('rated', 'FedEx rates retrieved successfully.', [
            'rates' => $rateReply,
            'request_payload' => $request,
            'last_response' => $result['body'],
        ]);
    }

    public function webhook(B2BShippingProvider $provider, array $payload, ?string $signature = null): array
    {
        $status = Arr::get($payload, 'scanEvent.eventDescription', Arr::get($payload, 'status'));

        return $this->success('processed', 'FedEx webhook normalized.', [
            'tracking_number' => Arr::get($payload, 'trackingNumber'),
            'carrier_status' => $status,
            'normalized_status' => $this->normalizeStatus((string) $status),
            'events' => [[
                'status' => $this->normalizeStatus((string) $status),
                'carrier_status' => $status,
                'title' => $status ?: 'Carrier event',
                'description' => Arr::get($payload, 'scanEvent.exceptionDescription'),
                'city' => Arr::get($payload, 'scanEvent.scanLocation.city'),
                'country' => Arr::get($payload, 'scanEvent.scanLocation.countryCode'),
                'location' => trim((string) Arr::get($payload, 'scanEvent.scanLocation.city') . ', ' . (string) Arr::get($payload, 'scanEvent.scanLocation.countryCode'), ', '),
                'event_at' => $this->parseTimestamp(Arr::get($payload, 'scanEvent.date')) ?? now()->toIso8601String(),
                'carrier_event' => Arr::get($payload, 'scanEvent.derivedStatusCode'),
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
            str_contains($normalized, 'export') && str_contains($normalized, 'customs') => 'export_customs',
            str_contains($normalized, 'import') && str_contains($normalized, 'customs') => 'import_customs',
            str_contains($normalized, 'hold') => 'customs_hold',
            str_contains($normalized, 'out') && str_contains($normalized, 'delivery') => 'out_for_delivery',
            str_contains($normalized, 'deliver') => 'delivered',
            str_contains($normalized, 'return') => 'returned',
            str_contains($normalized, 'cancel') => 'cancelled',
            str_contains($normalized, 'exception') || str_contains($normalized, 'delay') || str_contains($normalized, 'failed') => 'exception',
            default => null,
        };
    }
}
