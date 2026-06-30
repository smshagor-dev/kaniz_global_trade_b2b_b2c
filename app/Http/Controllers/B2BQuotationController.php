<?php

namespace App\Http\Controllers;

use App\Models\B2BQuotation;
use App\Models\B2BRfq;
use App\Services\B2BAuditService;
use App\Services\B2BCompanyService;
use App\Services\B2BNotificationService;
use App\Services\B2BPermissionService;
use App\Services\B2BTradeService;
use App\Services\B2BTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class B2BQuotationController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BNotificationService $b2bNotificationService,
        protected B2BTransactionService $b2bTransactionService,
        protected B2BAuditService $b2bAuditService,
        protected B2BPermissionService $b2bPermissionService
    )
    {
    }

    public function supplierRfqIndex(Request $request)
    {
        if (!$this->isApprovedSupplierUser()) {
            return $this->supplierApprovalRedirect();
        }

        $supplierCompany = $this->getApprovedSupplierCompany();
        $supplierCategoryIds = $supplierCompany->categories()->pluck('categories.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $supplierProductCategoryIds = $supplierCompany->wholesaleProducts()
            ->pluck('category_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $rfqs = B2BRfq::with(['company', 'product', 'category', 'quotations'])
            ->where('user_id', '!=', Auth::id())
            ->whereIn('status', ['open', 'quoted'])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->orderByRaw('CASE WHEN supplier_company_id = ? THEN 0 WHEN supplier_company_id IS NULL THEN 1 ELSE 2 END', [$supplierCompany->id])
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        $rfqs->getCollection()->transform(function (B2BRfq $rfq) use ($supplierCompany, $supplierCategoryIds, $supplierProductCategoryIds) {
            $rfq->is_related_match = $this->isRfqRelatedToSupplier(
                $rfq,
                $supplierCompany->id,
                $supplierCategoryIds,
                $supplierProductCategoryIds
            );
            $rfq->can_submit_quote = $rfq->is_related_match && in_array($rfq->status, ['open', 'quoted'], true);

            return $rfq;
        });

        return view('seller.b2b.rfqs.index', compact('rfqs', 'supplierCompany'));
    }

    public function supplierIndex()
    {
        $supplierCompany = $this->getAccessibleSupplierCompany();

        $quotations = B2BQuotation::with(['rfq.company', 'rfq.product', 'supplierCompany', 'product'])
            ->where('supplier_company_id', $supplierCompany->id)
            ->latest()
            ->paginate(15);

        return view('seller.b2b.quotations.index', compact('quotations'));
    }

    public function create($rfqId)
    {
        if (!$this->isApprovedSupplierUser()) {
            return $this->supplierApprovalRedirect();
        }

        $rfq = $this->getQuotableRfq($rfqId);
        $supplierCompany = $this->getApprovedSupplierCompany();
        $existingQuotation = $this->findExistingQuotation($rfq->id, $supplierCompany->id);

        return view('seller.b2b.rfqs.quote', compact('rfq', 'supplierCompany', 'existingQuotation'));
    }

    public function store(Request $request, $rfqId)
    {
        if (!$this->isApprovedSupplierUser()) {
            return $this->supplierApprovalRedirect();
        }

        $rfq = $this->getQuotableRfq($rfqId);
        $supplierCompany = $this->getApprovedSupplierCompany();

        if ($existingQuotation = $this->findExistingQuotation($rfq->id, $supplierCompany->id)) {
            flash(translate('You have already submitted a quotation for this RFQ.'))->warning();
            return $this->redirectToExistingQuotation($existingQuotation);
        }

        $data = $this->validatedData($request);
        $data['rfq_id'] = $rfq->id;
        $data['supplier_user_id'] = Auth::id();
        $data['supplier_company_id'] = $supplierCompany->id;
        $data['product_id'] = $data['product_id'] ?? $rfq->product_id;
        $data['currency'] = $rfq->currency;
        $data['status'] = 'pending';
        $data['attachment'] = $this->storeFile($request, 'attachment', 'uploads/b2b_quotations');

        $quotation = null;

        DB::transaction(function () use ($data, $rfq, &$quotation) {
            $quotation = B2BQuotation::create($data);

            if ($rfq->status === 'open') {
                $rfq->update(['status' => 'quoted']);
            }
        });

        if ($quotation) {
            $quotation->load(['rfq', 'supplierCompany']);
            $negotiation = $this->b2bTransactionService->ensureNegotiation($rfq, $quotation);
            $this->b2bTransactionService->addSystemMessage(
                $negotiation,
                Auth::id(),
                $supplierCompany->id,
                'price_change',
                'Initial quotation submitted.',
                [
                    'quotation_id' => $quotation->id,
                    'price' => $quotation->price,
                    'currency' => $quotation->currency,
                ]
            );

            $this->b2bAuditService->log(Auth::id(), $supplierCompany->id, 'quote_submitted', $quotation, 'Quotation submitted for RFQ.', [
                'rfq_id' => $rfq->id,
                'price' => $quotation->price,
            ]);
            $this->b2bNotificationService->notifyBuyerAboutNewQuotation($quotation);
        }

        flash(translate('Quotation submitted successfully.'))->success();

        return redirect()->route('seller.b2b.quotations.index');
    }

    public function show($id)
    {
        $this->getAccessibleSupplierCompany();

        $quotation = $this->ownedSupplierQuotationQuery()
            ->with(['rfq.company', 'rfq.product', 'rfq.category', 'supplierCompany', 'product', 'negotiation'])
            ->findOrFail($id);

        return view('seller.b2b.quotations.show', compact('quotation'));
    }

    public function edit($id)
    {
        if (!$this->isApprovedSupplierUser()) {
            return $this->supplierApprovalRedirect();
        }

        $quotation = $this->ownedSupplierQuotationQuery()
            ->with(['rfq.company', 'rfq.product', 'rfq.category', 'supplierCompany', 'product'])
            ->findOrFail($id);

        if ($quotation->status !== 'pending') {
            flash(translate('Only pending quotations can be edited.'))->warning();
            return redirect()->route('seller.b2b.quotations.show', $quotation->id);
        }

        return view('seller.b2b.quotations.edit', compact('quotation'));
    }

    public function update(Request $request, $id)
    {
        if (!$this->isApprovedSupplierUser()) {
            return $this->supplierApprovalRedirect();
        }

        $quotation = $this->ownedSupplierQuotationQuery()
            ->with(['rfq', 'product'])
            ->findOrFail($id);

        if ($quotation->status !== 'pending') {
            flash(translate('Only pending quotations can be updated.'))->warning();
            return redirect()->route('seller.b2b.quotations.show', $quotation->id);
        }

        if (!in_array($quotation->rfq->status, ['open', 'quoted'])) {
            flash(translate('This RFQ is no longer open for quotation updates.'))->warning();
            return redirect()->route('seller.b2b.quotations.show', $quotation->id);
        }

        if ($quotation->rfq->expires_at && $quotation->rfq->expires_at->isPast()) {
            flash(translate('This RFQ has expired.'))->warning();
            return redirect()->route('seller.b2b.quotations.show', $quotation->id);
        }

        $data = $this->validatedData($request);
        $oldPrice = $quotation->price;
        $data['product_id'] = $data['product_id'] ?? $quotation->rfq->product_id;
        $data['currency'] = $quotation->rfq->currency;
        $data['attachment'] = $this->storeFile($request, 'attachment', 'uploads/b2b_quotations', $quotation->attachment);

        $quotation->update($data);
        $quotation->load('supplierCompany');

        $negotiation = $this->b2bTransactionService->ensureNegotiation($quotation->rfq, $quotation);
        $messageType = $oldPrice != $quotation->price ? 'price_change' : 'status_change';
        $message = $oldPrice != $quotation->price
            ? 'Quotation price updated.'
            : 'Quotation details updated.';
        $this->b2bTransactionService->addSystemMessage(
            $negotiation,
            Auth::id(),
            $quotation->supplier_company_id,
            $messageType,
            $message,
            [
                'quotation_id' => $quotation->id,
                'old_price' => $oldPrice,
                'new_price' => $quotation->price,
                'currency' => $quotation->currency,
            ]
        );

        $this->b2bAuditService->log(Auth::id(), $quotation->supplier_company_id, 'quote_updated', $quotation, 'Quotation updated by supplier.', [
            'rfq_id' => $quotation->rfq_id,
            'old_price' => $oldPrice,
            'new_price' => $quotation->price,
        ]);

        flash(translate('Quotation updated successfully.'))->success();

        return redirect()->route('seller.b2b.quotations.show', $quotation->id);
    }

    public function accept($id)
    {
        $company = $this->getAccessibleBuyerCompanyForPurchaseFlow();
        $quotation = B2BQuotation::with('rfq')
            ->whereHas('rfq', fn ($query) => $query->where('b2b_company_id', $company->id))
            ->findOrFail($id);

        if (in_array($quotation->rfq->status, ['closed', 'cancelled']) || $quotation->status !== 'pending') {
            flash(translate('This quotation can no longer be accepted.'))->warning();
            return back();
        }

        $rejectedSupplierIds = collect();

        DB::transaction(function () use ($quotation, &$rejectedSupplierIds) {
            $rejectedQuotationIds = B2BQuotation::where('rfq_id', $quotation->rfq_id)
                ->where('id', '!=', $quotation->id)
                ->where('status', 'pending')
                ->pluck('supplier_user_id');

            $rejectedSupplierIds = $rejectedQuotationIds->values();

            B2BQuotation::where('rfq_id', $quotation->rfq_id)
                ->where('id', '!=', $quotation->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            $quotation->update(['status' => 'accepted']);
            $quotation->rfq->update(['status' => 'closed']);
        });

        $purchaseOrder = $this->b2bTransactionService->createPurchaseOrderFromQuotation($quotation->fresh(['rfq.company', 'rfq.product', 'supplierCompany', 'supplier']));
        $negotiation = $this->b2bTransactionService->ensureNegotiation($quotation->rfq, $quotation->fresh());
        $this->b2bTransactionService->syncNegotiationPurchaseOrder($negotiation, $purchaseOrder);
        $this->b2bAuditService->log(Auth::id(), $quotation->rfq->b2b_company_id, 'quote_accepted', $quotation, 'Quotation accepted by buyer.', [
            'purchase_order_id' => $purchaseOrder->id,
            'rfq_id' => $quotation->rfq_id,
        ]);
        $this->b2bNotificationService->notifyQuotationDecision($quotation, $rejectedSupplierIds);

        flash(translate('Quotation accepted successfully.'))->success();

        return back();
    }

    public function reject($id)
    {
        $company = $this->getAccessibleBuyerCompanyForPurchaseFlow();
        $quotation = B2BQuotation::with('rfq')
            ->whereHas('rfq', fn ($query) => $query->where('b2b_company_id', $company->id))
            ->findOrFail($id);

        if (in_array($quotation->rfq->status, ['closed', 'cancelled']) || $quotation->status !== 'pending') {
            flash(translate('This quotation cannot be rejected.'))->warning();
            return back();
        }

        $quotation->update(['status' => 'rejected']);
        $this->b2bAuditService->log(Auth::id(), $quotation->rfq->b2b_company_id, 'quote_rejected', $quotation, 'Quotation rejected by buyer.', [
            'rfq_id' => $quotation->rfq_id,
        ]);

        flash(translate('Quotation rejected successfully.'))->success();

        return back();
    }

    public function withdraw($id)
    {
        if (!$this->isApprovedSupplierUser()) {
            return $this->supplierApprovalRedirect();
        }

        $quotation = $this->ownedSupplierQuotationQuery()->with('rfq')->findOrFail($id);

        if ($quotation->status === 'accepted') {
            flash(translate('Accepted quotations cannot be withdrawn.'))->warning();
            return back();
        }

        if ($quotation->status === 'withdrawn') {
            flash(translate('This quotation has already been withdrawn.'))->warning();
            return back();
        }

        $quotation->update(['status' => 'withdrawn']);
        $this->b2bAuditService->log(Auth::id(), $quotation->supplier_company_id, 'quote_withdrawn', $quotation, 'Quotation withdrawn by supplier.', [
            'rfq_id' => $quotation->rfq_id,
        ]);

        $this->b2bNotificationService->notifyBuyerAboutWithdrawnQuotation($quotation);

        flash(translate('Quotation withdrawn successfully.'))->success();

        return back();
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:20',
            'moq' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'shipping_terms' => 'nullable|string|max:255',
            'incoterm' => 'nullable|in:' . implode(',', B2BTradeService::INCOTERMS),
            'payment_terms' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);
    }

    protected function getQuotableRfq($rfqId): B2BRfq
    {
        $rfq = B2BRfq::with(['company', 'product', 'category'])->findOrFail($rfqId);
        $supplierCompany = $this->getApprovedSupplierCompany();

        if ($rfq->user_id === Auth::id()) {
            abort(403);
        }

        if (!in_array($rfq->status, ['open', 'quoted'])) {
            abort(403);
        }

        if ($rfq->expires_at && $rfq->expires_at->isPast()) {
            abort(403);
        }

        abort_unless(
            $supplierCompany && $this->isRfqRelatedToSupplier($rfq, $supplierCompany->id),
            403
        );

        return $rfq;
    }

    protected function isRfqRelatedToSupplier(
        B2BRfq $rfq,
        int $supplierCompanyId,
        ?array $supplierCategoryIds = null,
        ?array $supplierProductCategoryIds = null
    ): bool {
        if ($rfq->supplier_company_id) {
            return (int) $rfq->supplier_company_id === $supplierCompanyId;
        }

        $rfqCategoryId = (int) ($rfq->category_id ?: $rfq->product?->category_id);

        if ($rfqCategoryId <= 0) {
            return true;
        }

        $supplierCategoryIds ??= \App\Models\B2BCompany::find($supplierCompanyId)?->categories()
            ->pluck('categories.id')
            ->map(fn ($id) => (int) $id)
            ->all() ?? [];

        if (in_array($rfqCategoryId, $supplierCategoryIds, true)) {
            return true;
        }

        $supplierProductCategoryIds ??= \App\Models\B2BCompany::find($supplierCompanyId)?->wholesaleProducts()
            ->pluck('category_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all() ?? [];

        return in_array($rfqCategoryId, $supplierProductCategoryIds, true);
    }

    protected function getApprovedSupplierCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        if (
            !$company ||
            !$this->b2bCompanyService->isApprovedSupplier(Auth::id(), $company->id) ||
            !$this->b2bCompanyService->canReplyToRfq(Auth::id(), $company->id)
        ) {
            return null;
        }

        return $company;
    }

    protected function getAccessibleSupplierCompany()
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

    protected function getAccessibleBuyerCompanyForPurchaseFlow()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless(
            $company &&
            $this->b2bCompanyService->isApprovedBuyer(Auth::id(), $company->id) &&
            $this->b2bPermissionService->canManagePurchaseOrder(Auth::id(), $company->id),
            403
        );

        return $company;
    }

    protected function ownedSupplierQuotationQuery()
    {
        $supplierCompany = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        return B2BQuotation::query()
            ->where('supplier_company_id', $supplierCompany?->id ?? 0);
    }

    protected function findExistingQuotation(int $rfqId, int $supplierCompanyId): ?B2BQuotation
    {
        return B2BQuotation::query()
            ->where('rfq_id', $rfqId)
            ->where('supplier_company_id', $supplierCompanyId)
            ->first();
    }

    protected function redirectToExistingQuotation(B2BQuotation $quotation)
    {
        if ($quotation->status === 'pending') {
            return redirect()->route('seller.b2b.quotations.edit', $quotation->id);
        }

        return redirect()->route('seller.b2b.quotations.show', $quotation->id);
    }

    protected function isApprovedSupplierUser(): bool
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        return Auth::check()
            && $company
            && $this->b2bCompanyService->canReplyToRfq(Auth::id(), $company->id);
    }

    protected function supplierApprovalRedirect()
    {
        flash(translate('An approved supplier company and active supplier package are required to submit quotations.'))->error();
        return redirect()->route('b2b.packages.index');
    }

    protected function storeFile(Request $request, string $field, string $directoryPath, ?string $oldFile = null): ?string
    {
        if (!$request->hasFile($field)) {
            return $oldFile;
        }

        $directory = public_path($directoryPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if ($oldFile && File::exists(public_path($oldFile))) {
            File::delete(public_path($oldFile));
        }

        $file = $request->file($field);
        $fileName = time() . '_' . $field . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $fileName);

        return $directoryPath . '/' . $fileName;
    }
}
