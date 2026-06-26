<?php

namespace App\Services;

use App\Models\B2BContainerEvent;
use App\Models\B2BContainerShipment;
use App\Models\B2BCustomsDocument;
use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuote;
use App\Models\B2BPort;
use App\Services\Freight\FreightForwarderManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class B2BFreightService
{
    public const FREIGHT_MODES = [
        'sea_freight',
        'air_freight',
        'rail_freight',
        'truck_freight',
        'multimodal_freight',
    ];

    public const SERVICE_TYPES = [
        'port_to_port',
        'door_to_port',
        'port_to_door',
        'door_to_door',
        'fcl',
        'lcl',
    ];

    public const CONTAINER_EVENTS = [
        'gate_in',
        'loaded_on_vessel',
        'vessel_departed',
        'transshipment_arrived',
        'transshipment_departed',
        'vessel_arrived',
        'customs_hold',
        'customs_cleared',
        'gate_out',
        'delivered',
        'delayed',
        'exception',
    ];

    public const CUSTOMS_DOCUMENT_TYPES = [
        'commercial_invoice',
        'packing_list',
        'bill_of_lading',
        'certificate_of_origin',
        'insurance_certificate',
        'inspection_certificate',
        'export_license',
        'import_license',
        'hs_code_declaration',
        'customs_clearance_document',
    ];

    public function __construct(
        protected FreightForwarderManager $manager,
        protected B2BAuditService $auditService,
        protected B2BNotificationService $notificationService,
        protected B2BFreightCostService $freightCostService
    ) {
    }

    public function requestQuote(B2BFreightQuote $quote): array
    {
        if (!$quote->forwarder) {
            return $this->failure('not_configured', 'No freight forwarder is assigned to this quote.');
        }

        $result = $this->manager->requestQuote($quote->forwarder, $this->quotePayload($quote));
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $quote->forceFill($quote->filterPersistable([
            'freight_cost' => Arr::get($result, 'freight_cost', $quote->freight_cost),
            'insurance_cost' => Arr::get($result, 'insurance_cost', $quote->insurance_cost),
            'customs_estimate' => Arr::get($result, 'customs_estimate', $quote->customs_estimate),
            'total_cost' => Arr::get($result, 'total_cost', $quote->total_cost),
            'currency' => Arr::get($result, 'currency', $quote->currency),
            'estimated_days' => Arr::get($result, 'estimated_days', $quote->estimated_days),
            'status' => 'submitted',
            'request_payload' => Arr::get($result, 'request_payload'),
            'response_payload' => $this->encode(Arr::get($result, 'response_payload')),
        ]))->save();

        $this->freightCostService->syncDefaultCostsFromQuote($quote->fresh());
        $this->notificationService->notifyFreightQuoteSubmitted($quote);

        return [
            'success' => true,
            'status' => 'submitted',
            'message' => $result['message'] ?? 'Freight quote submitted successfully.',
            'quote_id' => $quote->id,
        ];
    }

    public function createContainerShipment(B2BFreightQuote $quote, array $payload = []): array
    {
        $quote->loadMissing('forwarder');

        if (!$quote->forwarder) {
            return $this->failure('not_configured', 'No freight forwarder is assigned to this quote.');
        }

        $result = $this->manager->createBooking($quote->forwarder, $quote, $payload);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $shipment = B2BContainerShipment::create((new B2BContainerShipment())->filterPersistable([
            'freight_quote_id' => $quote->id,
            'shipment_id' => $quote->shipment_id,
            'forwarder_id' => $quote->forwarder_id,
            'booking_number' => Arr::get($result, 'booking_number'),
            'bill_of_lading_number' => Arr::get($result, 'bill_of_lading_number'),
            'container_number' => Arr::get($result, 'container_number'),
            'seal_number' => Arr::get($result, 'seal_number'),
            'vessel_name' => Arr::get($result, 'vessel_name'),
            'voyage_number' => Arr::get($result, 'voyage_number'),
            'origin_port_id' => $quote->origin_port_id,
            'destination_port_id' => $quote->destination_port_id,
            'etd' => Arr::get($result, 'etd'),
            'eta' => Arr::get($result, 'eta'),
            'status' => Arr::get($result, 'status', 'booked'),
            'current_location' => Arr::get($result, 'current_location'),
            'source_provider' => $quote->forwarder->name,
            'tracking_reference' => Arr::get($result, 'container_number') ?: Arr::get($result, 'bill_of_lading_number') ?: Arr::get($result, 'booking_number'),
            'total_freight_cost' => $quote->total_cost_base_currency ?: $quote->total_cost,
            'landed_cost_total' => $quote->landed_cost_total,
            'request_payload' => Arr::get($result, 'request_payload'),
            'last_response' => $this->encode(Arr::get($result, 'response_payload')),
            'last_synced_at' => now(),
        ]));

        $quote->update($quote->filterPersistable(['status' => 'selected']));

        $this->appendContainerEvent($shipment, [
            'event_type' => 'gate_in',
            'description' => 'Container shipment booked.',
            'event_at' => now()->toIso8601String(),
            'source_provider' => $quote->forwarder->name,
        ]);

        $this->notificationService->notifyContainerBooked($shipment);

        return [
            'success' => true,
            'status' => 'booked',
            'message' => $result['message'] ?? 'Container shipment booked successfully.',
            'container_shipment_id' => $shipment->id,
        ];
    }

    public function syncContainerShipment(B2BContainerShipment $shipment): array
    {
        $shipment->loadMissing('forwarder');

        if (!$shipment->forwarder) {
            return $this->failure('not_configured', 'No freight forwarder is assigned to this container shipment.');
        }

        $result = $this->manager->trackContainer($shipment->forwarder, $shipment);
        if (!($result['success'] ?? false)) {
            $shipment->forceFill($shipment->filterPersistable([
                'sync_error' => $result['message'] ?? 'Freight sync failed.',
                'last_synced_at' => now(),
            ]))->save();

            return $result;
        }

        $shipment->forceFill($shipment->filterPersistable([
            'status' => Arr::get($result, 'status', $shipment->status),
            'eta' => Arr::get($result, 'eta', $shipment->eta),
            'ata' => Arr::get($result, 'ata', $shipment->ata),
            'current_location' => Arr::get($result, 'current_location', $shipment->current_location),
            'sync_error' => null,
            'last_response' => $this->encode(Arr::get($result, 'response_payload')),
            'last_synced_at' => now(),
        ]))->save();

        foreach (Arr::get($result, 'events', []) as $event) {
            $this->appendContainerEvent($shipment, $event);
        }

        if (in_array($shipment->status, ['customs_hold', 'customs_cleared', 'delivered', 'delayed'], true)) {
            $this->notificationService->notifyContainerStatusUpdate($shipment, $shipment->status);
        }

        return [
            'success' => true,
            'status' => 'synced',
            'message' => $result['message'] ?? 'Container shipment synced successfully.',
            'container_shipment_id' => $shipment->id,
        ];
    }

    public function handleWebhook(B2BFreightForwarder $forwarder, array $payload, ?string $signature = null): array
    {
        $result = $this->manager->webhook($forwarder, $payload, $signature);
        if (!($result['success'] ?? false)) {
            return $result;
        }

        $shipment = B2BContainerShipment::query()
            ->where('forwarder_id', $forwarder->id)
            ->when(Arr::get($result, 'container_number'), fn ($query, $value) => $query->where('container_number', $value))
            ->when(!Arr::get($result, 'container_number') && Arr::get($result, 'bill_of_lading_number'), fn ($query, $value) => $query->where('bill_of_lading_number', $value))
            ->when(!Arr::get($result, 'container_number') && !Arr::get($result, 'bill_of_lading_number') && Arr::get($result, 'booking_number'), fn ($query, $value) => $query->where('booking_number', $value))
            ->latest('id')
            ->first();

        if (!$shipment) {
            return $this->failure('shipment_not_found', 'No container shipment matched the incoming webhook payload.');
        }

        $shipment->forceFill($shipment->filterPersistable([
            'status' => Arr::get($result, 'status', $shipment->status),
            'current_location' => Arr::get($result, 'current_location', $shipment->current_location),
            'last_response' => $this->encode(Arr::get($result, 'response_payload')),
            'last_synced_at' => now(),
            'sync_error' => null,
        ]))->save();

        foreach (Arr::get($result, 'events', []) as $event) {
            $this->appendContainerEvent($shipment, $event);
        }

        $this->notificationService->notifyContainerStatusUpdate($shipment, $shipment->status);

        return [
            'success' => true,
            'status' => 'processed',
            'message' => 'Freight webhook processed successfully.',
            'container_shipment_id' => $shipment->id,
        ];
    }

    public function appendContainerEvent(B2BContainerShipment $shipment, array $eventData): ?B2BContainerEvent
    {
        $eventType = Arr::get($eventData, 'event_type');
        if (!$eventType) {
            return null;
        }

        $eventAt = Arr::get($eventData, 'event_at') ? Carbon::parse(Arr::get($eventData, 'event_at')) : now();
        $portId = $this->resolvePortId(Arr::get($eventData, 'port_id'), Arr::get($eventData, 'port_location'));

        $exists = $shipment->events()
            ->where('event_type', $eventType)
            ->where('event_at', $eventAt)
            ->exists();

        if ($exists) {
            return null;
        }

        $event = $shipment->events()->create((new B2BContainerEvent())->filterPersistable([
            'event_type' => $eventType,
            'port_location' => Arr::get($eventData, 'port_location'),
            'port_id' => $portId,
            'vessel_name' => Arr::get($eventData, 'vessel_name', $shipment->vessel_name),
            'voyage_number' => Arr::get($eventData, 'voyage_number', $shipment->voyage_number),
            'description' => Arr::get($eventData, 'description'),
            'source_provider' => Arr::get($eventData, 'source_provider', $shipment->source_provider),
            'event_at' => $eventAt,
        ]));

        $shipment->forceFill($shipment->filterPersistable([
            'status' => $eventType,
            'current_location' => $event->port?->name ?: $event->port_location ?: $shipment->current_location,
            'current_location_port_id' => $event->port_id ?: $shipment->current_location_port_id,
            'vessel_name' => $event->vessel_name ?: $shipment->vessel_name,
            'voyage_number' => $event->voyage_number ?: $shipment->voyage_number,
            'ata' => $eventType === 'delivered' ? $eventAt : $shipment->ata,
        ]))->save();

        return $event;
    }

    public function storeCustomsDocument(Model $documentable, Request $request, int $uploadedBy, ?int $companyId): B2BCustomsDocument
    {
        $file = $request->file('file');
        $fileName = time() . '_customs_document_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $filePath = 'uploads/b2b_customs_documents/' . $fileName;
        Storage::disk('local')->putFileAs('uploads/b2b_customs_documents', $file, $fileName);

        return $documentable->customsDocuments()->create((new B2BCustomsDocument())->filterPersistable([
            'uploaded_by' => $uploadedBy,
            'company_id' => $companyId,
            'document_type' => $request->string('document_type'),
            'title' => $request->input('title'),
            'file_path' => $filePath,
            'issued_at' => $request->input('issued_at'),
            'expires_at' => $request->input('expires_at'),
            'notes' => $request->input('notes'),
        ]));
    }

    public function deleteCustomsDocument(B2BCustomsDocument $document): void
    {
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();
    }

    public function buildContainerTimeline(B2BContainerShipment $shipment): Collection
    {
        $shipment->loadMissing(['events.port', 'originPort', 'destinationPort']);

        return $shipment->events->map(fn ($event) => [
            'date' => $event->event_at,
            'port' => $event->port?->name ?: $event->port_location,
            'event' => $event->event_type,
            'vessel' => $event->vessel_name,
            'voyage' => $event->voyage_number,
            'description' => $event->description,
            'source_provider' => $event->source_provider,
        ])->values();
    }

    protected function quotePayload(B2BFreightQuote $quote): array
    {
        return [
            'origin_country' => $quote->origin_country,
            'origin_port_code' => $quote->originPort?->code,
            'destination_country' => $quote->destination_country,
            'destination_port_code' => $quote->destinationPort?->code,
            'freight_mode' => $quote->freight_mode,
            'service_type' => $quote->service_type,
            'incoterm' => $quote->incoterm,
            'container_type' => $quote->container_type,
            'container_count' => $quote->container_count,
            'cargo_weight' => $quote->cargo_weight,
            'cargo_volume' => $quote->cargo_volume,
            'hs_code' => $quote->hs_code,
            'goods_description' => $quote->goods_description,
            'pickup_address' => $quote->pickup_address,
            'delivery_address' => $quote->delivery_address,
        ];
    }

    protected function resolvePortId(?int $portId, ?string $location): ?int
    {
        if ($portId) {
            return $portId;
        }

        if (blank($location)) {
            return null;
        }

        return B2BPort::query()->where('name', $location)->orWhere('code', $location)->value('id');
    }

    protected function encode(mixed $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        return is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    protected function failure(string $status, string $message): array
    {
        return [
            'success' => false,
            'status' => $status,
            'message' => $message,
        ];
    }
}
