<?php

namespace App\Services;

use App\Models\B2BEscrow;
use App\Models\B2BFinanceDispute;
use App\Models\B2BFinanceDisputeMessage;
use App\Models\B2BFinanceRefund;
use App\Models\B2BLetterOfCredit;
use App\Models\B2BPaymentMilestone;
use App\Models\B2BPaymentTransaction;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BSettlement;
use App\Models\B2BSettlementLog;
use App\Services\Currency\CurrencyService;
use Illuminate\Support\Facades\DB;

class B2BTradeFinanceService
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected B2BAuditService $auditService,
        protected B2BNotificationService $notificationService
    ) {
    }

    public function createMilestones(B2BPurchaseOrder $purchaseOrder, array $milestones, int $actorUserId, ?int $actorCompanyId = null): void
    {
        DB::transaction(function () use ($purchaseOrder, $milestones, $actorUserId, $actorCompanyId) {
            B2BPaymentMilestone::where('purchase_order_id', $purchaseOrder->id)->delete();
            $currencySnapshot = $purchaseOrder->currency_snapshot ?: $this->currencyService->snapshot($purchaseOrder->currency);
            $total = (float) ($purchaseOrder->proformaInvoices()->latest('id')->value('buyer_payable_total') ?: $purchaseOrder->total_amount);

            foreach (array_values($milestones) as $index => $milestone) {
                $percentage = round((float) ($milestone['percentage'] ?? 0), 2);
                $amount = round($total * ($percentage / 100), 2);

                B2BPaymentMilestone::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'proforma_invoice_id' => $purchaseOrder->proformaInvoices()->latest('id')->value('id'),
                    'buyer_company_id' => $purchaseOrder->buyer_company_id,
                    'supplier_company_id' => $purchaseOrder->supplier_company_id,
                    'title' => $milestone['title'],
                    'trigger_event' => $milestone['trigger_event'] ?? 'custom',
                    'sort_order' => $index + 1,
                    'percentage' => $percentage,
                    'amount' => $amount,
                    'currency' => $purchaseOrder->currency,
                    'exchange_rate_snapshot' => $purchaseOrder->exchange_rate_snapshot ?: $this->currencyService->rateFor($purchaseOrder->currency),
                    'currency_snapshot' => $currencySnapshot,
                    'status' => 'pending',
                    'scheduled_release_at' => $milestone['scheduled_release_at'] ?? null,
                    'due_at' => $milestone['due_at'] ?? null,
                    'notes' => $milestone['notes'] ?? null,
                ]);
            }

            $this->auditService->log($actorUserId, $actorCompanyId, 'milestones_configured', $purchaseOrder, 'Payment milestones configured.', [
                'milestone_count' => count($milestones),
            ]);
        });
    }

    public function fundMilestone(B2BPaymentMilestone $milestone, array $data, int $actorUserId, ?int $actorCompanyId = null): B2BPaymentMilestone
    {
        return DB::transaction(function () use ($milestone, $data, $actorUserId, $actorCompanyId) {
            $transaction = B2BPaymentTransaction::create([
                'reference_type' => B2BPaymentMilestone::class,
                'reference_id' => $milestone->id,
                'buyer_company_id' => $milestone->buyer_company_id,
                'supplier_company_id' => $milestone->supplier_company_id,
                'payment_gateway' => $data['payment_gateway'] ?? 'existing_manual_flow',
                'payment_method' => $data['payment_method'] ?? 'milestone',
                'currency' => $milestone->currency,
                'settlement_currency' => $data['settlement_currency'] ?? $milestone->currency,
                'exchange_rate_snapshot' => $milestone->exchange_rate_snapshot ?: 1,
                'settlement_exchange_rate_snapshot' => $milestone->exchange_rate_snapshot ?: 1,
                'amount' => $milestone->amount,
                'gateway_reference' => $data['reference'] ?? null,
                'reference_number' => $data['reference'] ?? null,
                'swift' => $data['swift'] ?? null,
                'iban' => $data['iban'] ?? null,
                'status' => 'paid',
                'paid_at' => now(),
                'meta' => ['milestone' => $milestone->title],
                'currency_snapshot' => $milestone->currency_snapshot,
            ]);

            $escrow = B2BEscrow::create([
                'reference_type' => B2BPaymentMilestone::class,
                'reference_id' => $milestone->id,
                'buyer_company_id' => $milestone->buyer_company_id,
                'supplier_company_id' => $milestone->supplier_company_id,
                'payment_transaction_id' => $transaction->id,
                'currency' => $milestone->currency,
                'settlement_currency' => $data['settlement_currency'] ?? $milestone->currency,
                'exchange_rate_snapshot' => $milestone->exchange_rate_snapshot ?: 1,
                'settlement_exchange_rate_snapshot' => $milestone->exchange_rate_snapshot ?: 1,
                'amount' => $milestone->amount,
                'funded_amount' => $milestone->amount,
                'status' => 'funded',
                'funded_at' => now(),
                'last_action_by' => $actorUserId,
                'currency_snapshot' => $milestone->currency_snapshot,
                'meta' => ['milestone' => $milestone->title],
            ]);

            $milestone->update([
                'payment_transaction_id' => $transaction->id,
                'escrow_id' => $escrow->id,
                'status' => 'funded',
                'paid_at' => now(),
            ]);

            $this->auditService->log($actorUserId, $actorCompanyId, 'milestone_funded', $milestone, 'Milestone funded.', [
                'amount' => $milestone->amount,
            ]);

            return $milestone->fresh();
        });
    }

    public function releaseMilestone(B2BPaymentMilestone $milestone, int $actorUserId, ?int $actorCompanyId = null, ?string $notes = null): B2BPaymentMilestone
    {
        return DB::transaction(function () use ($milestone, $actorUserId, $actorCompanyId, $notes) {
            $milestone->escrow?->update([
                'status' => 'released',
                'released_amount' => $milestone->amount,
                'released_at' => now(),
                'last_action_by' => $actorUserId,
            ]);

            $milestone->update([
                'status' => 'released',
                'released_at' => now(),
                'approved_by' => $actorUserId,
                'approved_at' => now(),
            ]);

            $this->auditService->log($actorUserId, $actorCompanyId, 'milestone_released', $milestone, 'Milestone released.', [
                'notes' => $notes,
            ]);

            return $milestone->fresh();
        });
    }

    public function createLetterOfCredit(B2BPurchaseOrder $purchaseOrder, array $data, int $actorUserId, ?int $actorCompanyId = null): B2BLetterOfCredit
    {
        $lc = B2BLetterOfCredit::create([
            'purchase_order_id' => $purchaseOrder->id,
            'proforma_invoice_id' => $purchaseOrder->proformaInvoices()->latest('id')->value('id'),
            'buyer_company_id' => $purchaseOrder->buyer_company_id,
            'supplier_company_id' => $purchaseOrder->supplier_company_id,
            'lc_number' => $data['lc_number'],
            'issuing_bank' => $data['issuing_bank'],
            'advising_bank' => $data['advising_bank'] ?? null,
            'expiry_date' => $data['expiry_date'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'required_documents' => $data['required_documents'] ?? [],
            'status' => 'requested',
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        $this->auditService->log($actorUserId, $actorCompanyId, 'lc_requested', $lc, 'Letter of credit requested.');

        return $lc;
    }

    public function updateLetterOfCreditStatus(B2BLetterOfCredit $lc, string $status, int $actorUserId, ?int $actorCompanyId = null, ?string $notes = null): B2BLetterOfCredit
    {
        $lc->update([
            'status' => $status,
            'review_notes' => $notes ?: $lc->review_notes,
            'approved_at' => $status === 'approved' ? now() : $lc->approved_at,
            'completed_at' => $status === 'completed' ? now() : $lc->completed_at,
        ]);

        $this->auditService->log($actorUserId, $actorCompanyId, 'lc_status_updated', $lc, 'Letter of credit status updated.', [
            'status' => $status,
        ]);

        return $lc->fresh();
    }

    public function requestSettlement(B2BEscrow $escrow, array $data, int $actorUserId, ?int $actorCompanyId = null): B2BSettlement
    {
        $fees = round((float) ($data['fees'] ?? 0), 2);
        $settlement = B2BSettlement::create([
            'supplier_company_id' => $escrow->supplier_company_id,
            'payment_transaction_id' => $escrow->payment_transaction_id,
            'escrow_id' => $escrow->id,
            'settlement_method' => $data['settlement_method'],
            'currency' => $escrow->settlement_currency ?: $escrow->currency,
            'amount' => $escrow->released_amount ?: $escrow->amount,
            'fees' => $fees,
            'net_amount' => ($escrow->released_amount ?: $escrow->amount) - $fees,
            'reference' => $data['reference'] ?? null,
            'destination_details' => $data['destination_details'] ?? [],
            'status' => 'pending_approval',
            'requested_by' => $actorUserId,
            'requested_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        B2BSettlementLog::create([
            'settlement_id' => $settlement->id,
            'action' => 'requested',
            'performed_by' => $actorUserId,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->auditService->log($actorUserId, $actorCompanyId, 'settlement_requested', $settlement, 'Settlement requested.');
        $this->notificationService->notifySupplierPayoutRequested($settlement);

        return $settlement;
    }

    public function approveSettlement(B2BSettlement $settlement, int $actorUserId, ?int $actorCompanyId = null, ?string $notes = null): B2BSettlement
    {
        $settlement->update([
            'status' => 'approved',
            'approved_by' => $actorUserId,
            'approved_at' => now(),
            'notes' => $notes ?: $settlement->notes,
        ]);

        B2BSettlementLog::create([
            'settlement_id' => $settlement->id,
            'action' => 'approved',
            'performed_by' => $actorUserId,
            'notes' => $notes,
        ]);

        $this->auditService->log($actorUserId, $actorCompanyId, 'settlement_approved', $settlement, 'Settlement approved.');
        $this->notificationService->notifySupplierPayoutApproved($settlement);

        return $settlement->fresh();
    }

    public function completeSettlement(B2BSettlement $settlement, int $actorUserId, ?int $actorCompanyId = null, ?string $notes = null): B2BSettlement
    {
        $settlement->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $notes ?: $settlement->notes,
        ]);

        B2BSettlementLog::create([
            'settlement_id' => $settlement->id,
            'action' => 'completed',
            'performed_by' => $actorUserId,
            'notes' => $notes,
        ]);

        $this->auditService->log($actorUserId, $actorCompanyId, 'settlement_completed', $settlement, 'Settlement completed.');
        $this->notificationService->notifySupplierPayoutCompleted($settlement);

        return $settlement->fresh();
    }

    public function createDispute(array $data, int $actorUserId, ?int $actorCompanyId = null): B2BFinanceDispute
    {
        $dispute = B2BFinanceDispute::create([
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'purchase_order_id' => $data['purchase_order_id'] ?? null,
            'proforma_invoice_id' => $data['proforma_invoice_id'] ?? null,
            'buyer_company_id' => $data['buyer_company_id'],
            'supplier_company_id' => $data['supplier_company_id'],
            'created_by' => $actorUserId,
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'],
            'evidence' => $data['evidence'] ?? [],
            'status' => 'open',
            'escrow_hold' => (bool) ($data['escrow_hold'] ?? true),
        ]);

        if ($dispute->escrow_hold && $data['proforma_invoice_id']) {
            B2BEscrow::where('reference_type', B2BProformaInvoice::class)
                ->where('reference_id', $data['proforma_invoice_id'])
                ->update(['status' => 'disputed', 'disputed_at' => now(), 'last_action_by' => $actorUserId]);
        }

        $this->auditService->log($actorUserId, $actorCompanyId, 'dispute_created', $dispute, 'Finance dispute created.');

        return $dispute;
    }

    public function addDisputeMessage(B2BFinanceDispute $dispute, array $data, int $actorUserId): B2BFinanceDisputeMessage
    {
        return B2BFinanceDisputeMessage::create([
            'dispute_id' => $dispute->id,
            'sender_user_id' => $actorUserId,
            'sender_company_id' => $data['sender_company_id'] ?? null,
            'message' => $data['message'],
            'attachments' => $data['attachments'] ?? [],
        ]);
    }

    public function resolveDispute(B2BFinanceDispute $dispute, array $data, int $actorUserId, ?int $actorCompanyId = null): B2BFinanceDispute
    {
        $dispute->update([
            'status' => 'resolved',
            'resolution' => $data['resolution'],
            'resolution_notes' => $data['resolution_notes'] ?? null,
            'resolved_by' => $actorUserId,
            'resolved_at' => now(),
        ]);

        if ($dispute->proforma_invoice_id) {
            B2BEscrow::where('reference_type', B2BProformaInvoice::class)
                ->where('reference_id', $dispute->proforma_invoice_id)
                ->where('status', 'disputed')
                ->update(['status' => $data['resolution'] === 'refund' ? 'refunded' : 'funded']);
        }

        $this->auditService->log($actorUserId, $actorCompanyId, 'dispute_resolved', $dispute, 'Finance dispute resolved.', [
            'resolution' => $data['resolution'],
        ]);

        return $dispute->fresh();
    }

    public function requestRefund(array $data, int $actorUserId, ?int $actorCompanyId = null): B2BFinanceRefund
    {
        $refund = B2BFinanceRefund::create([
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'payment_transaction_id' => $data['payment_transaction_id'] ?? null,
            'escrow_id' => $data['escrow_id'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'refund_type' => $data['refund_type'],
            'status' => 'pending_approval',
            'reason' => $data['reason'],
            'requested_by' => $actorUserId,
            'notes' => $data['notes'] ?? null,
            'meta' => $data['meta'] ?? [],
        ]);

        $this->auditService->log($actorUserId, $actorCompanyId, 'refund_requested', $refund, 'Refund requested.');

        return $refund;
    }

    public function approveRefund(B2BFinanceRefund $refund, int $actorUserId, ?int $actorCompanyId = null, ?string $notes = null): B2BFinanceRefund
    {
        $refund->update([
            'status' => 'approved',
            'approved_by' => $actorUserId,
            'notes' => $notes ?: $refund->notes,
        ]);

        $this->auditService->log($actorUserId, $actorCompanyId, 'refund_approved', $refund, 'Refund approved.');

        return $refund->fresh();
    }

    public function completeRefund(B2BFinanceRefund $refund, int $actorUserId, ?int $actorCompanyId = null, ?string $notes = null): B2BFinanceRefund
    {
        $refund->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $notes ?: $refund->notes,
        ]);

        if ($refund->escrow_id) {
            B2BEscrow::whereKey($refund->escrow_id)->update([
                'status' => 'refunded',
                'refunded_amount' => $refund->amount,
                'refunded_at' => now(),
                'last_action_by' => $actorUserId,
            ]);
        }

        $this->auditService->log($actorUserId, $actorCompanyId, 'refund_completed', $refund, 'Refund completed.');

        return $refund->fresh();
    }

    public function processDueReleasesAndExpiries(): array
    {
        $released = 0;
        $expired = 0;

        B2BPaymentMilestone::where('status', 'funded')
            ->whereNotNull('scheduled_release_at')
            ->where('scheduled_release_at', '<=', now())
            ->chunkById(50, function ($milestones) use (&$released) {
                foreach ($milestones as $milestone) {
                    $this->releaseMilestone($milestone, 0, null, 'Automatic scheduled release');
                    $released++;
                }
            });

        B2BEscrow::where('status', 'funded')
            ->whereNotNull('meta')
            ->get()
            ->each(function (B2BEscrow $escrow) use (&$expired) {
                $expiry = data_get($escrow->meta, 'expiry_at');
                if ($expiry && now()->greaterThan($expiry)) {
                    $escrow->update(['status' => 'expired']);
                    $expired++;
                }
            });

        return compact('released', 'expired');
    }
}
