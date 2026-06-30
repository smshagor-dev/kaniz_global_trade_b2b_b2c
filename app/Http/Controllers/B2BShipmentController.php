<?php

namespace App\Http\Controllers;

use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BSampleOrder;
use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use App\Models\B2BShippingQuote;
use App\Services\B2BAuditService;
use App\Services\B2BCarrierService;
use App\Services\B2BCompanyService;
use App\Services\B2BIntegrationManagementService;
use App\Services\B2BPermissionService;
use App\Services\B2BShipmentTrackingService;
use App\Services\B2BTradeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class B2BShipmentController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPermissionService $b2bPermissionService,
        protected B2BTradeService $b2bTradeService,
        protected B2BCarrierService $b2bCarrierService,
        protected B2BShipmentTrackingService $shipmentTrackingService,
        protected B2BAuditService $b2bAuditService,
        protected B2BIntegrationManagementService $integrationService
    ) {
    }

    public function buyerIndex()
    {
        $company = $this->getBuyerCompany();
        $shipments = B2BShipment::with(['purchaseOrder', 'sampleOrder', 'shippingProvider'])
            ->where('buyer_company_id', $company->id)
            ->latest()
            ->paginate(15);

        return view('b2b.shipments.index', compact('shipments'));
    }

    public function buyerShow($id)
    {
        $company = $this->getBuyerCompany();
        $shipment = B2BShipment::with(['purchaseOrder', 'proformaInvoice', 'sampleOrder', 'shippingProvider', 'events', 'documents'])
            ->where('buyer_company_id', $company->id)
            ->findOrFail($id);

        return view('b2b.shipments.show', compact('shipment'));
    }

    public function supplierIndex()
    {
        $company = $this->getSupplierCompany();
        $shipments = B2BShipment::with(['purchaseOrder', 'sampleOrder', 'shippingProvider'])
            ->where('supplier_company_id', $company->id)
            ->latest()
            ->paginate(15);

        return view('seller.b2b.shipments.index', compact('shipments'));
    }

    public function create(Request $request)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canManageFreight(Auth::id(), $company->id), 403);

        $purchaseOrder = $request->purchase_order_id
            ? B2BPurchaseOrder::where('supplier_company_id', $company->id)->findOrFail($request->purchase_order_id)
            : null;
        $sampleOrder = $request->sample_order_id
            ? B2BSampleOrder::where('supplier_company_id', $company->id)->findOrFail($request->sample_order_id)
            : null;
        $proformaInvoice = $request->proforma_invoice_id
            ? B2BProformaInvoice::where('supplier_company_id', $company->id)->findOrFail($request->proforma_invoice_id)
            : null;

        if ($purchaseOrder && !in_array($purchaseOrder->status, ['accepted', 'completed'], true)) {
            flash(translate('Purchase order must be accepted before shipment can be created.'))->warning();

            return redirect()->route('seller.b2b.purchase-orders.show', $purchaseOrder->id);
        }

        if ($sampleOrder && !in_array($sampleOrder->status, ['paid', 'in_shipment'], true)) {
            flash(translate('Sample order must be paid before shipment can be created.'))->warning();

            return redirect()->route('seller.b2b.sample-orders.show', $sampleOrder->id);
        }

        if ($proformaInvoice && !in_array($proformaInvoice->status, ['accepted'], true)) {
            flash(translate('Proforma invoice must be accepted before shipment can be created.'))->warning();

            return redirect()->route('seller.b2b.proforma-invoices.show', $proformaInvoice->id);
        }

        $shippingQuotes = B2BShippingQuote::where('supplier_company_id', $company->id)
            ->when($purchaseOrder, fn ($query) => $query->where('purchase_order_id', $purchaseOrder->id))
            ->when($sampleOrder, fn ($query) => $query->where('sample_order_id', $sampleOrder->id))
            ->with('shippingProvider')
            ->get();

        return view('seller.b2b.shipments.create', [
            'purchaseOrder' => $purchaseOrder,
            'sampleOrder' => $sampleOrder,
            'proformaInvoice' => $proformaInvoice,
            'shippingQuotes' => $shippingQuotes,
            'providers' => B2BShippingProvider::where('is_active', true)->orderBy('name')->get(),
            'transportModes' => B2BTradeService::TRANSPORT_MODES,
            'incoterms' => B2BTradeService::INCOTERMS,
            'providerTypes' => B2BShippingProvider::PROVIDER_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canManageFreight(Auth::id(), $company->id), 403);

        $data = $request->validate([
            'purchase_order_id' => 'nullable|exists:b2b_purchase_orders,id',
            'proforma_invoice_id' => 'nullable|exists:b2b_proforma_invoices,id',
            'sample_order_id' => 'nullable|exists:b2b_sample_orders,id',
            'shipping_quote_id' => 'nullable|exists:b2b_shipping_quotes,id',
            'shipping_provider_id' => 'nullable|exists:b2b_shipping_providers,id',
            'transport_mode' => 'required|in:' . implode(',', B2BTradeService::TRANSPORT_MODES),
            'incoterm' => 'required|in:' . implode(',', B2BTradeService::INCOTERMS),
            'tracking_number' => 'nullable|string|max:120',
            'carrier_reference' => 'nullable|string|max:120',
            'carrier_service' => 'nullable|string|max:120',
            'service_type' => 'nullable|string|max:120',
            'delivery_priority' => 'nullable|string|max:40',
            'tracking_url' => 'nullable|url|max:255',
            'live_tracking_enabled' => 'nullable|boolean',
            'currency' => 'nullable|string|max:20',
            'declared_value' => 'nullable|numeric|min:0',
            'insurance_amount' => 'nullable|numeric|min:0',
            'total_weight' => 'nullable|numeric|min:0.001',
            'package_length' => 'nullable|numeric|min:0',
            'package_width' => 'nullable|numeric|min:0',
            'package_height' => 'nullable|numeric|min:0',
            'origin_country' => 'nullable|string|max:100',
            'destination_country' => 'nullable|string|max:100',
            'estimated_departure' => 'nullable|date',
            'estimated_arrival' => 'nullable|date|after_or_equal:estimated_departure',
            'carrier_payload' => 'nullable|array',
            'generate_with_carrier' => 'nullable|boolean',
            'pickup_requested' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $purchaseOrder = !empty($data['purchase_order_id']) ? B2BPurchaseOrder::where('supplier_company_id', $company->id)->findOrFail($data['purchase_order_id']) : null;
        $sampleOrder = !empty($data['sample_order_id']) ? B2BSampleOrder::where('supplier_company_id', $company->id)->findOrFail($data['sample_order_id']) : null;
        $proformaInvoice = !empty($data['proforma_invoice_id']) ? B2BProformaInvoice::where('supplier_company_id', $company->id)->findOrFail($data['proforma_invoice_id']) : null;
        $shippingQuote = !empty($data['shipping_quote_id']) ? B2BShippingQuote::where('supplier_company_id', $company->id)->findOrFail($data['shipping_quote_id']) : null;
        $shippingProviderId = $data['shipping_provider_id'] ?? $shippingQuote?->shipping_provider_id;
        $shippingProvider = $shippingProviderId ? B2BShippingProvider::find($shippingProviderId) : null;

        if ($purchaseOrder && !in_array($purchaseOrder->status, ['accepted', 'completed'], true)) {
            flash(translate('Purchase order must be accepted before shipment can be created.'))->warning();

            return back();
        }

        if ($sampleOrder && !in_array($sampleOrder->status, ['paid', 'in_shipment'], true)) {
            flash(translate('Shipment can only be created after the sample order is paid.'))->warning();
            return back();
        }

        if ($proformaInvoice && !in_array($proformaInvoice->status, ['accepted'], true)) {
            flash(translate('Proforma invoice must be accepted before shipment can be created.'))->warning();

            return back();
        }

        if ($shippingQuote && $shippingQuote->status !== 'selected') {
            flash(translate('Please use the selected shipping quote to create the shipment.'))->warning();

            return back();
        }

        if ($sampleOrder && !$shippingQuote && $sampleOrder->shippingQuotes()->where('status', 'selected')->exists()) {
            flash(translate('Please select the approved shipping quote before creating the shipment.'))->warning();

            return back();
        }

        $buyerCompanyId = $purchaseOrder?->buyer_company_id ?? $sampleOrder?->buyer_company_id ?? $proformaInvoice?->buyer_company_id;
        abort_if(!$buyerCompanyId, 422);

        $shipment = B2BShipment::create((new B2BShipment())->filterPersistable([
            'shipment_number' => $this->nextShipmentNumber(),
            'purchase_order_id' => $purchaseOrder?->id,
            'proforma_invoice_id' => $proformaInvoice?->id,
            'sample_order_id' => $sampleOrder?->id,
            'shipping_quote_id' => $shippingQuote?->id,
            'supplier_company_id' => $company->id,
            'buyer_company_id' => $buyerCompanyId,
            'shipping_provider_id' => $shippingProviderId,
            'created_by' => Auth::id(),
            'transport_mode' => $data['transport_mode'],
            'incoterm' => $data['incoterm'],
            'tracking_number' => $data['tracking_number'] ?? null,
            'carrier_reference' => $data['carrier_reference'] ?? null,
            'carrier_service' => $data['carrier_service'] ?? null,
            'service_type' => $data['service_type'] ?? null,
            'delivery_priority' => $data['delivery_priority'] ?? null,
            'tracking_url' => $data['tracking_url'] ?? null,
            'live_tracking_enabled' => $request->boolean('live_tracking_enabled') && $shippingProvider?->isApiProvider(),
            'currency' => $data['currency'] ?? 'USD',
            'declared_value' => $data['declared_value'] ?? null,
            'insurance_amount' => $data['insurance_amount'] ?? null,
            'total_weight' => $data['total_weight'] ?? null,
            'package_length' => $data['package_length'] ?? null,
            'package_width' => $data['package_width'] ?? null,
            'package_height' => $data['package_height'] ?? null,
            'origin_country' => $data['origin_country'] ?? $shippingQuote?->origin_country,
            'destination_country' => $data['destination_country'] ?? $shippingQuote?->destination_country,
            'estimated_departure' => $data['estimated_departure'] ?? null,
            'estimated_arrival' => $data['estimated_arrival'] ?? null,
            'status' => 'preparing',
            'notes' => $data['notes'] ?? null,
            'carrier_payload' => $data['carrier_payload'] ?? null,
        ]));

        $this->b2bTradeService->addShipmentEvent($shipment, 'preparing', Auth::id(), translate('Shipment created'), $shipment->notes);
        $this->b2bAuditService->log(Auth::id(), $company->id, 'shipment_created', $shipment, 'Shipment created.');
        $this->shipmentTrackingService->syncShipment($shipment->id);
        app(\App\Services\B2BNotificationService::class)->notifyShipmentTrackingUpdate($shipment->fresh(['purchaseOrder', 'sampleOrder', 'buyerCompany', 'supplierCompany']), 'preparing');

        if ($request->boolean('generate_with_carrier') && $shippingProvider?->isApiProvider()) {
            $result = $this->b2bCarrierService->createShipment($shipment, array_merge(
                $data['carrier_payload'] ?? [],
                ['pickup_requested' => $request->boolean('pickup_requested')]
            ), Auth::id());

            flash(translate($result['message'] ?? 'Carrier shipment request completed.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();
        }

        if ($sampleOrder && $sampleOrder->status === 'paid') {
            $sampleOrder->update(['status' => 'in_shipment']);
        }

        flash(translate('Shipment created successfully.'))->success();

        return redirect()->route('seller.b2b.shipments.show', $shipment->id);
    }

    public function supplierShow($id)
    {
        $company = $this->getSupplierCompany();
        $shipment = B2BShipment::with(['purchaseOrder', 'proformaInvoice', 'sampleOrder', 'shippingProvider', 'events', 'documents'])
            ->where('supplier_company_id', $company->id)
            ->findOrFail($id);

        return view('seller.b2b.shipments.show', compact('shipment'));
    }

    public function updateStatus(Request $request, $id)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canManageFreight(Auth::id(), $company->id), 403);

        $shipment = B2BShipment::where('supplier_company_id', $company->id)->findOrFail($id);
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', B2BTradeService::SHIPMENT_STATUSES),
            'title' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'event_at' => 'nullable|date',
        ]);

        $this->b2bTradeService->addShipmentEvent(
            $shipment,
            $data['status'],
            Auth::id(),
            $data['title'] ?? null,
            $data['description'] ?? null,
            $data['location'] ?? null,
            $data['event_at'] ?? null
        );
        $this->shipmentTrackingService->syncShipment($shipment->id);
        app(\App\Services\B2BNotificationService::class)->notifyShipmentTrackingUpdate($shipment->fresh(['purchaseOrder', 'sampleOrder', 'buyerCompany', 'supplierCompany']), $data['status']);

        flash(translate('Shipment status updated successfully.'))->success();

        return back();
    }

    public function updateTracking(Request $request, $id)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canManageFreight(Auth::id(), $company->id), 403);

        $shipment = B2BShipment::where('supplier_company_id', $company->id)->findOrFail($id);
        $data = $request->validate([
            'shipping_provider_id' => 'nullable|exists:b2b_shipping_providers,id',
            'tracking_number' => 'nullable|string|max:120',
            'carrier_reference' => 'nullable|string|max:120',
            'carrier_service' => 'nullable|string|max:120',
            'tracking_url' => 'nullable|url|max:255',
            'live_tracking_enabled' => 'nullable|boolean',
        ]);

        $providerId = $data['shipping_provider_id'] ?? $shipment->shipping_provider_id;
        $provider = $providerId ? B2BShippingProvider::find($providerId) : null;
        $liveTrackingEnabled = $request->boolean('live_tracking_enabled') && $provider?->isApiProvider();

        $changes = [];
        foreach ([
            'shipping_provider_id' => $providerId,
            'tracking_number' => $data['tracking_number'] ?? null,
            'carrier_reference' => $data['carrier_reference'] ?? null,
            'carrier_service' => $data['carrier_service'] ?? null,
            'tracking_url' => $data['tracking_url'] ?? null,
            'live_tracking_enabled' => $liveTrackingEnabled,
        ] as $field => $value) {
            if ($shipment->{$field} != $value) {
                $changes[$field] = $value;
            }
        }

        $shipment->forceFill($shipment->filterPersistable([
            'shipping_provider_id' => $providerId,
            'tracking_number' => $data['tracking_number'] ?? null,
            'carrier_reference' => $data['carrier_reference'] ?? null,
            'carrier_service' => $data['carrier_service'] ?? null,
            'tracking_url' => $data['tracking_url'] ?? null,
            'live_tracking_enabled' => $liveTrackingEnabled,
            'sync_error' => null,
        ]))->save();

        if (array_key_exists('shipping_provider_id', $changes)) {
            $this->b2bAuditService->log(Auth::id(), $company->id, 'shipment_provider_assigned', $shipment, 'Shipping provider assigned.', [
                'shipping_provider_id' => $providerId,
            ]);
        }

        if (array_key_exists('tracking_number', $changes)) {
            $this->b2bAuditService->log(Auth::id(), $company->id, 'shipment_tracking_number_updated', $shipment, 'Tracking number updated.');
        }

        if (array_key_exists('live_tracking_enabled', $changes)) {
            $this->b2bAuditService->log(
                Auth::id(),
                $company->id,
                $liveTrackingEnabled ? 'shipment_live_tracking_enabled' : 'shipment_live_tracking_disabled',
                $shipment,
                $liveTrackingEnabled ? 'Live tracking enabled.' : 'Live tracking disabled.'
            );
        }

        flash(translate('Shipment tracking details updated successfully.'))->success();

        return back();
    }

    public function sync($id)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canManageFreight(Auth::id(), $company->id), 403);

        $shipment = B2BShipment::where('supplier_company_id', $company->id)->findOrFail($id);
        $result = $this->shipmentTrackingService->syncShipment($shipment->id);
        if ($shipment->shippingProvider) {
            $this->integrationService->touchLastSync($shipment->shippingProvider);
        }

        flash(translate($result['message'] ?? 'Shipment sync completed.'))->{$result['success'] ? 'success' : 'warning'}();

        return back();
    }

    public function createCarrierShipment(Request $request, $id)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canManageFreight(Auth::id(), $company->id), 403);

        $shipment = B2BShipment::where('supplier_company_id', $company->id)->findOrFail($id);
        $result = $this->b2bCarrierService->createShipment($shipment, $request->input('carrier_payload', []), Auth::id());

        return $this->carrierActionResponse($result);
    }

    public function requestPickup(Request $request, $id)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canManageFreight(Auth::id(), $company->id), 403);

        $shipment = B2BShipment::where('supplier_company_id', $company->id)->findOrFail($id);
        $result = $this->b2bCarrierService->requestPickup($shipment, $request->input('carrier_payload', []), Auth::id());

        return $this->carrierActionResponse($result);
    }

    public function generateLabel(Request $request, $id)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canManageFreight(Auth::id(), $company->id), 403);

        $shipment = B2BShipment::where('supplier_company_id', $company->id)->findOrFail($id);
        $result = $this->b2bCarrierService->createLabel($shipment, $request->input('carrier_payload', []), Auth::id());

        return $this->carrierActionResponse($result);
    }

    public function cancelCarrierShipment(Request $request, $id)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->canManageFreight(Auth::id(), $company->id), 403);

        $shipment = B2BShipment::where('supplier_company_id', $company->id)->findOrFail($id);
        $result = $this->b2bCarrierService->cancelShipment($shipment, $request->input('carrier_payload', []), Auth::id());

        return $this->carrierActionResponse($result);
    }

    public function adminIndex()
    {
        $shipments = B2BShipment::with(['purchaseOrder', 'sampleOrder', 'buyerCompany', 'supplierCompany', 'shippingProvider'])
            ->latest()
            ->paginate(20);

        return view('backend.b2b.shipments.index', compact('shipments'));
    }

    public function adminShow($id)
    {
        $shipment = B2BShipment::with(['purchaseOrder', 'proformaInvoice', 'sampleOrder', 'buyerCompany', 'supplierCompany', 'shippingProvider', 'events', 'documents'])
            ->findOrFail($id);

        return view('backend.b2b.shipments.show', compact('shipment'));
    }

    public function adminSync($id)
    {
        $shipment = B2BShipment::findOrFail($id);
        $result = $this->shipmentTrackingService->syncShipment($shipment->id);
        if ($shipment->shippingProvider) {
            $this->integrationService->touchLastSync($shipment->shippingProvider);
        }

        flash(translate($result['message'] ?? 'Shipment sync completed.'))->{$result['success'] ? 'success' : 'warning'}();

        return back();
    }

    public function handleWebhook(Request $request, $provider, ?string $channel = null)
    {
        $signature = $request->header('X-Webhook-Signature')
            ?: $request->header('X-Signature')
            ?: $request->input('signature');

        $result = $this->shipmentTrackingService->handleWebhook($provider, $request->all(), $signature);

        if (($result['status'] ?? null) === 'invalid_signature') {
            return response()->json(['message' => $result['message']], 403);
        }

        if (!($result['success'] ?? false)) {
            Log::info('B2B carrier webhook processed without state change.', [
                'provider' => $provider,
                'channel' => $channel,
                'status' => $result['status'] ?? 'unknown',
            ]);

            return response()->json(['message' => $result['message'] ?? 'Webhook ignored.'], 202);
        }

        return response()->json(['message' => $result['message'] ?? 'Webhook processed.']);
    }

    protected function nextShipmentNumber(): string
    {
        $latest = B2BShipment::latest('id')->value('id') ?? 0;

        return 'SH-' . now()->format('Ymd') . '-' . str_pad((string) ($latest + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function getBuyerCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless(
            $company &&
            $this->b2bCompanyService->isApprovedBuyer(Auth::id(), $company->id) &&
            $this->b2bPermissionService->canAccessCompany(Auth::id(), $company->id),
            403
        );

        return $company;
    }

    protected function getSupplierCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless(
            $company &&
            $this->b2bCompanyService->isApprovedSupplier(Auth::id(), $company->id) &&
            $this->b2bPermissionService->canAccessCompany(Auth::id(), $company->id),
            403
        );

        return $company;
    }

    protected function carrierActionResponse(array $result)
    {
        if (request()->expectsJson()) {
            return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
        }

        flash(translate($result['message'] ?? 'Carrier action completed.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();

        return back();
    }
}
