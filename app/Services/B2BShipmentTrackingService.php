<?php

namespace App\Services;

use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use App\Services\Carriers\CarrierManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class B2BShipmentTrackingService
{
    public function __construct(
        protected CarrierManager $carrierManager,
        protected B2BTradeService $b2bTradeService,
        protected B2BAuditService $b2bAuditService,
        protected B2BNotificationService $b2bNotificationService
    ) {
    }

    public function syncShipment($shipmentId): array
    {
        $shipment = B2BShipment::with(['shippingProvider', 'purchaseOrder', 'sampleOrder'])->findOrFail($shipmentId);
        $provider = $shipment->shippingProvider;

        if (!$provider) {
            return $this->recordFailure($shipment, 'No shipping provider is assigned to this shipment.', 'not_configured');
        }

        $result = $this->carrierManager->trackShipment($provider, $shipment);

        if (!($result['success'] ?? false)) {
            return $this->recordFailure($shipment, $result['message'] ?? 'Carrier sync failed.', $result['status'] ?? 'failed');
        }

        $normalizedStatus = $result['normalized_status'] ?? $this->normalizeCarrierStatus($result['carrier_status'] ?? null);

        $shipment->forceFill($shipment->filterPersistable([
            'carrier_status' => $result['carrier_status'] ?? $shipment->carrier_status,
            'carrier_reference' => $result['carrier_reference'] ?? $shipment->carrier_reference,
            'carrier_service' => $result['carrier_service'] ?? $shipment->carrier_service,
            'tracking_url' => $result['tracking_url'] ?? $shipment->tracking_url,
            'last_tracked_at' => now(),
            'sync_error' => null,
            'last_carrier_response' => $this->encodeForStorage($result['last_response'] ?? null),
            'current_location' => $result['current_location'] ?? $shipment->current_location,
            'current_country' => $result['current_country'] ?? $shipment->current_country,
            'estimated_delivery_at' => isset($result['estimated_delivery']) ? Carbon::parse($result['estimated_delivery']) : $shipment->estimated_delivery_at,
            'signed_receiver' => $result['signed_receiver'] ?? $shipment->signed_receiver,
            'proof_of_delivery_url' => $result['proof_of_delivery_url'] ?? $shipment->proof_of_delivery_url,
        ]))->save();

        foreach (($result['events'] ?? []) as $eventData) {
            $this->appendTrackingEvent($shipment, $eventData);
        }

        if ($normalizedStatus && $normalizedStatus !== $shipment->status) {
            $this->appendTrackingEvent($shipment, [
                'status' => $normalizedStatus,
                'carrier_status' => $result['carrier_status'] ?? $normalizedStatus,
                'title' => 'Carrier status update',
                'description' => $result['message'] ?? null,
                'event_at' => $result['tracked_at'] ?? now(),
            ]);
            $shipment->refresh();
        }

        $this->b2bAuditService->log(
            null,
            $shipment->supplier_company_id,
            'shipment_sync_success',
            $shipment,
            'Shipment tracking sync completed.',
            [
                'carrier_status' => $shipment->carrier_status,
                'status' => $shipment->status,
            ]
        );

        return [
            'success' => true,
            'status' => 'synced',
            'message' => $result['message'] ?? 'Shipment synced successfully.',
            'shipment_id' => $shipment->id,
        ];
    }

    public function syncDueShipments(): array
    {
        $shipments = B2BShipment::with('shippingProvider')
            ->where('live_tracking_enabled', true)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->get();

        $results = [
            'total' => $shipments->count(),
            'synced' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($shipments as $shipment) {
            if (!$shipment->shippingProvider || !$shipment->shippingProvider->is_active) {
                $results['skipped']++;
                continue;
            }

            $result = $this->syncShipment($shipment->id);

            if ($result['success'] ?? false) {
                $results['synced']++;
            } elseif (($result['status'] ?? null) === 'manual') {
                $results['skipped']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    public function handleWebhook($provider, $payload, $signature = null): array
    {
        $providerModel = $this->resolveProvider($provider);
        abort_unless($providerModel, 404);

        if (filled($providerModel->webhook_secret) && !hash_equals((string) $providerModel->webhook_secret, (string) $signature)) {
            Log::warning('Invalid B2B carrier webhook signature.', [
                'provider_id' => $providerModel->id,
                'api_driver' => $providerModel->api_driver,
            ]);

            return [
                'success' => false,
                'status' => 'invalid_signature',
                'message' => 'Invalid webhook signature.',
            ];
        }

        $result = $this->carrierManager->webhook($providerModel, (array) $payload, $signature);

        $this->b2bAuditService->log(
            null,
            null,
            'shipment_webhook_received',
            $providerModel,
            'Carrier webhook received.',
            [
                'status' => $result['status'] ?? null,
                'tracking_number' => $result['tracking_number'] ?? null,
            ]
        );

        if (!($result['success'] ?? false)) {
            return $result;
        }

        $shipment = B2BShipment::query()
            ->where('shipping_provider_id', $providerModel->id)
            ->when($result['tracking_number'] ?? null, fn ($query, $trackingNumber) => $query->where('tracking_number', $trackingNumber))
            ->when(!($result['tracking_number'] ?? null) && ($result['carrier_reference'] ?? null), fn ($query, $carrierReference) => $query->where('carrier_reference', $carrierReference))
            ->latest('id')
            ->first();

        if (!$shipment) {
            return [
                'success' => false,
                'status' => 'shipment_not_found',
                'message' => 'No shipment matched the incoming webhook payload.',
            ];
        }

        $shipment->forceFill($shipment->filterPersistable([
            'carrier_status' => $result['carrier_status'] ?? $shipment->carrier_status,
            'carrier_reference' => $result['carrier_reference'] ?? $shipment->carrier_reference,
            'carrier_service' => $result['carrier_service'] ?? $shipment->carrier_service,
            'tracking_url' => $result['tracking_url'] ?? $shipment->tracking_url,
            'last_tracked_at' => now(),
            'sync_error' => null,
            'last_carrier_response' => $this->encodeForStorage($result['last_response'] ?? null),
            'current_location' => $result['current_location'] ?? $shipment->current_location,
            'current_country' => $result['current_country'] ?? $shipment->current_country,
            'estimated_delivery_at' => isset($result['estimated_delivery']) ? Carbon::parse($result['estimated_delivery']) : $shipment->estimated_delivery_at,
            'signed_receiver' => $result['signed_receiver'] ?? $shipment->signed_receiver,
            'proof_of_delivery_url' => $result['proof_of_delivery_url'] ?? $shipment->proof_of_delivery_url,
        ]))->save();

        foreach (($result['events'] ?? []) as $eventData) {
            $this->appendTrackingEvent($shipment, $eventData);
        }

        return [
            'success' => true,
            'status' => 'processed',
            'message' => 'Webhook processed successfully.',
            'shipment_id' => $shipment->id,
        ];
    }

    public function normalizeCarrierStatus($carrierStatus): ?string
    {
        if (blank($carrierStatus)) {
            return null;
        }

        $normalized = strtolower(trim(str_replace(['-', ' '], '_', (string) $carrierStatus)));

        return match (true) {
            str_contains($normalized, 'prepar') => 'preparing',
            str_contains($normalized, 'label') => 'label_created',
            str_contains($normalized, 'pickup') && str_contains($normalized, 'schedule') => 'pickup_scheduled',
            str_contains($normalized, 'pick') => 'picked_up',
            str_contains($normalized, 'hub') => 'arrived_hub',
            str_contains($normalized, 'export') && str_contains($normalized, 'custom') => 'export_customs',
            str_contains($normalized, 'import') && str_contains($normalized, 'custom') => 'import_customs',
            str_contains($normalized, 'hold') => 'customs_hold',
            str_contains($normalized, 'out_for_delivery') || (str_contains($normalized, 'out') && str_contains($normalized, 'delivery')) => 'out_for_delivery',
            str_contains($normalized, 'deliver') => 'delivered',
            str_contains($normalized, 'return') => 'returned',
            str_contains($normalized, 'delay') => 'delayed',
            str_contains($normalized, 'cancel') => 'cancelled',
            str_contains($normalized, 'exception') || str_contains($normalized, 'failed') => 'exception',
            str_contains($normalized, 'transit') || str_contains($normalized, 'depart') || str_contains($normalized, 'flight') => 'in_transit',
            default => null,
        };
    }

    public function appendTrackingEvent($shipment, $eventData)
    {
        $status = $eventData['status'] ?? $this->normalizeCarrierStatus($eventData['carrier_status'] ?? null) ?? $shipment->status;
        $eventAt = isset($eventData['event_at']) ? Carbon::parse($eventData['event_at']) : now();
        $title = $eventData['title'] ?? ucwords(str_replace('_', ' ', $status));
        $city = $eventData['city'] ?? null;
        $country = $eventData['country'] ?? null;
        $location = $eventData['location'] ?? collect([$city, $country])->filter()->implode(', ');

        $exists = $shipment->events()
            ->where('status', $status)
            ->where('title', $title)
            ->where('event_at', $eventAt)
            ->exists();

        if ($exists) {
            return null;
        }

        $event = $this->b2bTradeService->addShipmentEvent(
            $shipment,
            $status,
            $eventData['created_by'] ?? null,
            $title,
            $eventData['description'] ?? null,
            $location ?: null,
            $eventAt,
            $eventData['carrier_event'] ?? null,
            $city,
            $country
        );

        $shipment->refresh();

        if (in_array($status, ['label_created', 'pickup_scheduled', 'picked_up', 'customs_hold', 'delayed', 'exception', 'out_for_delivery', 'delivered', 'returned'], true)) {
            $this->b2bNotificationService->notifyShipmentTrackingUpdate($shipment, $status);
        }

        $this->b2bAuditService->log(
            $eventData['created_by'] ?? null,
            $shipment->supplier_company_id,
            $status === 'delivered' ? 'shipment_delivered' : 'shipment_tracking_event',
            $shipment,
            'Shipment tracking event recorded.',
            [
                'status' => $status,
                'title' => $title,
            ]
        );

        return $event;
    }

    protected function recordFailure(B2BShipment $shipment, string $message, string $status): array
    {
        $shipment->forceFill($shipment->filterPersistable([
            'last_tracked_at' => now(),
            'sync_error' => $message,
        ]))->save();

        $this->b2bAuditService->log(
            null,
            $shipment->supplier_company_id,
            'shipment_sync_failure',
            $shipment,
            'Shipment tracking sync failed.',
            [
                'status' => $status,
                'error' => $message,
            ]
        );

        return [
            'success' => false,
            'status' => $status,
            'message' => $message,
            'shipment_id' => $shipment->id,
        ];
    }

    protected function resolveProvider($provider): ?B2BShippingProvider
    {
        if ($provider instanceof B2BShippingProvider) {
            return $provider;
        }

        return B2BShippingProvider::query()
            ->when(is_numeric($provider), fn ($query) => $query->where('id', (int) $provider))
            ->when(!is_numeric($provider), fn ($query) => $query->where('api_driver', $provider)->orWhere('name', $provider))
            ->first();
    }

    protected function encodeForStorage(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
