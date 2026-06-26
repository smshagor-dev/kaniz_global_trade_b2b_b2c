<?php

namespace App\Http\Controllers;

use App\Models\B2BNegotiation;
use App\Models\B2BPurchaseOrder;
use App\Services\B2BAuditService;
use App\Services\B2BCompanyService;
use App\Services\B2BNotificationService;
use App\Services\B2BPermissionService;
use App\Services\B2BTradeService;
use App\Services\B2BTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BPurchaseOrderController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BTransactionService $b2bTransactionService,
        protected B2BAuditService $b2bAuditService,
        protected B2BNotificationService $b2bNotificationService,
        protected B2BPermissionService $b2bPermissionService,
        protected B2BTradeService $b2bTradeService
    ) {
    }

    public function buyerIndex()
    {
        $company = $this->getBuyerCompany();
        $purchaseOrders = B2BPurchaseOrder::with(['supplierCompany', 'quotation', 'rfq'])
            ->where('buyer_company_id', $company->id)
            ->latest()
            ->paginate(15);

        return view('b2b.purchase_orders.index', compact('purchaseOrders'));
    }

    public function buyerShow($id)
    {
        $purchaseOrder = $this->buyerQuery()->findOrFail($id);
        $timeline = $this->b2bTradeService->buildTradeTimelineForPurchaseOrder($purchaseOrder);

        return view('b2b.purchase_orders.show', compact('purchaseOrder', 'timeline'));
    }

    public function buyerCancel($id)
    {
        $purchaseOrder = $this->buyerQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $purchaseOrder->buyer_company_id, ['owner', 'admin', 'procurement_manager']), 403);

        if (!in_array($purchaseOrder->status, ['draft', 'sent'])) {
            flash(translate('Only draft or sent purchase orders can be cancelled.'))->warning();
            return back();
        }

        $purchaseOrder->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->logPurchaseOrderStatus($purchaseOrder, 'po_cancelled', 'Purchase order cancelled by buyer.');

        flash(translate('Purchase order cancelled successfully.'))->success();

        return back();
    }

    public function buyerComplete($id)
    {
        $purchaseOrder = $this->buyerQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $purchaseOrder->buyer_company_id, ['owner', 'admin', 'procurement_manager']), 403);

        $invoice = $purchaseOrder->proformaInvoices->sortByDesc('created_at')->first();
        if ($invoice && $invoice->usesEscrow() && !in_array($invoice->escrow_status, ['released', 'refunded'], true)) {
            flash(translate('Escrow must be released or refunded before completing this purchase order.'))->warning();
            return back();
        }

        if ($purchaseOrder->status !== 'accepted') {
            flash(translate('Only accepted purchase orders can be marked completed.'))->warning();
            return back();
        }

        $purchaseOrder->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->logPurchaseOrderStatus($purchaseOrder, 'po_completed', 'Purchase order marked completed by buyer.');

        flash(translate('Purchase order completed successfully.'))->success();

        return back();
    }

    public function supplierIndex()
    {
        $company = $this->getSupplierCompany();
        $purchaseOrders = B2BPurchaseOrder::with(['buyerCompany', 'quotation', 'rfq'])
            ->where('supplier_company_id', $company->id)
            ->latest()
            ->paginate(15);

        return view('seller.b2b.purchase_orders.index', compact('purchaseOrders'));
    }

    public function supplierShow($id)
    {
        $purchaseOrder = $this->supplierQuery()->findOrFail($id);
        $timeline = $this->b2bTradeService->buildTradeTimelineForPurchaseOrder($purchaseOrder);

        return view('seller.b2b.purchase_orders.show', compact('purchaseOrder', 'timeline'));
    }

    public function supplierAccept($id)
    {
        $purchaseOrder = $this->supplierQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $purchaseOrder->supplier_company_id, ['owner', 'admin', 'sales_manager']), 403);

        if (!in_array($purchaseOrder->status, ['draft', 'sent'])) {
            flash(translate('This purchase order cannot be accepted.'))->warning();
            return back();
        }

        $purchaseOrder->update([
            'status' => 'accepted',
            'supplier_reviewed_at' => now(),
            'accepted_at' => now(),
        ]);

        $this->logPurchaseOrderStatus($purchaseOrder, 'po_accepted', 'Purchase order accepted by supplier.');
        $this->b2bNotificationService->notifyBuyerAboutPurchaseOrderDecision($purchaseOrder);

        flash(translate('Purchase order accepted successfully.'))->success();

        return back();
    }

    public function supplierReject($id)
    {
        $purchaseOrder = $this->supplierQuery()->findOrFail($id);
        abort_unless($this->b2bPermissionService->hasRole(Auth::id(), $purchaseOrder->supplier_company_id, ['owner', 'admin', 'sales_manager']), 403);

        if (!in_array($purchaseOrder->status, ['draft', 'sent'])) {
            flash(translate('This purchase order cannot be rejected.'))->warning();
            return back();
        }

        $purchaseOrder->update([
            'status' => 'rejected',
            'supplier_reviewed_at' => now(),
            'rejected_at' => now(),
        ]);

        $this->logPurchaseOrderStatus($purchaseOrder, 'po_rejected', 'Purchase order rejected by supplier.');
        $this->b2bNotificationService->notifyBuyerAboutPurchaseOrderDecision($purchaseOrder);

        flash(translate('Purchase order rejected successfully.'))->success();

        return back();
    }

    public function adminIndex()
    {
        $purchaseOrders = B2BPurchaseOrder::with(['buyerCompany', 'supplierCompany', 'rfq'])
            ->latest()
            ->paginate(20);

        return view('backend.b2b.purchase_orders.index', compact('purchaseOrders'));
    }

    public function adminShow($id)
    {
        $purchaseOrder = B2BPurchaseOrder::with(['buyerCompany', 'supplierCompany', 'rfq', 'quotation', 'items', 'proformaInvoices', 'shippingQuotes.shippingProvider', 'shipments.events', 'documents'])
            ->findOrFail($id);
        $timeline = $this->b2bTradeService->buildTradeTimelineForPurchaseOrder($purchaseOrder);

        return view('backend.b2b.purchase_orders.show', compact('purchaseOrder', 'timeline'));
    }

    protected function buyerQuery()
    {
        $company = $this->getBuyerCompany();

        return B2BPurchaseOrder::with(['buyerCompany', 'supplierCompany', 'rfq', 'quotation', 'items', 'proformaInvoices', 'shippingQuotes.shippingProvider', 'shipments.events', 'documents', 'negotiation.messages.sender'])
            ->with(['milestones.escrow', 'lettersOfCredit', 'financeDisputes.messages'])
            ->where('buyer_company_id', $company->id);
    }

    protected function supplierQuery()
    {
        $company = $this->getSupplierCompany();

        return B2BPurchaseOrder::with(['buyerCompany', 'supplierCompany', 'rfq', 'quotation', 'items', 'proformaInvoices', 'shippingQuotes.shippingProvider', 'shipments.events', 'documents', 'negotiation.messages.sender'])
            ->with(['milestones.escrow', 'lettersOfCredit', 'financeDisputes.messages'])
            ->where('supplier_company_id', $company->id);
    }

    protected function logPurchaseOrderStatus(B2BPurchaseOrder $purchaseOrder, string $eventType, string $description): void
    {
        $negotiation = $purchaseOrder->negotiation ?: B2BNegotiation::where('quotation_id', $purchaseOrder->quotation_id)->first();
        if ($negotiation) {
            $this->b2bTransactionService->addSystemMessage(
                $negotiation,
                Auth::id(),
                $this->b2bCompanyService->getCompanyByUser(Auth::id())?->id,
                'status_change',
                $description,
                ['purchase_order_id' => $purchaseOrder->id, 'status' => $purchaseOrder->status]
            );
        }

        $this->b2bAuditService->log(
            Auth::id(),
            $this->b2bCompanyService->getCompanyByUser(Auth::id())?->id,
            $eventType,
            $purchaseOrder,
            $description,
            ['status' => $purchaseOrder->status]
        );
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
