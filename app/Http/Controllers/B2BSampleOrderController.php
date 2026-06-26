<?php

namespace App\Http\Controllers;

use App\Models\B2BCompany;
use App\Models\B2BSampleOrder;
use App\Models\Product;
use App\Services\B2BCompanyService;
use App\Services\B2BPermissionService;
use App\Services\B2BSampleProcessingFeeService;
use App\Services\B2BTradeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BSampleOrderController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPermissionService $b2bPermissionService,
        protected B2BTradeService $b2bTradeService,
        protected B2BSampleProcessingFeeService $sampleProcessingFeeService
    ) {
    }

    public function create(Request $request)
    {
        $buyerCompany = $this->getBuyerCompany();
        $product = $request->product_id ? Product::findOrFail($request->product_id) : null;
        $supplierCompany = $this->resolveSupplierCompany($request, $product);

        return view('b2b.sample_orders.create', [
            'buyerCompany' => $buyerCompany,
            'product' => $product,
            'supplierCompany' => $supplierCompany,
            'sampleProcessingFeeSettings' => $this->sampleProcessingFeeService->settings(),
        ]);
    }

    public function store(Request $request)
    {
        $buyerCompany = $this->getBuyerCompany();
        $data = $request->validate([
            'supplier_company_id' => 'required|exists:b2b_companies,id',
            'product_id' => 'nullable|exists:products,id',
            'rfq_id' => 'nullable|exists:b2b_rfqs,id',
            'quotation_id' => 'nullable|exists:b2b_quotations,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $supplierCompany = B2BCompany::approvedSupplierSide()->findOrFail($data['supplier_company_id']);
        $product = !empty($data['product_id']) ? Product::find($data['product_id']) : null;

        $sampleOrder = B2BSampleOrder::create([
            'sample_number' => $this->nextSampleOrderNumber(),
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'buyer_user_id' => Auth::id(),
            'supplier_user_id' => $supplierCompany->user_id,
            'product_id' => $product?->id,
            'rfq_id' => $data['rfq_id'] ?? null,
            'quotation_id' => $data['quotation_id'] ?? null,
            'currency' => $data['currency'] ?? get_system_default_currency()->code,
            'quantity' => $data['quantity'],
            'unit' => $data['unit'] ?? $product?->unit,
            'notes' => $data['notes'] ?? null,
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        flash(translate('Sample request submitted successfully.'))->success();

        return redirect()->route('b2b.sample-orders.show', $sampleOrder->id);
    }

    public function buyerIndex()
    {
        $company = $this->getBuyerCompany();
        $sampleOrders = B2BSampleOrder::with(['supplierCompany', 'product', 'shippingQuotes.shippingProvider', 'shipment'])
            ->where('buyer_company_id', $company->id)
            ->latest()
            ->paginate(15);

        return view('b2b.sample_orders.index', compact('sampleOrders'));
    }

    public function buyerShow($id)
    {
        $company = $this->getBuyerCompany();
        $sampleOrder = B2BSampleOrder::with([
            'supplierCompany',
            'product',
            'shippingQuotes.shippingProvider',
            'shipment.events',
            'documents',
        ])->where('buyer_company_id', $company->id)->findOrFail($id);

        $timeline = $this->b2bTradeService->buildSampleOrderTimeline($sampleOrder);

        return view('b2b.sample_orders.show', compact('sampleOrder', 'timeline'));
    }

    public function buyerMarkPaid(Request $request, $id)
    {
        $company = $this->getBuyerCompany();
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $company->id, ['owner', 'admin', 'procurement_manager']), 403);

        $sampleOrder = B2BSampleOrder::with('shippingQuotes')
            ->where('buyer_company_id', $company->id)
            ->findOrFail($id);

        $request->validate([
            'payment_reference' => 'required|string|max:255',
        ]);

        $selectedQuote = $sampleOrder->shippingQuotes()->where('status', 'selected')->latest()->first();
        abort_if(!$selectedQuote, 422, 'Shipping quote selection is required before payment.');

        $payload = $this->sampleProcessingFeeService->applyToSampleOrderPayload([
            'shipping_amount' => (float) $selectedQuote->total_cost,
            'sample_price' => (float) $sampleOrder->sample_price,
            'payment_reference' => $request->payment_reference,
            'paid_at' => now(),
            'status' => 'paid',
        ]);

        $sampleOrder->update($payload);

        flash(translate('Sample order marked as paid.'))->success();

        return back();
    }

    public function supplierIndex()
    {
        $company = $this->getSupplierCompany();
        $sampleOrders = B2BSampleOrder::with(['buyerCompany', 'product', 'shippingQuotes.shippingProvider', 'shipment'])
            ->where('supplier_company_id', $company->id)
            ->latest()
            ->paginate(15);

        return view('seller.b2b.sample_orders.index', compact('sampleOrders'));
    }

    public function supplierShow($id)
    {
        $company = $this->getSupplierCompany();
        $sampleOrder = B2BSampleOrder::with([
            'buyerCompany',
            'product',
            'shippingQuotes.shippingProvider',
            'shipment.events',
            'documents',
        ])->where('supplier_company_id', $company->id)->findOrFail($id);

        $timeline = $this->b2bTradeService->buildSampleOrderTimeline($sampleOrder);

        return view('seller.b2b.sample_orders.show', compact('sampleOrder', 'timeline'));
    }

    public function supplierAccept(Request $request, $id)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $company->id, ['owner', 'admin', 'sales_manager']), 403);

        $request->validate([
            'sample_price' => 'nullable|numeric|min:0',
        ]);

        $sampleOrder = B2BSampleOrder::where('supplier_company_id', $company->id)->findOrFail($id);
        $sampleOrder->update([
            'sample_price' => $request->sample_price ?? $sampleOrder->sample_price,
            'supplier_responded_at' => now(),
            'status' => 'accepted',
        ]);

        flash(translate('Sample order accepted successfully.'))->success();

        return back();
    }

    public function supplierReject($id)
    {
        $company = $this->getSupplierCompany();
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $company->id, ['owner', 'admin', 'sales_manager']), 403);

        $sampleOrder = B2BSampleOrder::where('supplier_company_id', $company->id)->findOrFail($id);
        $sampleOrder->update([
            'supplier_responded_at' => now(),
            'status' => 'rejected',
        ]);

        flash(translate('Sample order rejected successfully.'))->success();

        return back();
    }

    public function adminIndex()
    {
        $sampleOrders = B2BSampleOrder::with(['buyerCompany', 'supplierCompany', 'product', 'shipment'])->latest()->paginate(20);

        return view('backend.b2b.sample_orders.index', compact('sampleOrders'));
    }

    public function adminShow($id)
    {
        $sampleOrder = B2BSampleOrder::with([
            'buyerCompany',
            'supplierCompany',
            'product',
            'shippingQuotes.shippingProvider',
            'shipment.events',
            'documents',
        ])->findOrFail($id);

        $timeline = $this->b2bTradeService->buildSampleOrderTimeline($sampleOrder);

        return view('backend.b2b.sample_orders.show', compact('sampleOrder', 'timeline'));
    }

    protected function resolveSupplierCompany(Request $request, ?Product $product): ?B2BCompany
    {
        if ($request->supplier_company_id) {
            return B2BCompany::publicSuppliers()->find($request->supplier_company_id);
        }

        return $product?->publicSupplierCompany;
    }

    protected function nextSampleOrderNumber(): string
    {
        $latest = B2BSampleOrder::latest('id')->value('id') ?? 0;

        return 'SO-' . now()->format('Ymd') . '-' . str_pad((string) ($latest + 1), 5, '0', STR_PAD_LEFT);
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
