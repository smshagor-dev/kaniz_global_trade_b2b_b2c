<?php

namespace App\Http\Controllers;

use App\Models\B2BPurchaseOrder;
use App\Models\B2BSampleOrder;
use App\Models\B2BShippingProvider;
use App\Models\B2BShippingQuote;
use App\Services\B2BCompanyService;
use App\Services\B2BLogisticsChargeService;
use App\Services\B2BPermissionService;
use App\Services\B2BSampleProcessingFeeService;
use App\Services\B2BTradeService;
use App\Services\Carriers\CarrierManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BShippingQuoteController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPermissionService $b2bPermissionService,
        protected B2BTradeService $b2bTradeService,
        protected CarrierManager $carrierManager,
        protected B2BLogisticsChargeService $logisticsChargeService,
        protected B2BSampleProcessingFeeService $sampleProcessingFeeService
    ) {
    }

    public function createForPurchaseOrder($purchaseOrderId)
    {
        $company = $this->getSupplierCompany();
        $purchaseOrder = B2BPurchaseOrder::with(['buyerCompany', 'supplierCompany'])->where('supplier_company_id', $company->id)->findOrFail($purchaseOrderId);

        return view('seller.b2b.shipping_quotes.create', [
            'purchaseOrder' => $purchaseOrder,
            'sampleOrder' => null,
            'providers' => B2BShippingProvider::where('is_active', true)->orderBy('name')->get(),
            'transportModes' => B2BTradeService::TRANSPORT_MODES,
            'incoterms' => B2BTradeService::INCOTERMS,
            'shippingChargeSettings' => $this->logisticsChargeService->settings('shipping'),
        ]);
    }

    public function storeForPurchaseOrder(Request $request, $purchaseOrderId)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $company->id, ['owner', 'admin', 'sales_manager']), 403);

        $purchaseOrder = B2BPurchaseOrder::with(['buyerCompany', 'supplierCompany'])->where('supplier_company_id', $company->id)->findOrFail($purchaseOrderId);
        $data = $this->validatedData($request);

        $data = $this->logisticsChargeService->applyToShippingQuotePayload($data);

        B2BShippingQuote::create((new B2BShippingQuote())->filterPersistable(array_merge($data, [
            'quote_number' => $this->nextQuoteNumber(),
            'purchase_order_id' => $purchaseOrder->id,
            'sample_order_id' => null,
            'supplier_company_id' => $company->id,
            'buyer_company_id' => $purchaseOrder->buyer_company_id,
            'created_by' => Auth::id(),
        ])));

        flash(translate('Shipping quote created successfully.'))->success();

        return redirect()->route('seller.b2b.purchase-orders.show', $purchaseOrder->id);
    }

    public function createForSampleOrder($sampleOrderId)
    {
        $company = $this->getSupplierCompany();
        $sampleOrder = B2BSampleOrder::with(['buyerCompany', 'supplierCompany'])->where('supplier_company_id', $company->id)->findOrFail($sampleOrderId);

        return view('seller.b2b.shipping_quotes.create', [
            'purchaseOrder' => null,
            'sampleOrder' => $sampleOrder,
            'providers' => B2BShippingProvider::where('is_active', true)->orderBy('name')->get(),
            'transportModes' => B2BTradeService::TRANSPORT_MODES,
            'incoterms' => B2BTradeService::INCOTERMS,
            'shippingChargeSettings' => $this->logisticsChargeService->settings('shipping'),
        ]);
    }

    public function storeForSampleOrder(Request $request, $sampleOrderId)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $company->id, ['owner', 'admin', 'sales_manager']), 403);

        $sampleOrder = B2BSampleOrder::with(['buyerCompany', 'supplierCompany'])->where('supplier_company_id', $company->id)->findOrFail($sampleOrderId);
        $data = $this->validatedData($request);

        $data = $this->logisticsChargeService->applyToShippingQuotePayload($data);

        B2BShippingQuote::create((new B2BShippingQuote())->filterPersistable(array_merge($data, [
            'quote_number' => $this->nextQuoteNumber(),
            'purchase_order_id' => null,
            'sample_order_id' => $sampleOrder->id,
            'supplier_company_id' => $company->id,
            'buyer_company_id' => $sampleOrder->buyer_company_id,
            'created_by' => Auth::id(),
        ])));

        if (in_array($sampleOrder->status, ['accepted', 'requested'], true)) {
            $sampleOrder->update(['status' => 'shipping_quoted']);
        }

        flash(translate('Sample shipping quote created successfully.'))->success();

        return redirect()->route('seller.b2b.sample-orders.show', $sampleOrder->id);
    }

    public function select($id)
    {
        $company = $this->getBuyerCompany();
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $company->id, ['owner', 'admin', 'procurement_manager']), 403);

        $quote = B2BShippingQuote::with(['purchaseOrder', 'sampleOrder'])
            ->where('buyer_company_id', $company->id)
            ->findOrFail($id);

        if ($quote->purchase_order_id) {
            B2BShippingQuote::where('purchase_order_id', $quote->purchase_order_id)->update(['status' => 'rejected']);
        }

        if ($quote->sample_order_id) {
            B2BShippingQuote::where('sample_order_id', $quote->sample_order_id)->update(['status' => 'rejected']);
        }

        $quote->update(['status' => 'selected']);

        if ($quote->sampleOrder) {
            $payload = $this->sampleProcessingFeeService->applyToSampleOrderPayload([
                'shipping_amount' => (float) $quote->total_cost,
                'sample_price' => (float) $quote->sampleOrder->sample_price,
                'status' => 'payment_pending',
            ]);

            $quote->sampleOrder->update($payload);
        }

        flash(translate('Shipping quote selected successfully.'))->success();

        return back();
    }

    public function adminIndex()
    {
        $shippingQuotes = B2BShippingQuote::with(['shippingProvider', 'buyerCompany', 'supplierCompany', 'purchaseOrder', 'sampleOrder'])
            ->latest()
            ->paginate(20);

        return view('backend.b2b.shipping_quotes.index', compact('shippingQuotes'));
    }

    public function lookupRates(Request $request, $providerId)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $company->id, ['owner', 'admin', 'sales_manager']), 403);

        $provider = B2BShippingProvider::where('is_active', true)->findOrFail($providerId);
        $data = $request->validate([
            'shipper' => 'required|array',
            'recipient' => 'required|array',
            'weight' => 'required|numeric|min:0.001',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:20',
            'declared_value' => 'nullable|numeric|min:0',
            'service_type' => 'nullable|string|max:120',
        ]);

        $result = $this->carrierManager->getShippingRates($provider, $data);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    protected function validatedData(Request $request): array
    {
        $data = $request->validate([
            'shipping_provider_id' => 'nullable|exists:b2b_shipping_providers,id',
            'transport_mode' => 'required|in:' . implode(',', B2BTradeService::TRANSPORT_MODES),
            'origin_country' => 'required|string|max:100',
            'destination_country' => 'required|string|max:100',
            'incoterm' => 'required|in:' . implode(',', B2BTradeService::INCOTERMS),
            'service_type' => 'nullable|string|max:120',
            'delivery_priority' => 'nullable|string|max:40',
            'total_weight' => 'nullable|numeric|min:0.001',
            'package_length' => 'nullable|numeric|min:0',
            'package_width' => 'nullable|numeric|min:0',
            'package_height' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:20',
            'estimated_days' => 'nullable|integer|min:1|max:365',
            'shipping_cost' => 'nullable|numeric|min:0',
            'insurance_amount' => 'nullable|numeric|min:0',
            'customs_estimate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'rate_request_payload' => 'nullable|array',
            'rate_response_payload' => 'nullable',
        ]);

        $provider = !empty($data['shipping_provider_id']) ? B2BShippingProvider::find($data['shipping_provider_id']) : null;
        $defaults = $provider?->defaultQuoteAmounts() ?? [
            'shipping_cost' => 0,
            'insurance_amount' => 0,
            'customs_estimate' => 0,
        ];

        $data['shipping_cost'] = isset($data['shipping_cost']) ? (float) $data['shipping_cost'] : $defaults['shipping_cost'];
        $data['insurance_amount'] = isset($data['insurance_amount']) ? (float) $data['insurance_amount'] : $defaults['insurance_amount'];
        $data['customs_estimate'] = isset($data['customs_estimate']) ? (float) $data['customs_estimate'] : $defaults['customs_estimate'];

        return $data;
    }

    protected function nextQuoteNumber(): string
    {
        $latest = B2BShippingQuote::latest('id')->value('id') ?? 0;

        return 'SQ-' . now()->format('Ymd') . '-' . str_pad((string) ($latest + 1), 5, '0', STR_PAD_LEFT);
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
}
