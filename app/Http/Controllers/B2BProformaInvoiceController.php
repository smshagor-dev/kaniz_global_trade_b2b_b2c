<?php

namespace App\Http\Controllers;

use App\Models\B2BNegotiation;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Services\B2BAuditService;
use App\Services\B2BCompanyService;
use App\Services\B2BEscrowFeeService;
use App\Services\B2BNotificationService;
use App\Services\B2BPermissionService;
use App\Services\B2BOrderPlatformFeeService;
use App\Services\B2BPaymentService;
use App\Services\B2BTradeService;
use App\Services\B2BTransactionService;
use App\Services\Currency\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BProformaInvoiceController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BTransactionService $b2bTransactionService,
        protected B2BAuditService $b2bAuditService,
        protected B2BNotificationService $b2bNotificationService,
        protected B2BPermissionService $b2bPermissionService,
        protected B2BTradeService $b2bTradeService,
        protected B2BOrderPlatformFeeService $orderPlatformFeeService,
        protected B2BEscrowFeeService $escrowFeeService,
        protected B2BPaymentService $b2bPaymentService,
        protected CurrencyService $currencyService
    ) {
    }

    public function buyerIndex()
    {
        $company = $this->getBuyerCompany();
        $invoices = B2BProformaInvoice::with(['supplierCompany', 'purchaseOrder'])
            ->where('buyer_company_id', $company->id)
            ->latest()
            ->paginate(15);

        return view('b2b.proforma_invoices.index', compact('invoices'));
    }

    public function buyerShow($id)
    {
        $invoice = $this->buyerQuery()->findOrFail($id);
        $timeline = $invoice->purchaseOrder ? $this->b2bTradeService->buildTradeTimelineForPurchaseOrder($invoice->purchaseOrder) : collect();

        return view('b2b.proforma_invoices.show', compact('invoice', 'timeline'));
    }

    public function buyerAccept($id)
    {
        $invoice = $this->buyerQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canManageInvoice(Auth::id(), $invoice->buyer_company_id), 403);

        if ($invoice->status !== 'sent') {
            flash(translate('Only sent proforma invoices can be accepted.'))->warning();
            return back();
        }

        $invoice->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'escrow_status' => $invoice->usesEscrow() ? 'awaiting_payment' : 'not_applicable',
        ]);

        $this->logInvoiceStatus($invoice, 'invoice_accepted', 'Proforma invoice accepted by buyer.');
        $this->b2bNotificationService->notifySupplierAboutInvoiceDecision($invoice);

        flash(translate('Proforma invoice accepted successfully.'))->success();

        return back();
    }

    public function buyerFund(Request $request, $id)
    {
        $invoice = $this->buyerQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canManageInvoice(Auth::id(), $invoice->buyer_company_id), 403);

        if (!$invoice->canFundEscrow()) {
            flash(translate('This invoice is not ready for escrow funding.'))->warning();
            return back();
        }

        $data = $request->validate([
            'escrow_payment_reference' => 'required|string|max:255',
            'payment_gateway' => 'nullable|string|max:60',
            'payment_method' => 'nullable|string|max:60',
            'settlement_currency' => 'nullable|string|max:20',
            'reference_number' => 'nullable|string|max:255',
            'swift' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
        ]);

        $this->b2bPaymentService->recordInvoiceFunding($invoice, $data, Auth::id());

        $invoice->update([
            'escrow_status' => 'funded',
            'escrow_payment_reference' => $data['escrow_payment_reference'],
            'escrow_funded_at' => now(),
            'escrow_disputed_at' => null,
            'escrow_dispute_reason' => null,
            'escrow_resolution' => null,
            'escrow_resolution_notes' => null,
        ]);

        $this->logInvoiceStatus($invoice, 'escrow_funded', 'Escrow funded by buyer.');
        $this->b2bNotificationService->notifySupplierAboutInvoiceDecision($invoice);

        flash(translate('Escrow funded successfully.'))->success();

        return back();
    }

    public function buyerRelease(Request $request, $id)
    {
        $invoice = $this->buyerQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canManageInvoice(Auth::id(), $invoice->buyer_company_id), 403);

        if (!$invoice->canReleaseEscrow()) {
            flash(translate('This escrow cannot be released right now.'))->warning();
            return back();
        }

        $this->resolveEscrow($invoice, 'released', $request->input('escrow_resolution_notes'), 'buyer');
        $this->b2bNotificationService->notifySupplierAboutInvoiceDecision($invoice);

        flash(translate('Escrow released successfully.'))->success();

        return back();
    }

    public function buyerDispute(Request $request, $id)
    {
        $invoice = $this->buyerQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canManageInvoice(Auth::id(), $invoice->buyer_company_id), 403);

        if (!$invoice->canDisputeEscrow()) {
            flash(translate('This escrow cannot be disputed right now.'))->warning();
            return back();
        }

        $data = $request->validate([
            'escrow_dispute_reason' => 'required|string|max:5000',
        ]);

        $invoice->update([
            'escrow_status' => 'disputed',
            'escrow_disputed_at' => now(),
            'escrow_dispute_reason' => $data['escrow_dispute_reason'],
            'escrow_resolution' => null,
            'escrow_resolution_notes' => null,
        ]);

        $this->logInvoiceStatus($invoice, 'escrow_disputed', 'Escrow disputed by buyer.');
        $this->b2bNotificationService->notifySupplierAboutInvoiceDecision($invoice);

        flash(translate('Escrow dispute raised successfully.'))->success();

        return back();
    }

    public function supplierIndex()
    {
        $company = $this->getSupplierCompany();
        $invoices = B2BProformaInvoice::with(['buyerCompany', 'purchaseOrder'])
            ->where('supplier_company_id', $company->id)
            ->latest()
            ->paginate(15);

        return view('seller.b2b.proforma_invoices.index', compact('invoices'));
    }

    public function create($purchaseOrderId)
    {
        $purchaseOrder = $this->supplierPurchaseOrderQuery()->findOrFail($purchaseOrderId);
        abort_unless($this->b2bPermissionService->canManageInvoice(Auth::id(), $purchaseOrder->supplier_company_id), 403);

        if ($purchaseOrder->status !== 'accepted') {
            flash(translate('Only accepted purchase orders are ready for proforma invoice generation.'))->warning();
            return redirect()->route('seller.b2b.purchase-orders.show', $purchaseOrder->id);
        }

        if ($existingInvoice = B2BProformaInvoice::where('purchase_order_id', $purchaseOrder->id)->latest()->first()) {
            flash(translate('A proforma invoice already exists for this purchase order.'))->warning();
            return redirect()->route('seller.b2b.proforma-invoices.show', $existingInvoice->id);
        }

        return view('seller.b2b.proforma_invoices.create', [
            'purchaseOrder' => $purchaseOrder,
            'orderPlatformFeeSettings' => $this->orderPlatformFeeService->settings(),
            'escrowFeeSettings' => $this->escrowFeeService->settings(),
        ]);
    }

    public function store(Request $request, $purchaseOrderId)
    {
        $purchaseOrder = $this->supplierPurchaseOrderQuery()->findOrFail($purchaseOrderId);
        abort_unless($this->b2bPermissionService->canManageInvoice(Auth::id(), $purchaseOrder->supplier_company_id), 403);

        if ($purchaseOrder->status !== 'accepted') {
            flash(translate('Only accepted purchase orders are ready for proforma invoice generation.'))->warning();
            return redirect()->route('seller.b2b.purchase-orders.show', $purchaseOrder->id);
        }

        if ($existingInvoice = B2BProformaInvoice::where('purchase_order_id', $purchaseOrder->id)->latest()->first()) {
            flash(translate('A proforma invoice already exists for this purchase order.'))->warning();
            return redirect()->route('seller.b2b.proforma-invoices.show', $existingInvoice->id);
        }

        $payload = $this->validatedPayload($request, $purchaseOrder);
        $invoice = $this->b2bTransactionService->createProformaInvoiceFromPurchaseOrder($purchaseOrder, $payload);

        flash(translate('Proforma invoice created successfully.'))->success();

        return redirect()->route('seller.b2b.proforma-invoices.show', $invoice->id);
    }

    public function supplierShow($id)
    {
        $invoice = $this->supplierQuery()->findOrFail($id);
        $timeline = $invoice->purchaseOrder ? $this->b2bTradeService->buildTradeTimelineForPurchaseOrder($invoice->purchaseOrder) : collect();

        return view('seller.b2b.proforma_invoices.show', compact('invoice', 'timeline'));
    }

    public function supplierSend($id)
    {
        $invoice = $this->supplierQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canManageInvoice(Auth::id(), $invoice->supplier_company_id), 403);

        if ($invoice->status !== 'draft') {
            flash(translate('Only draft proforma invoices can be sent.'))->warning();
            return back();
        }

        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->logInvoiceStatus($invoice, 'invoice_sent', 'Proforma invoice sent by supplier.');
        $this->b2bNotificationService->notifyBuyerAboutProformaInvoice($invoice);

        flash(translate('Proforma invoice sent successfully.'))->success();

        return back();
    }

    public function supplierCancel($id)
    {
        $invoice = $this->supplierQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->canManageInvoice(Auth::id(), $invoice->supplier_company_id), 403);

        if (!in_array($invoice->status, ['draft', 'sent'])) {
            flash(translate('This proforma invoice cannot be cancelled.'))->warning();
            return back();
        }

        $invoice->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'escrow_status' => $invoice->usesEscrow() ? 'cancelled' : $invoice->escrow_status,
            'escrow_cancelled_at' => $invoice->usesEscrow() ? now() : $invoice->escrow_cancelled_at,
        ]);

        $this->logInvoiceStatus($invoice, 'invoice_cancelled', 'Proforma invoice cancelled by supplier.');

        flash(translate('Proforma invoice cancelled successfully.'))->success();

        return back();
    }

    public function adminIndex()
    {
        $invoices = B2BProformaInvoice::with(['buyerCompany', 'supplierCompany', 'purchaseOrder'])
            ->latest()
            ->paginate(20);

        return view('backend.b2b.proforma_invoices.index', compact('invoices'));
    }

    public function adminShow($id)
    {
        $invoice = B2BProformaInvoice::with(['buyerCompany', 'supplierCompany', 'purchaseOrder', 'items', 'shipments.events', 'documents'])
            ->findOrFail($id);
        $timeline = $invoice->purchaseOrder ? $this->b2bTradeService->buildTradeTimelineForPurchaseOrder($invoice->purchaseOrder) : collect();

        return view('backend.b2b.proforma_invoices.show', compact('invoice', 'timeline'));
    }

    public function adminRelease(Request $request, $id)
    {
        $invoice = B2BProformaInvoice::findOrFail($id);

        if (!$invoice->usesEscrow() || !in_array($invoice->escrow_status, ['funded', 'disputed'], true)) {
            flash(translate('This escrow cannot be released right now.'))->warning();
            return back();
        }

        $this->resolveEscrow($invoice, 'released', $request->input('escrow_resolution_notes'), 'admin');
        $this->b2bNotificationService->notifyBuyerAboutProformaInvoice($invoice);
        $this->b2bNotificationService->notifySupplierAboutInvoiceDecision($invoice);

        flash(translate('Escrow released successfully.'))->success();

        return back();
    }

    public function adminRefund(Request $request, $id)
    {
        $invoice = B2BProformaInvoice::findOrFail($id);

        if (!$invoice->usesEscrow() || !in_array($invoice->escrow_status, ['funded', 'disputed'], true)) {
            flash(translate('This escrow cannot be refunded right now.'))->warning();
            return back();
        }

        $this->resolveEscrow($invoice, 'refunded', $request->input('escrow_resolution_notes'), 'admin');
        $this->b2bNotificationService->notifyBuyerAboutProformaInvoice($invoice);
        $this->b2bNotificationService->notifySupplierAboutInvoiceDecision($invoice);

        flash(translate('Escrow refunded successfully.'))->success();

        return back();
    }

    protected function buyerQuery()
    {
        $company = $this->getBuyerCompany();

        return B2BProformaInvoice::with(['buyerCompany', 'supplierCompany', 'purchaseOrder.shipments.events', 'items', 'shipments.events', 'documents'])
            ->with(['milestones.escrow', 'lettersOfCredit', 'financeDisputes.messages', 'financeRefunds', 'escrows.settlements'])
            ->where('buyer_company_id', $company->id);
    }

    protected function supplierQuery()
    {
        $company = $this->getSupplierCompany();

        return B2BProformaInvoice::with(['buyerCompany', 'supplierCompany', 'purchaseOrder.shipments.events', 'items', 'shipments.events', 'documents'])
            ->with(['milestones.escrow', 'lettersOfCredit', 'financeDisputes.messages', 'financeRefunds', 'escrows.settlements'])
            ->where('supplier_company_id', $company->id);
    }

    protected function supplierPurchaseOrderQuery()
    {
        $company = $this->getSupplierCompany();

        return B2BPurchaseOrder::with(['buyerCompany', 'supplierCompany', 'items'])
            ->where('supplier_company_id', $company->id);
    }

    protected function validatedPayload(Request $request, B2BPurchaseOrder $purchaseOrder): array
    {
        $validated = $request->validate([
            'currency' => 'required|string|max:20',
            'incoterm' => 'required|in:' . implode(',', \App\Services\B2BTradeService::INCOTERMS),
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'valid_until' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:100',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.line_total' => 'required|numeric|min:0',
        ]);

        $items = collect($validated['items'])->map(function ($item) {
            $item['tax_amount'] = $item['tax_amount'] ?? 0;
            $item['discount_amount'] = $item['discount_amount'] ?? 0;
            return $item;
        })->values()->all();

        $subtotal = collect($items)->sum('line_total');
        $taxAmount = (float) ($validated['tax_amount'] ?? 0);
        $shippingAmount = (float) ($validated['shipping_amount'] ?? 0);
        $discountAmount = (float) ($validated['discount_amount'] ?? 0);

        return [
            'currency' => $validated['currency'],
            'exchange_rate_snapshot' => $this->currencyService->rateFor($validated['currency']),
            'currency_snapshot' => $this->currencyService->snapshot($validated['currency']),
            'incoterm' => $validated['incoterm'],
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'grand_total' => $subtotal + $taxAmount + $shippingAmount - $discountAmount,
            'valid_until' => $validated['valid_until'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
            'items' => $items,
        ];
    }

    protected function logInvoiceStatus(B2BProformaInvoice $invoice, string $eventType, string $description): void
    {
        $negotiation = B2BNegotiation::where('purchase_order_id', $invoice->purchase_order_id)->first();
        if ($negotiation) {
            $this->b2bTransactionService->addSystemMessage(
                $negotiation,
                Auth::id(),
                $this->b2bCompanyService->getCompanyByUser(Auth::id())?->id,
                'status_change',
                $description,
                ['proforma_invoice_id' => $invoice->id, 'status' => $invoice->status]
            );
        }

        $this->b2bAuditService->log(
            Auth::id(),
            $this->b2bCompanyService->getCompanyByUser(Auth::id())?->id,
            $eventType,
            $invoice,
            $description,
            ['status' => $invoice->status]
        );
    }

    protected function resolveEscrow(B2BProformaInvoice $invoice, string $resolution, ?string $notes = null, string $actor = 'buyer'): void
    {
        $updates = [
            'escrow_status' => $resolution,
            'escrow_resolution' => $resolution,
            'escrow_resolution_notes' => $notes,
        ];

        if ($resolution === 'released') {
            $updates['escrow_released_at'] = now();
            $updates['supplier_paid_out_at'] = now();
        }

        if ($resolution === 'refunded') {
            $updates['escrow_refunded_at'] = now();
        }

        $invoice->update($updates);
        $this->b2bPaymentService->resolveEscrow($invoice, $resolution, $notes, Auth::id());

        if ($resolution === 'released' && $invoice->purchaseOrder && $invoice->purchaseOrder->status !== 'completed') {
            $invoice->purchaseOrder->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        $eventType = $resolution === 'released' ? 'escrow_released' : 'escrow_refunded';
        $description = $resolution === 'released'
            ? 'Escrow released to supplier.'
            : 'Escrow refunded to buyer.';

        $this->logInvoiceStatus($invoice, $eventType, $description . ' Resolved by ' . $actor . '.');
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
