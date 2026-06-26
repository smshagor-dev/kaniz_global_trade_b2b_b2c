<?php

namespace App\Services;

use App\Models\B2BEscrow;
use App\Models\B2BEscrowLog;
use App\Models\B2BPaymentTransaction;
use App\Models\B2BProformaInvoice;
use App\Services\Currency\CurrencyService;
use Illuminate\Support\Facades\DB;

class B2BPaymentService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {
    }

    public function recordInvoiceFunding(B2BProformaInvoice $invoice, array $data, ?int $actorId = null): array
    {
        return DB::transaction(function () use ($invoice, $data, $actorId) {
            $snapshot = $invoice->currency_snapshot ?: $this->currencyService->snapshot($invoice->currency);
            $rateSnapshot = (float) ($invoice->exchange_rate_snapshot ?: ($snapshot['exchange_rate'] ?? 1));

            $transaction = B2BPaymentTransaction::create([
                'reference_type' => B2BProformaInvoice::class,
                'reference_id' => $invoice->id,
                'buyer_company_id' => $invoice->buyer_company_id,
                'supplier_company_id' => $invoice->supplier_company_id,
                'payment_gateway' => $data['payment_gateway'] ?? 'existing_manual_flow',
                'payment_method' => $data['payment_method'] ?? 'trade_finance',
                'currency' => $invoice->currency,
                'settlement_currency' => $data['settlement_currency'] ?? $invoice->currency,
                'exchange_rate_snapshot' => $rateSnapshot,
                'settlement_exchange_rate_snapshot' => $data['settlement_exchange_rate_snapshot'] ?? $rateSnapshot,
                'amount' => $invoice->buyer_payable_total ?: $invoice->grand_total,
                'gateway_reference' => $data['gateway_reference'] ?? $data['escrow_payment_reference'],
                'reference_number' => $data['reference_number'] ?? $data['escrow_payment_reference'],
                'swift' => $data['swift'] ?? null,
                'iban' => $data['iban'] ?? null,
                'receipt_path' => $data['receipt_path'] ?? null,
                'status' => 'paid',
                'paid_at' => now(),
                'meta' => [
                    'notes' => $data['notes'] ?? null,
                    'source' => 'b2b_invoice_escrow_funding',
                ],
                'currency_snapshot' => $snapshot,
            ]);

            $escrow = B2BEscrow::updateOrCreate(
                [
                    'reference_type' => B2BProformaInvoice::class,
                    'reference_id' => $invoice->id,
                ],
                [
                    'buyer_company_id' => $invoice->buyer_company_id,
                    'supplier_company_id' => $invoice->supplier_company_id,
                    'payment_transaction_id' => $transaction->id,
                    'currency' => $invoice->currency,
                    'settlement_currency' => $data['settlement_currency'] ?? $invoice->currency,
                    'exchange_rate_snapshot' => $rateSnapshot,
                    'settlement_exchange_rate_snapshot' => $data['settlement_exchange_rate_snapshot'] ?? $rateSnapshot,
                    'amount' => $invoice->buyer_payable_total ?: $invoice->grand_total,
                    'funded_amount' => $invoice->buyer_payable_total ?: $invoice->grand_total,
                    'status' => 'funded',
                    'funded_at' => now(),
                    'last_action_by' => $actorId,
                    'currency_snapshot' => $snapshot,
                    'meta' => ['invoice_number' => $invoice->invoice_number],
                ]
            );

            $this->logEscrow($escrow, 'funded', $actorId, $data['notes'] ?? 'Escrow funded.');

            return compact('transaction', 'escrow');
        });
    }

    public function resolveEscrow(B2BProformaInvoice $invoice, string $resolution, ?string $notes = null, ?int $actorId = null): ?B2BEscrow
    {
        return DB::transaction(function () use ($invoice, $resolution, $notes, $actorId) {
            $escrow = B2BEscrow::where('reference_type', B2BProformaInvoice::class)
                ->where('reference_id', $invoice->id)
                ->latest('id')
                ->first();

            if (!$escrow) {
                return null;
            }

            $amount = (float) ($invoice->buyer_payable_total ?: $invoice->grand_total);
            $updates = [
                'status' => $resolution,
                'last_action_by' => $actorId,
            ];

            if ($resolution === 'released') {
                $updates['released_amount'] = $amount;
                $updates['released_at'] = now();
            }

            if ($resolution === 'refunded') {
                $updates['refunded_amount'] = $amount;
                $updates['refunded_at'] = now();
            }

            if ($resolution === 'disputed') {
                $updates['disputed_at'] = now();
            }

            $escrow->update($updates);
            $this->logEscrow($escrow, $resolution, $actorId, $notes);

            return $escrow->fresh();
        });
    }

    protected function logEscrow(B2BEscrow $escrow, string $action, ?int $actorId = null, ?string $notes = null): B2BEscrowLog
    {
        return B2BEscrowLog::create([
            'escrow_id' => $escrow->id,
            'action' => $action,
            'performed_by' => $actorId,
            'notes' => $notes,
            'meta' => [
                'status' => $escrow->status,
                'amount' => $escrow->amount,
            ],
        ]);
    }
}
