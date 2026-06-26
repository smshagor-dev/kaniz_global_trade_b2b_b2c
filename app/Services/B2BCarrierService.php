<?php

namespace App\Services;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use App\Services\Carriers\CarrierManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class B2BCarrierService
{
    public function __construct(
        protected CarrierManager $carrierManager,
        protected B2BTradeService $b2bTradeService,
        protected B2BAuditService $b2bAuditService,
        protected B2BNotificationService $b2bNotificationService
    ) {
    }

    public function createShipment(B2BShipment $shipment, array $payload = [], ?int $actorId = null): array
    {
        $shipment->loadMissing(['shippingProvider', 'supplierCompany', 'buyerCompany']);
        $provider = $shipment->shippingProvider;

        if (!$provider) {
            return $this->failure($shipment, 'No shipping provider is assigned to this shipment.', 'not_configured');
        }

        $result = $this->carrierManager->createShipment($provider, $shipment, $this->withDefaultAddresses($shipment, $payload));
        if (!($result['success'] ?? false)) {
            return $this->failure($shipment, $result['message'] ?? 'Carrier shipment creation failed.', $result['status'] ?? 'failed');
        }

        $this->applyCarrierResult($shipment, $result, $actorId, 'shipment_created', 'Shipment created with carrier.');

        return [
            'success' => true,
            'status' => 'created',
            'message' => $result['message'] ?? 'Shipment created successfully.',
            'shipment_id' => $shipment->id,
        ];
    }

    public function cancelShipment(B2BShipment $shipment, array $payload = [], ?int $actorId = null): array
    {
        $shipment->loadMissing('shippingProvider');
        $provider = $shipment->shippingProvider;

        if (!$provider) {
            return $this->failure($shipment, 'No shipping provider is assigned to this shipment.', 'not_configured');
        }

        $result = $this->carrierManager->cancelShipment($provider, $shipment, $payload);
        if (!($result['success'] ?? false)) {
            return $this->failure($shipment, $result['message'] ?? 'Carrier cancellation failed.', $result['status'] ?? 'failed');
        }

        $this->applyCarrierResult($shipment, $result, $actorId, 'shipment_cancelled', 'Shipment cancelled with carrier.');

        return [
            'success' => true,
            'status' => 'cancelled',
            'message' => $result['message'] ?? 'Shipment cancelled successfully.',
            'shipment_id' => $shipment->id,
        ];
    }

    public function requestPickup(B2BShipment $shipment, array $payload = [], ?int $actorId = null): array
    {
        $shipment->loadMissing(['shippingProvider', 'supplierCompany']);
        $provider = $shipment->shippingProvider;

        if (!$provider) {
            return $this->failure($shipment, 'No shipping provider is assigned to this shipment.', 'not_configured');
        }

        $result = $this->carrierManager->requestPickup($provider, $shipment, $this->withDefaultAddresses($shipment, $payload));
        if (!($result['success'] ?? false)) {
            return $this->failure($shipment, $result['message'] ?? 'Carrier pickup request failed.', $result['status'] ?? 'failed');
        }

        $this->applyCarrierResult($shipment, $result, $actorId, 'shipment_pickup_scheduled', 'Shipment pickup scheduled.');

        return [
            'success' => true,
            'status' => 'pickup_scheduled',
            'message' => $result['message'] ?? 'Pickup scheduled successfully.',
            'shipment_id' => $shipment->id,
        ];
    }

    public function createLabel(B2BShipment $shipment, array $payload = [], ?int $actorId = null): array
    {
        $shipment->loadMissing('shippingProvider');
        $provider = $shipment->shippingProvider;

        if (!$provider) {
            return $this->failure($shipment, 'No shipping provider is assigned to this shipment.', 'not_configured');
        }

        $result = $this->carrierManager->createLabel($provider, $shipment, $payload);
        if (!($result['success'] ?? false)) {
            return $this->failure($shipment, $result['message'] ?? 'Carrier label generation failed.', $result['status'] ?? 'failed');
        }

        $this->applyCarrierResult($shipment, $result, $actorId, 'shipment_label_created', 'Shipment label generated.');

        return [
            'success' => true,
            'status' => 'label_ready',
            'message' => $result['message'] ?? 'Label is ready.',
            'shipment_id' => $shipment->id,
        ];
    }

    public function getRates(B2BShippingProvider $provider, array $payload = []): array
    {
        return $this->carrierManager->getShippingRates($provider, $payload);
    }

    protected function applyCarrierResult(B2BShipment $shipment, array $result, ?int $actorId, string $auditAction, string $auditMessage): void
    {
        $labelPath = $this->storeLabelIfPresent($shipment, $result);
        $estimatedDelivery = Arr::get($result, 'estimated_delivery');
        $pickupDate = Arr::get($result, 'pickup_date');

        $shipment->forceFill($shipment->filterPersistable(array_filter([
            'tracking_number' => Arr::get($result, 'tracking_number', $shipment->tracking_number),
            'carrier_reference' => Arr::get($result, 'carrier_reference', $shipment->carrier_reference),
            'carrier_service' => Arr::get($result, 'carrier_service', $shipment->carrier_service),
            'service_type' => Arr::get($result, 'carrier_service', $shipment->service_type),
            'carrier_status' => Arr::get($result, 'carrier_status', $shipment->carrier_status),
            'tracking_url' => Arr::get($result, 'tracking_url', $shipment->tracking_url),
            'sync_error' => null,
            'last_tracked_at' => now(),
            'carrier_payload' => Arr::get($result, 'request_payload'),
            'last_carrier_response' => $this->encodeForStorage(Arr::get($result, 'last_response')),
            'current_location' => Arr::get($result, 'current_location', $shipment->current_location),
            'current_country' => Arr::get($result, 'current_country', $shipment->current_country),
            'estimated_delivery_at' => $estimatedDelivery ? Carbon::parse($estimatedDelivery) : $shipment->estimated_delivery_at,
            'signed_receiver' => Arr::get($result, 'signed_receiver', $shipment->signed_receiver),
            'proof_of_delivery_url' => Arr::get($result, 'proof_of_delivery_url', $shipment->proof_of_delivery_url),
            'pickup_scheduled_at' => $pickupDate ? Carbon::parse($pickupDate) : $shipment->pickup_scheduled_at,
            'pickup_confirmation' => Arr::get($result, 'pickup_confirmation', $shipment->pickup_confirmation),
            'pickup_status' => Arr::get($result, 'pickup_status', $shipment->pickup_status),
            'latest_label_path' => $labelPath ?: $shipment->latest_label_path,
            'latest_label_format' => Arr::get($result, 'label_format', $shipment->latest_label_format),
        ], fn ($value) => $value !== null)))->save();

        foreach (Arr::get($result, 'events', []) as $eventData) {
            app(B2BShipmentTrackingService::class)->appendTrackingEvent($shipment, $eventData);
        }

        if (Arr::get($result, 'normalized_status') === 'label_created') {
            $this->b2bNotificationService->notifyShipmentTrackingUpdate($shipment, 'label_created');
        }

        if (Arr::get($result, 'normalized_status') === 'pickup_scheduled') {
            $this->b2bNotificationService->notifyShipmentTrackingUpdate($shipment, 'pickup_scheduled');
        }

        $this->b2bAuditService->log(
            $actorId,
            $shipment->supplier_company_id,
            $auditAction,
            $shipment,
            $auditMessage,
            [
                'carrier_status' => $shipment->carrier_status,
                'tracking_number' => $shipment->tracking_number,
            ]
        );
    }

    protected function storeLabelIfPresent(B2BShipment $shipment, array $result): ?string
    {
        $label = Arr::get($result, 'label');
        if (blank($label)) {
            return null;
        }

        if (Arr::get($result, 'label_is_url')) {
            return (string) $label;
        }

        $format = strtolower((string) Arr::get($result, 'label_format', 'pdf'));
        $extension = match ($format) {
            'png' => 'png',
            'zpl' => 'zpl',
            default => 'pdf',
        };

        $directory = 'uploads/b2b_labels';
        $path = $directory . '/' . Str::slug($shipment->shipment_number) . '-' . now()->format('YmdHis') . '.' . $extension;
        $binary = base64_decode((string) $label, true);
        Storage::disk('local')->put($path, $binary !== false ? $binary : (string) $label);

        $shipment->documents()->create([
            'uploaded_by' => $shipment->created_by,
            'company_id' => $shipment->supplier_company_id,
            'document_type' => 'shipping_label',
            'title' => 'Carrier label',
            'file_path' => $path,
            'issued_at' => now()->toDateString(),
            'notes' => Arr::get($result, 'carrier_service'),
        ]);

        return $path;
    }

    protected function withDefaultAddresses(B2BShipment $shipment, array $payload): array
    {
        $shipment->loadMissing(['supplierCompany', 'buyerCompany']);

        $payload['shipper'] ??= $this->companyPayload($shipment->supplierCompany);
        $payload['recipient'] ??= $this->companyPayload($shipment->buyerCompany);

        return $payload;
    }

    protected function companyPayload($company): array
    {
        if (!$company) {
            return [];
        }

        return array_filter([
            'companyName' => $company->company_name,
            'name' => $company->company_name,
            'personName' => $company->company_name,
            'emailAddress' => $company->business_email,
            'phoneNumber' => $company->phone,
            'cityName' => $company->city,
            'city' => $company->city,
            'countryCode' => $company->country,
            'country' => $company->country,
            'postalCode' => null,
            'addressLine1' => $company->address,
            'addressLine' => [$company->address],
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    protected function encodeForStorage(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    protected function failure(B2BShipment $shipment, string $message, string $status): array
    {
        $shipment->forceFill([
            'sync_error' => $message,
            'last_tracked_at' => now(),
        ])->save();

        return [
            'success' => false,
            'status' => $status,
            'message' => $message,
            'shipment_id' => $shipment->id,
        ];
    }
}
