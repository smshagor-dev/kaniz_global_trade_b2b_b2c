<?php

namespace App\Http\Controllers;

use App\Models\B2BEscrow;
use App\Models\B2BFinanceDispute;
use App\Models\B2BFinanceRefund;
use App\Models\B2BLetterOfCredit;
use App\Models\B2BPaymentMilestone;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BSettlement;
use App\Services\B2BAuditService;
use App\Services\B2BCompanyService;
use App\Services\B2BDashboardService;
use App\Services\B2BNotificationService;
use App\Services\B2BPermissionService;
use App\Services\B2BTradeFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BTradeFinanceController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPermissionService $permissionService,
        protected B2BTradeFinanceService $tradeFinanceService,
        protected B2BNotificationService $notificationService,
        protected B2BAuditService $auditService,
        protected B2BDashboardService $dashboardService
    ) {
    }

    public function buyerDashboard()
    {
        $company = $this->getBuyerCompany();
        $stats = $this->dashboardService->buyerStats(Auth::id());
        $purchaseOrders = B2BPurchaseOrder::with(['milestones', 'lettersOfCredit', 'financeDisputes', 'proformaInvoices'])
            ->where('buyer_company_id', $company->id)
            ->latest()
            ->paginate(10);

        return view('b2b.trade_finance.dashboard', compact('company', 'stats', 'purchaseOrders'));
    }

    public function supplierDashboard()
    {
        $company = $this->getSupplierCompany();
        $stats = $this->dashboardService->sellerStats(Auth::id());
        $purchaseOrders = B2BPurchaseOrder::with(['milestones', 'lettersOfCredit', 'financeDisputes', 'proformaInvoices'])
            ->where('supplier_company_id', $company->id)
            ->latest()
            ->paginate(10);

        return view('seller.b2b.trade_finance.dashboard', compact('company', 'stats', 'purchaseOrders'));
    }

    public function adminDashboard()
    {
        $stats = $this->dashboardService->adminStats();
        $milestones = B2BPaymentMilestone::latest()->limit(8)->get();
        $disputes = B2BFinanceDispute::latest()->limit(8)->get();
        $settlements = B2BSettlement::latest()->limit(8)->get();
        $refunds = B2BFinanceRefund::latest()->limit(8)->get();

        return view('backend.b2b.trade_finance.dashboard', compact('stats', 'milestones', 'disputes', 'settlements', 'refunds'));
    }

    public function configureMilestones(Request $request, $purchaseOrderId)
    {
        $purchaseOrder = $this->purchaseOrderForActor($purchaseOrderId, 'buyer');
        abort_unless($this->permissionService->canApproveMilestones(Auth::id(), $purchaseOrder->buyer_company_id), 403);

        $validated = $request->validate([
            'milestones' => 'required|array|min:1',
            'milestones.*.title' => 'required|string|max:255',
            'milestones.*.percentage' => 'required|numeric|min:0.01|max:100',
            'milestones.*.trigger_event' => 'nullable|in:production,shipment,delivery,custom',
            'milestones.*.scheduled_release_at' => 'nullable|date',
            'milestones.*.due_at' => 'nullable|date',
            'milestones.*.notes' => 'nullable|string',
        ]);

        $this->tradeFinanceService->createMilestones($purchaseOrder, $validated['milestones'], Auth::id(), $purchaseOrder->buyer_company_id);
        flash(translate('Milestones configured successfully.'))->success();

        return back();
    }

    public function fundMilestone(Request $request, $milestoneId)
    {
        $milestone = B2BPaymentMilestone::findOrFail($milestoneId);
        $purchaseOrder = $this->purchaseOrderForActor($milestone->purchase_order_id, 'buyer');
        abort_unless($this->permissionService->canApprovePayment(Auth::id(), $purchaseOrder->buyer_company_id), 403);

        $validated = $request->validate([
            'reference' => 'required|string|max:255',
            'payment_gateway' => 'nullable|string|max:60',
            'payment_method' => 'nullable|string|max:60',
            'settlement_currency' => 'nullable|string|max:20',
            'swift' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
        ]);

        $this->tradeFinanceService->fundMilestone($milestone, $validated, Auth::id(), $purchaseOrder->buyer_company_id);
        flash(translate('Milestone funded successfully.'))->success();

        return back();
    }

    public function releaseMilestone(Request $request, $milestoneId)
    {
        $milestone = B2BPaymentMilestone::findOrFail($milestoneId);
        $purchaseOrder = $this->purchaseOrderForActor($milestone->purchase_order_id, 'buyer');
        abort_unless($this->permissionService->canReleaseEscrow(Auth::id(), $purchaseOrder->buyer_company_id), 403);

        $this->tradeFinanceService->releaseMilestone($milestone, Auth::id(), $purchaseOrder->buyer_company_id, $request->input('notes'));
        flash(translate('Milestone released successfully.'))->success();

        return back();
    }

    public function requestLetterOfCredit(Request $request, $purchaseOrderId)
    {
        $purchaseOrder = $this->purchaseOrderForActor($purchaseOrderId, 'buyer');
        abort_unless($this->permissionService->canManageTradeFinance(Auth::id(), $purchaseOrder->buyer_company_id), 403);

        $validated = $request->validate([
            'lc_number' => 'required|string|max:255',
            'issuing_bank' => 'required|string|max:255',
            'advising_bank' => 'nullable|string|max:255',
            'expiry_date' => 'required|date|after:today',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:20',
            'required_documents' => 'nullable|string',
            'review_notes' => 'nullable|string',
        ]);

        $validated['required_documents'] = collect(explode(',', (string) ($validated['required_documents'] ?? '')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();

        $this->tradeFinanceService->createLetterOfCredit($purchaseOrder, $validated, Auth::id(), $purchaseOrder->buyer_company_id);
        flash(translate('Letter of credit requested successfully.'))->success();

        return back();
    }

    public function updateLetterOfCreditStatus(Request $request, $lcId)
    {
        $lc = B2BLetterOfCredit::findOrFail($lcId);
        $validated = $request->validate([
            'status' => 'required|in:bank_review,approved,documents_uploaded,shipment,lc_released,completed,rejected',
            'review_notes' => 'nullable|string',
        ]);

        $actorCompanyId = $lc->buyer_company_id ?: $lc->supplier_company_id;
        abort_unless($this->permissionService->canManageTradeFinance(Auth::id(), $actorCompanyId), 403);

        $this->tradeFinanceService->updateLetterOfCreditStatus($lc, $validated['status'], Auth::id(), $actorCompanyId, $validated['review_notes'] ?? null);
        flash(translate('LC status updated successfully.'))->success();

        return back();
    }

    public function createDispute(Request $request, $invoiceId)
    {
        $invoice = $this->invoiceForActor($invoiceId, 'buyer_or_supplier');
        $actorCompanyId = $this->resolveActorCompanyIdForInvoice($invoice);
        abort_unless($this->permissionService->canManageTradeFinance(Auth::id(), $actorCompanyId), 403);

        $validated = $request->validate([
            'category' => 'required|in:late_shipment,wrong_product,damage,payment,document_issue,refund',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'evidence' => 'nullable|string',
        ]);

        $validated += [
            'reference_type' => B2BProformaInvoice::class,
            'reference_id' => $invoice->id,
            'purchase_order_id' => $invoice->purchase_order_id,
            'proforma_invoice_id' => $invoice->id,
            'buyer_company_id' => $invoice->buyer_company_id,
            'supplier_company_id' => $invoice->supplier_company_id,
            'evidence' => collect(explode(',', (string) ($validated['evidence'] ?? '')))->map(fn ($item) => trim($item))->filter()->values()->all(),
        ];

        $this->tradeFinanceService->createDispute($validated, Auth::id(), $actorCompanyId);
        flash(translate('Dispute created successfully.'))->success();

        return back();
    }

    public function addDisputeMessage(Request $request, $disputeId)
    {
        $dispute = B2BFinanceDispute::findOrFail($disputeId);
        $actorCompanyId = $this->resolveActorCompanyIdForDispute($dispute);
        abort_unless($this->permissionService->canManageTradeFinance(Auth::id(), $actorCompanyId), 403);

        $validated = $request->validate([
            'message' => 'required|string',
            'attachments' => 'nullable|string',
        ]);

        $this->tradeFinanceService->addDisputeMessage($dispute, [
            'message' => $validated['message'],
            'sender_company_id' => $actorCompanyId,
            'attachments' => collect(explode(',', (string) ($validated['attachments'] ?? '')))->map(fn ($item) => trim($item))->filter()->values()->all(),
        ], Auth::id());

        flash(translate('Dispute message sent.'))->success();
        return back();
    }

    public function resolveDispute(Request $request, $disputeId)
    {
        $dispute = B2BFinanceDispute::findOrFail($disputeId);
        abort_unless(auth()->user()->user_type === 'admin', 403);

        $validated = $request->validate([
            'resolution' => 'required|in:release,refund,hold',
            'resolution_notes' => 'nullable|string',
        ]);

        $this->tradeFinanceService->resolveDispute($dispute, $validated, Auth::id(), null);
        flash(translate('Dispute resolved successfully.'))->success();

        return back();
    }

    public function requestSettlement(Request $request, $escrowId)
    {
        $escrow = B2BEscrow::findOrFail($escrowId);
        $company = $this->getSupplierCompany();
        abort_unless((int) $escrow->supplier_company_id === (int) $company->id, 403);
        abort_unless($this->permissionService->canManageTradeFinance(Auth::id(), $company->id), 403);

        $validated = $request->validate([
            'settlement_method' => 'required|in:wallet,bank_transfer,wise,payoneer,manual',
            'fees' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:255',
            'destination_details' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $this->tradeFinanceService->requestSettlement($escrow, [
            'settlement_method' => $validated['settlement_method'],
            'fees' => $validated['fees'] ?? 0,
            'reference' => $validated['reference'] ?? null,
            'destination_details' => ['summary' => $validated['destination_details'] ?? null],
            'notes' => $validated['notes'] ?? null,
        ], Auth::id(), $company->id);

        flash(translate('Settlement request submitted.'))->success();
        return back();
    }

    public function approveSettlement(Request $request, $settlementId)
    {
        $settlement = B2BSettlement::findOrFail($settlementId);
        abort_unless(auth()->user()->user_type === 'admin', 403);

        $this->tradeFinanceService->approveSettlement($settlement, Auth::id(), null, $request->input('notes'));
        flash(translate('Settlement approved successfully.'))->success();
        return back();
    }

    public function completeSettlement(Request $request, $settlementId)
    {
        $settlement = B2BSettlement::findOrFail($settlementId);
        abort_unless(auth()->user()->user_type === 'admin', 403);

        $this->tradeFinanceService->completeSettlement($settlement, Auth::id(), null, $request->input('notes'));
        flash(translate('Settlement completed successfully.'))->success();
        return back();
    }

    public function requestRefund(Request $request, $invoiceId)
    {
        $invoice = $this->invoiceForActor($invoiceId, 'buyer_or_supplier');
        $actorCompanyId = $this->resolveActorCompanyIdForInvoice($invoice);
        abort_unless($this->permissionService->canManageTradeFinance(Auth::id(), $actorCompanyId), 403);

        $escrow = $invoice->escrows()->latest('id')->first();
        $paymentTransaction = $invoice->paymentTransactions()->latest('id')->first();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'refund_type' => 'required|in:full,partial,escrow,gateway,manual',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $this->tradeFinanceService->requestRefund([
            'reference_type' => B2BProformaInvoice::class,
            'reference_id' => $invoice->id,
            'payment_transaction_id' => $paymentTransaction?->id,
            'escrow_id' => $escrow?->id,
            'amount' => $validated['amount'],
            'currency' => $invoice->currency,
            'refund_type' => $validated['refund_type'],
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
        ], Auth::id(), $actorCompanyId);

        flash(translate('Refund request submitted.'))->success();
        return back();
    }

    public function approveRefund(Request $request, $refundId)
    {
        $refund = B2BFinanceRefund::findOrFail($refundId);
        abort_unless(auth()->user()->user_type === 'admin', 403);

        $this->tradeFinanceService->approveRefund($refund, Auth::id(), null, $request->input('notes'));
        flash(translate('Refund approved successfully.'))->success();
        return back();
    }

    public function completeRefund(Request $request, $refundId)
    {
        $refund = B2BFinanceRefund::findOrFail($refundId);
        abort_unless(auth()->user()->user_type === 'admin', 403);

        $this->tradeFinanceService->completeRefund($refund, Auth::id(), null, $request->input('notes'));
        flash(translate('Refund completed successfully.'))->success();
        return back();
    }

    protected function purchaseOrderForActor($purchaseOrderId, string $actor): B2BPurchaseOrder
    {
        $query = B2BPurchaseOrder::with(['milestones.escrow', 'lettersOfCredit', 'financeDisputes.messages', 'proformaInvoices.escrows', 'proformaInvoices.financeRefunds']);

        if ($actor === 'buyer') {
            $company = $this->getBuyerCompany();
            return $query->where('buyer_company_id', $company->id)->findOrFail($purchaseOrderId);
        }

        if ($actor === 'supplier') {
            $company = $this->getSupplierCompany();
            return $query->where('supplier_company_id', $company->id)->findOrFail($purchaseOrderId);
        }

        return $query->findOrFail($purchaseOrderId);
    }

    protected function invoiceForActor($invoiceId, string $actor): B2BProformaInvoice
    {
        $query = B2BProformaInvoice::with(['milestones.escrow', 'lettersOfCredit', 'financeDisputes.messages', 'financeRefunds', 'escrows.settlements']);

        if ($actor === 'buyer_or_supplier') {
            $buyerCompany = $this->b2bCompanyService->getCompanyByUser(Auth::id());
            return $query->where(function ($inner) use ($buyerCompany) {
                $inner->where('buyer_company_id', $buyerCompany?->id)->orWhere('supplier_company_id', $buyerCompany?->id);
            })->findOrFail($invoiceId);
        }

        return $query->findOrFail($invoiceId);
    }

    protected function resolveActorCompanyIdForInvoice(B2BProformaInvoice $invoice): int
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());
        abort_unless($company, 403);
        abort_unless(in_array($company->id, [$invoice->buyer_company_id, $invoice->supplier_company_id]), 403);

        return $company->id;
    }

    protected function resolveActorCompanyIdForDispute(B2BFinanceDispute $dispute): int
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());
        abort_unless($company, 403);
        abort_unless(in_array($company->id, [$dispute->buyer_company_id, $dispute->supplier_company_id]), 403);

        return $company->id;
    }

    protected function getBuyerCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());
        abort_unless($company && $this->b2bCompanyService->isApprovedBuyer(Auth::id(), $company->id), 403);

        return $company;
    }

    protected function getSupplierCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());
        abort_unless($company && $this->b2bCompanyService->isApprovedSupplier(Auth::id(), $company->id), 403);

        return $company;
    }
}
