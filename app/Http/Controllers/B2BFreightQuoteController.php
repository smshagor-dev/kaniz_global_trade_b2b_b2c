<?php

namespace App\Http\Controllers;

use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuoteCost;
use App\Models\B2BFreightQuote;
use App\Models\B2BPort;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BRfq;
use App\Models\B2BSampleOrder;
use App\Models\B2BShipment;
use App\Services\B2BFreightCostService;
use App\Services\B2BFreightPricingService;
use App\Services\B2BCompanyService;
use App\Services\B2BFreightService;
use App\Services\B2BPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BFreightQuoteController extends Controller
{
    public function __construct(
        protected B2BCompanyService $companyService,
        protected B2BPermissionService $permissionService,
        protected B2BFreightService $freightService,
        protected B2BFreightPricingService $pricingService,
        protected B2BFreightCostService $costService
    ) {
    }

    public function buyerIndex(Request $request)
    {
        $company = $this->getAccessibleCompany();
        $quotes = $this->filteredQuoteQuery($request)
            ->where('buyer_company_id', $company->id)
            ->paginate(15);

        return view('b2b.freight_quotes.index', compact('quotes'));
    }

    public function buyerShow($id)
    {
        $company = $this->getAccessibleCompany();
        $quote = $this->baseQuoteDetailQuery()
            ->where('buyer_company_id', $company->id)
            ->findOrFail($id);

        return view('b2b.freight_quotes.show', compact('quote'));
    }

    public function supplierIndex(Request $request)
    {
        $company = $this->getAccessibleCompany();
        $quotes = $this->filteredQuoteQuery($request)
            ->where('supplier_company_id', $company->id)
            ->paginate(15);

        return view('seller.b2b.freight_quotes.index', compact('quotes'));
    }

    public function supplierShow($id)
    {
        $company = $this->getAccessibleCompany();
        $quote = $this->baseQuoteDetailQuery()
            ->where('supplier_company_id', $company->id)
            ->findOrFail($id);

        return view('seller.b2b.freight_quotes.show', compact('quote'));
    }

    public function adminIndex(Request $request)
    {
        $quotes = $this->filteredQuoteQuery($request)->paginate(20);

        return view('backend.b2b.freight_quotes.index', compact('quotes'));
    }

    public function adminShow($id)
    {
        $quote = $this->baseQuoteDetailQuery()->findOrFail($id);

        return view('backend.b2b.freight_quotes.show', compact('quote'));
    }

    public function store(Request $request)
    {
        $company = $this->companyService->getCompanyByUser(Auth::id());
        abort_unless(
            $company &&
            $this->permissionService->canAccessCompany(Auth::id(), $company->id) &&
            ($this->permissionService->canManageFreight(Auth::id(), $company->id) || $this->permissionService->canApproveFreightCosts(Auth::id(), $company->id)),
            403
        );

        $data = $request->validate([
            'forwarder_id' => 'nullable|exists:b2b_freight_forwarders,id',
            'purchase_order_id' => 'nullable|exists:b2b_purchase_orders,id',
            'proforma_invoice_id' => 'nullable|exists:b2b_proforma_invoices,id',
            'sample_order_id' => 'nullable|exists:b2b_sample_orders,id',
            'shipment_id' => 'nullable|exists:b2b_shipments,id',
            'rfq_id' => 'nullable|exists:b2b_rfqs,id',
            'buyer_company_id' => 'nullable|exists:b2b_companies,id',
            'supplier_company_id' => 'nullable|exists:b2b_companies,id',
            'origin_country' => 'required|string|max:100',
            'origin_port_id' => 'nullable|exists:b2b_ports,id',
            'destination_country' => 'required|string|max:100',
            'destination_port_id' => 'nullable|exists:b2b_ports,id',
            'freight_mode' => 'required|in:' . implode(',', B2BFreightService::FREIGHT_MODES),
            'service_type' => 'required|in:' . implode(',', B2BFreightService::SERVICE_TYPES),
            'incoterm' => 'required|in:' . implode(',', \App\Services\B2BTradeService::INCOTERMS),
            'container_type' => 'nullable|string|max:40',
            'container_count' => 'nullable|integer|min:1',
            'cargo_weight' => 'nullable|numeric|min:0',
            'cargo_volume' => 'nullable|numeric|min:0',
            'hs_code' => 'nullable|string|max:60',
            'goods_description' => 'nullable|string',
            'pickup_address' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'currency' => 'nullable|string|max:20',
        ]);

        [$buyerCompanyId, $supplierCompanyId] = $this->resolveQuoteCompanies($data, $company->id);

        $quote = B2BFreightQuote::create((new B2BFreightQuote())->filterPersistable(array_merge($data, [
            'quote_number' => $this->nextQuoteNumber(),
            'buyer_company_id' => $buyerCompanyId,
            'supplier_company_id' => $supplierCompanyId,
            'created_by' => Auth::id(),
            'container_count' => $data['container_count'] ?? 1,
            'currency' => $data['currency'] ?? 'USD',
            'base_currency' => $data['currency'] ?? 'USD',
            'status' => 'requested',
        ])));

        $quote = $quote->fresh(['forwarder', 'originPort', 'destinationPort']);
        $result = $quote->forwarder_id ? $this->freightService->requestQuote($quote) : null;

        if (!($result['success'] ?? false)) {
            $pricing = $this->pricingService->calculate($quote);
            if ($pricing) {
                $this->costService->applyPricingResult($quote, $pricing);
                $result = [
                    'success' => true,
                    'status' => 'priced',
                    'message' => 'Rule based freight quote created successfully.',
                    'quote_id' => $quote->id,
                ];
            } elseif ($quote->forwarder) {
                $defaults = $quote->forwarder->defaultQuoteAmounts();
                if (($defaults['freight_cost'] ?? 0) > 0 || ($defaults['insurance_cost'] ?? 0) > 0 || ($defaults['customs_estimate'] ?? 0) > 0) {
                    $quote->update($quote->filterPersistable([
                        'freight_cost' => $defaults['freight_cost'],
                        'insurance_cost' => $defaults['insurance_cost'],
                        'customs_estimate' => $defaults['customs_estimate'],
                        'total_cost' => $defaults['total_cost'],
                        'total_cost_base_currency' => $defaults['total_cost'],
                    ]));
                    $this->costService->syncDefaultCostsFromQuote($quote->fresh());
                    $result = [
                        'success' => true,
                        'status' => 'priced',
                        'message' => 'Default forwarder freight amount applied successfully.',
                        'quote_id' => $quote->id,
                    ];
                } else {
                    $result = [
                        'success' => true,
                        'status' => 'requested',
                        'message' => 'Freight quote draft created successfully.',
                        'quote_id' => $quote->id,
                    ];
                }
            } else {
                $result = [
                    'success' => true,
                    'status' => 'requested',
                    'message' => 'Freight quote draft created successfully.',
                    'quote_id' => $quote->id,
                ];
            }
        }

        $quote = $quote->fresh();
        $this->costService->recalculate($quote);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function select($id)
    {
        $company = $this->getAccessibleCompany();
        abort_unless($this->permissionService->canManageFreight(Auth::id(), $company->id) || $this->permissionService->canApproveFreightCosts(Auth::id(), $company->id), 403);

        $quote = B2BFreightQuote::where('buyer_company_id', $company->id)->orWhere('supplier_company_id', $company->id)->findOrFail($id);
        B2BFreightQuote::where(function ($query) use ($quote) {
            foreach (['purchase_order_id', 'proforma_invoice_id', 'sample_order_id', 'shipment_id', 'rfq_id'] as $field) {
                if ($quote->{$field}) {
                    $query->orWhere($field, $quote->{$field});
                }
            }
        })->update(['status' => 'rejected']);

        $quote->update(['status' => 'selected']);

        if ($this->expectsJsonResponse()) {
            return response()->json(['success' => true, 'quote_id' => $quote->id]);
        }

        flash(translate('Freight quote selected successfully.'))->success();

        return back();
    }

    protected function expectsJsonResponse(): bool
    {
        return request()->expectsJson() || request()->wantsJson() || request()->ajax();
    }

    public function storeCostLine(Request $request, $id)
    {
        $company = $this->getAccessibleCompany();
        $quote = $this->authorizedQuoteForUpdate($company->id, $id);
        abort_unless($this->permissionService->canManageFreight(Auth::id(), $company->id) || $this->permissionService->canApproveFreightCosts(Auth::id(), $company->id), 403);

        $line = $this->costService->storeLine($quote, $this->validatedCostLine($request));

        flash(translate('Freight cost line added successfully.'))->success();

        return back();
    }

    public function updateCostLine(Request $request, $id, $lineId)
    {
        $company = $this->getAccessibleCompany();
        $quote = $this->authorizedQuoteForUpdate($company->id, $id);
        abort_unless($this->permissionService->canManageFreight(Auth::id(), $company->id) || $this->permissionService->canApproveFreightCosts(Auth::id(), $company->id), 403);

        $line = B2BFreightQuoteCost::where('freight_quote_id', $quote->id)->findOrFail($lineId);
        $this->costService->updateLine($line, $this->validatedCostLine($request));

        flash(translate('Freight cost line updated successfully.'))->success();

        return back();
    }

    public function deleteCostLine($id, $lineId)
    {
        $company = $this->getAccessibleCompany();
        $quote = $this->authorizedQuoteForUpdate($company->id, $id);
        abort_unless($this->permissionService->canManageFreight(Auth::id(), $company->id) || $this->permissionService->canApproveFreightCosts(Auth::id(), $company->id), 403);

        $line = B2BFreightQuoteCost::where('freight_quote_id', $quote->id)->findOrFail($lineId);
        $this->costService->deleteLine($line);

        flash(translate('Freight cost line deleted successfully.'))->success();

        return back();
    }

    protected function resolveQuoteCompanies(array $data, int $activeCompanyId): array
    {
        $purchaseOrder = !empty($data['purchase_order_id']) ? B2BPurchaseOrder::find($data['purchase_order_id']) : null;
        $invoice = !empty($data['proforma_invoice_id']) ? B2BProformaInvoice::find($data['proforma_invoice_id']) : null;
        $sampleOrder = !empty($data['sample_order_id']) ? B2BSampleOrder::find($data['sample_order_id']) : null;
        $shipment = !empty($data['shipment_id']) ? B2BShipment::find($data['shipment_id']) : null;
        $rfq = !empty($data['rfq_id']) ? B2BRfq::find($data['rfq_id']) : null;

        $linkedBuyerCompanyId = $purchaseOrder?->buyer_company_id
            ?? $invoice?->buyer_company_id
            ?? $sampleOrder?->buyer_company_id
            ?? $shipment?->buyer_company_id
            ?? $rfq?->b2b_company_id;

        $linkedSupplierCompanyId = $purchaseOrder?->supplier_company_id
            ?? $invoice?->supplier_company_id
            ?? $sampleOrder?->supplier_company_id
            ?? $shipment?->supplier_company_id
            ?? $rfq?->supplier_company_id;

        abort_if(
            $linkedBuyerCompanyId !== null &&
            $linkedSupplierCompanyId !== null &&
            (int) $activeCompanyId !== (int) $linkedBuyerCompanyId &&
            (int) $activeCompanyId !== (int) $linkedSupplierCompanyId,
            403
        );

        $buyer = $data['buyer_company_id']
            ?? $linkedBuyerCompanyId
            ?? $activeCompanyId;

        $supplier = $data['supplier_company_id']
            ?? $linkedSupplierCompanyId;

        if ($linkedBuyerCompanyId !== null) {
            $buyer = $linkedBuyerCompanyId;
        }

        if ($linkedSupplierCompanyId !== null) {
            $supplier = $linkedSupplierCompanyId;
        }

        return [$buyer, $supplier];
    }

    protected function nextQuoteNumber(): string
    {
        $latest = B2BFreightQuote::latest('id')->value('id') ?? 0;

        return 'FQ-' . now()->format('Ymd') . '-' . str_pad((string) ($latest + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function filteredQuoteQuery(Request $request)
    {
        return B2BFreightQuote::query()
            ->with(['buyerCompany', 'supplierCompany', 'forwarder', 'originPort', 'destinationPort', 'costs'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('origin_country'), fn ($query) => $query->where('origin_country', 'like', '%' . $request->origin_country . '%'))
            ->when($request->filled('destination_country'), fn ($query) => $query->where('destination_country', 'like', '%' . $request->destination_country . '%'))
            ->when($request->filled('port'), fn ($query) => $query->where(function ($portQuery) use ($request) {
                $portQuery->whereHas('originPort', fn ($originQuery) => $originQuery->where('name', 'like', '%' . $request->port . '%'))
                    ->orWhereHas('destinationPort', fn ($destinationQuery) => $destinationQuery->where('name', 'like', '%' . $request->port . '%'));
            }))
            ->when($request->filled('freight_mode'), fn ($query) => $query->where('freight_mode', $request->freight_mode))
            ->when($request->filled('service_type'), fn ($query) => $query->where('service_type', $request->service_type))
            ->when($request->filled('forwarder_id'), fn ($query) => $query->where('forwarder_id', $request->forwarder_id))
            ->when($request->filled('container_type'), fn ($query) => $query->where('container_type', $request->container_type))
            ->when($request->filled('incoterm'), fn ($query) => $query->where('incoterm', $request->incoterm))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->date))
            ->when($request->filled('search'), fn ($query) => $query->where(function ($searchQuery) use ($request) {
                $search = $request->search;
                $searchQuery->where('quote_number', 'like', '%' . $search . '%')
                    ->orWhere('goods_description', 'like', '%' . $search . '%')
                    ->orWhere('hs_code', 'like', '%' . $search . '%');
            }))
            ->latest();
    }

    protected function baseQuoteDetailQuery()
    {
        return B2BFreightQuote::query()->with([
            'buyerCompany',
            'supplierCompany',
            'forwarder',
            'originPort',
            'destinationPort',
            'purchaseOrder',
            'proformaInvoice',
            'sampleOrder',
            'shipment',
            'pricingRule',
            'hsCodeRecord',
            'costs',
            'customsDocuments',
            'containerShipments.events.port',
            'containerShipments.forwarder',
            'containerShipments.originPort',
            'containerShipments.destinationPort',
        ]);
    }

    protected function getAccessibleCompany()
    {
        $company = $this->companyService->getCompanyByUser(Auth::id());
        abort_unless($company && $this->permissionService->canAccessCompany(Auth::id(), $company->id), 403);

        return $company;
    }

    protected function authorizedQuoteForUpdate(int $companyId, int $quoteId): B2BFreightQuote
    {
        return B2BFreightQuote::query()
            ->where(function ($query) use ($companyId) {
                $query->where('buyer_company_id', $companyId)->orWhere('supplier_company_id', $companyId);
            })
            ->findOrFail($quoteId);
    }

    protected function validatedCostLine(Request $request): array
    {
        return $request->validate([
            'cost_type' => 'required|string|max:80',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string|max:20',
            'exchange_rate_snapshot' => 'nullable|numeric|min:0.000001',
            'payer' => 'nullable|in:buyer,supplier,platform',
            'is_billable' => 'nullable|boolean',
            'is_optional' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]) + [
            'is_billable' => $request->boolean('is_billable', true),
            'is_optional' => $request->boolean('is_optional'),
        ];
    }
}
