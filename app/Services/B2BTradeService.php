<?php

namespace App\Services;

use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BSampleOrder;
use App\Models\B2BShipment;
use App\Models\B2BShipmentEvent;
use App\Models\B2BTradeDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class B2BTradeService
{
    public const INCOTERMS = ['EXW', 'FOB', 'CIF', 'DAP', 'DDP', 'FCA', 'CPT', 'CIP'];
    public const TRANSPORT_MODES = ['air_freight', 'sea_freight', 'rail', 'truck', 'courier'];
    public const DOCUMENT_TYPES = [
        'commercial_invoice',
        'packing_list',
        'certificate_of_origin',
        'bill_of_lading',
        'air_waybill',
        'inspection_report',
        'insurance_certificate',
        'quality_certificate',
        'export_license',
        'import_license',
    ];
    public const SHIPMENT_STATUSES = [
        'label_created',
        'pickup_scheduled',
        'preparing',
        'picked_up',
        'arrived_hub',
        'export_customs',
        'customs_hold',
        'in_transit',
        'import_customs',
        'out_for_delivery',
        'delivered',
        'exception',
        'delayed',
        'returned',
        'cancelled',
    ];

    public function __construct(
        protected B2BTradeDocumentFeeService $tradeDocumentFeeService
    ) {
    }

    public function buildTradeTimelineForPurchaseOrder(B2BPurchaseOrder $purchaseOrder): Collection
    {
        $purchaseOrder->loadMissing(['rfq', 'quotation', 'proformaInvoices', 'shipments.events']);

        $items = collect();

        if ($purchaseOrder->rfq) {
            $items->push([
                'label' => 'RFQ',
                'status' => ucfirst($purchaseOrder->rfq->status),
                'date' => $purchaseOrder->rfq->created_at,
                'detail' => $purchaseOrder->rfq->title,
            ]);
        }

        if ($purchaseOrder->quotation) {
            $items->push([
                'label' => 'Quotation',
                'status' => ucfirst($purchaseOrder->quotation->status),
                'date' => $purchaseOrder->quotation->created_at,
                'detail' => $purchaseOrder->quotation->price . ' ' . $purchaseOrder->quotation->currency,
            ]);
        }

        $items->push([
            'label' => 'Purchase Order',
            'status' => ucfirst($purchaseOrder->status),
            'date' => $purchaseOrder->sent_at ?: $purchaseOrder->created_at,
            'detail' => $purchaseOrder->po_number,
        ]);

        $invoice = $purchaseOrder->proformaInvoices->sortByDesc('created_at')->first();
        if ($invoice) {
            $items->push([
                'label' => 'Proforma Invoice',
                'status' => ucfirst($invoice->status),
                'date' => $invoice->sent_at ?: $invoice->created_at,
                'detail' => $invoice->invoice_number,
            ]);

            if ($invoice->usesEscrow()) {
                $items->push([
                    'label' => 'Escrow',
                    'status' => $invoice->escrowStatusLabel(),
                    'date' => $invoice->escrow_released_at
                        ?: $invoice->escrow_refunded_at
                        ?: $invoice->escrow_disputed_at
                        ?: $invoice->escrow_funded_at
                        ?: $invoice->accepted_at
                        ?: $invoice->created_at,
                    'detail' => $invoice->escrow_payment_reference ?: $invoice->invoice_number,
                ]);
            }
        }

        $shipment = $purchaseOrder->shipments->sortByDesc('created_at')->first();
        if ($shipment) {
            $items->push([
                'label' => 'Shipping',
                'status' => ucwords(str_replace('_', ' ', $shipment->status)),
                'date' => $shipment->created_at,
                'detail' => $shipment->shipment_number,
            ]);

            foreach ($shipment->events as $event) {
                $items->push([
                    'label' => 'Shipment Event',
                    'status' => ucwords(str_replace('_', ' ', $event->status)),
                    'date' => $event->event_at,
                    'detail' => $event->title ?: $event->description,
                ]);
            }
        }

        if ($shipment?->status === 'delivered') {
            $items->push([
                'label' => 'Delivered',
                'status' => 'Delivered',
                'date' => $shipment->delivered_at ?: $shipment->updated_at,
                'detail' => $shipment->tracking_number,
            ]);
        }

        if ($purchaseOrder->status === 'completed') {
            $items->push([
                'label' => 'Completed',
                'status' => 'Completed',
                'date' => $purchaseOrder->completed_at ?: $purchaseOrder->updated_at,
                'detail' => $purchaseOrder->po_number,
            ]);
        }

        return $items->filter(fn ($item) => !empty($item['date']))->sortBy('date')->values();
    }

    public function storeTradeDocument(Model $documentable, Request $request, int $uploadedBy, ?int $companyId): B2BTradeDocument
    {
        $file = $request->file('file');
        $fileName = time() . '_trade_document_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $filePath = 'uploads/b2b_trade_documents/' . $fileName;

        Storage::disk('local')->putFileAs('uploads/b2b_trade_documents', $file, $fileName);

        $payload = $this->tradeDocumentFeeService->applyToPayload([
            'uploaded_by' => $uploadedBy,
            'company_id' => $companyId,
            'document_type' => (string) $request->string('document_type'),
            'title' => $request->input('title'),
            'file_path' => $filePath,
            'issued_at' => $request->input('issued_at'),
            'expires_at' => $request->input('expires_at'),
            'notes' => $request->input('notes'),
            'fee_base_amount' => $this->resolveTradeDocumentFeeBaseAmount($documentable),
        ]);

        return $documentable->documents()->create($payload);
    }

    public function deleteTradeDocument(B2BTradeDocument $document): void
    {
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();
    }

    protected function resolveTradeDocumentFeeBaseAmount(Model $documentable): float
    {
        if ($documentable instanceof B2BPurchaseOrder) {
            return (float) ($documentable->total_amount ?? 0);
        }

        if ($documentable instanceof B2BProformaInvoice) {
            return (float) ($documentable->grand_total ?? 0);
        }

        if ($documentable instanceof B2BSampleOrder) {
            return (float) ($documentable->total_amount ?? 0);
        }

        if ($documentable instanceof B2BShipment) {
            return (float) ($documentable->purchaseOrder?->total_amount ?? 0);
        }

        return 0;
    }

    public function addShipmentEvent(
        B2BShipment $shipment,
        string $status,
        ?int $createdBy,
        ?string $title = null,
        ?string $description = null,
        ?string $location = null,
        $eventAt = null,
        ?string $carrierEvent = null,
        ?string $city = null,
        ?string $country = null
    ): B2BShipmentEvent
    {
        $eventModel = new B2BShipmentEvent();

        $event = $shipment->events()->create($eventModel->filterPersistable([
            'created_by' => $createdBy,
            'status' => $status,
            'carrier_event' => $carrierEvent,
            'title' => $title ?: ucwords(str_replace('_', ' ', $status)),
            'description' => $description,
            'location' => $location,
            'city' => $city,
            'country' => $country,
            'event_at' => $eventAt ?: now(),
        ]));

        $updates = ['status' => $status];
        if ($status === 'in_transit' && !$shipment->actual_departure_at) {
            $updates['actual_departure_at'] = $event->event_at;
        }
        if ($status === 'delivered') {
            $updates['delivered_at'] = $event->event_at;
        }
        $shipment->update($updates);

        if ($shipment->purchaseOrder && $status === 'delivered' && $shipment->purchaseOrder->status === 'accepted') {
            $latestInvoice = $shipment->purchaseOrder->proformaInvoices()->latest('id')->first();

            if (!$latestInvoice || !$latestInvoice->usesEscrow() || in_array($latestInvoice->escrow_status, ['released', 'refunded'], true)) {
                $shipment->purchaseOrder->update(['status' => 'completed', 'completed_at' => now()]);
            }
        }

        if ($shipment->sampleOrder && $status === 'delivered' && $shipment->sampleOrder->status !== 'completed') {
            $shipment->sampleOrder->update(['status' => 'delivered']);
        }

        return $event;
    }

    public function buildSampleOrderTimeline(B2BSampleOrder $sampleOrder): Collection
    {
        $sampleOrder->loadMissing(['shippingQuotes', 'shipment.events']);

        $items = collect([
            [
                'label' => 'Sample Request',
                'status' => ucfirst($sampleOrder->status),
                'date' => $sampleOrder->requested_at ?: $sampleOrder->created_at,
                'detail' => $sampleOrder->sample_number,
            ],
        ]);

        foreach ($sampleOrder->shippingQuotes as $quote) {
            $items->push([
                'label' => 'Shipping Quote',
                'status' => ucfirst($quote->status),
                'date' => $quote->created_at,
                'detail' => $quote->quote_number,
            ]);
        }

        if ($sampleOrder->paid_at) {
            $items->push([
                'label' => 'Payment',
                'status' => 'Paid',
                'date' => $sampleOrder->paid_at,
                'detail' => $sampleOrder->payment_reference,
            ]);
        }

        if ($sampleOrder->shipment) {
            $items->push([
                'label' => 'Shipment',
                'status' => ucwords(str_replace('_', ' ', $sampleOrder->shipment->status)),
                'date' => $sampleOrder->shipment->created_at,
                'detail' => $sampleOrder->shipment->shipment_number,
            ]);
        }

        return $items->sortBy('date')->values();
    }
}
