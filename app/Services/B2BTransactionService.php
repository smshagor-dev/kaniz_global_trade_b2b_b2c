<?php

namespace App\Services;

use App\Models\Address;
use App\Models\B2BCompany;
use App\Models\B2BNegotiation;
use App\Models\B2BNegotiationMessage;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BQuotation;
use App\Models\B2BRfq;
use App\Models\User;
use App\Services\Currency\CurrencyService;
use Illuminate\Support\Facades\DB;

class B2BTransactionService
{
    public function __construct(
        protected B2BAuditService $b2bAuditService,
        protected B2BNotificationService $b2bNotificationService,
        protected B2BOrderPlatformFeeService $orderPlatformFeeService,
        protected B2BEscrowFeeService $escrowFeeService,
        protected CurrencyService $currencyService
    ) {
    }

    public function createPurchaseOrderFromQuotation(B2BQuotation $quotation): B2BPurchaseOrder
    {
        $quotation->loadMissing(['rfq.company', 'rfq.product', 'supplierCompany', 'supplier']);

        $existing = B2BPurchaseOrder::where('quotation_id', $quotation->id)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($quotation) {
            $rfq = $quotation->rfq;
            $buyer = User::findOrFail($rfq->user_id);
            $buyerCompany = $rfq->company;
            $supplierCompany = $quotation->supplierCompany;
            $deliveryAddress = $this->resolveDeliveryAddress($buyer, $buyerCompany, $rfq);
            $subtotal = round((float) $quotation->price * (float) $rfq->quantity, 2);
            $currencyCode = $quotation->currency ?: ($rfq->currency ?: get_system_default_currency()->code);
            $exchangeRateSnapshot = $quotation->exchange_rate_snapshot
                ?: $rfq->exchange_rate_snapshot
                ?: $this->currencyService->rateFor($currencyCode);
            $currencySnapshot = $quotation->currency_snapshot
                ?: $rfq->currency_snapshot
                ?: $this->currencyService->snapshot($currencyCode);

            $purchaseOrder = B2BPurchaseOrder::create([
                'po_number' => $this->nextPurchaseOrderNumber(),
                'buyer_user_id' => $buyer->id,
                'supplier_user_id' => $quotation->supplier_user_id,
                'buyer_company_id' => $buyerCompany->id,
                'supplier_company_id' => $supplierCompany->id,
                'rfq_id' => $rfq->id,
                'quotation_id' => $quotation->id,
                'currency' => $currencyCode,
                'exchange_rate_snapshot' => (float) $exchangeRateSnapshot,
                'currency_snapshot' => $currencySnapshot,
                'payment_terms' => $quotation->payment_terms,
                'shipping_terms' => $quotation->shipping_terms,
                'incoterms' => $quotation->incoterm ?: $rfq->incoterm,
                'delivery_address' => $deliveryAddress,
                'delivery_deadline' => $rfq->expected_delivery_date,
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'notes' => $quotation->message,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $purchaseOrder->items()->create([
                'product_id' => $quotation->product_id ?: $rfq->product_id,
                'product_name' => $quotation->product?->getTranslation('name') ?? $rfq->product?->getTranslation('name') ?? $rfq->title,
                'description' => $rfq->description,
                'quantity' => $rfq->quantity,
                'unit' => $rfq->unit,
                'unit_price' => $quotation->price,
                'line_total' => $subtotal,
                'meta' => [
                    'rfq_target_price' => $rfq->target_price,
                    'quotation_moq' => $quotation->moq,
                    'quotation_lead_time_days' => $quotation->lead_time_days,
                ],
            ]);

            $negotiation = $this->ensureNegotiation($rfq, $quotation, $purchaseOrder);
            $this->syncNegotiationPurchaseOrder($negotiation, $purchaseOrder);
            $this->addSystemMessage($negotiation, $buyer->id, $buyerCompany->id, 'status_change', 'Purchase order generated from accepted quotation.', [
                'purchase_order_id' => $purchaseOrder->id,
                'quotation_id' => $quotation->id,
            ]);

            $this->b2bAuditService->log($buyer->id, $buyerCompany->id, 'po_generated', $purchaseOrder, 'Purchase order generated from quotation acceptance.', [
                'quotation_id' => $quotation->id,
                'rfq_id' => $rfq->id,
            ]);

            $this->b2bNotificationService->notifySupplierAboutPurchaseOrder($purchaseOrder);

            return $purchaseOrder;
        });
    }

    public function ensureNegotiation(B2BRfq $rfq, B2BQuotation $quotation, ?B2BPurchaseOrder $purchaseOrder = null): B2BNegotiation
    {
        return B2BNegotiation::firstOrCreate(
            [
                'rfq_id' => $rfq->id,
                'quotation_id' => $quotation->id,
            ],
            [
                'purchase_order_id' => $purchaseOrder?->id,
                'buyer_company_id' => $rfq->b2b_company_id,
                'supplier_company_id' => $quotation->supplier_company_id,
                'buyer_user_id' => $rfq->user_id,
                'supplier_user_id' => $quotation->supplier_user_id,
                'last_message_at' => now(),
            ]
        );
    }

    public function syncNegotiationPurchaseOrder(B2BNegotiation $negotiation, B2BPurchaseOrder $purchaseOrder): void
    {
        if ($negotiation->purchase_order_id !== $purchaseOrder->id) {
            $negotiation->update(['purchase_order_id' => $purchaseOrder->id]);
        }
    }

    public function createProformaInvoiceFromPurchaseOrder(B2BPurchaseOrder $purchaseOrder, array $payload): B2BProformaInvoice
    {
        return DB::transaction(function () use ($purchaseOrder, $payload) {
            $payload = $this->orderPlatformFeeService->applyToInvoicePayload($payload);
            $payload = $this->escrowFeeService->applyToInvoicePayload($payload);
            $currencyCode = $payload['currency'] ?? $purchaseOrder->currency;
            $exchangeRateSnapshot = $payload['exchange_rate_snapshot']
                ?? $purchaseOrder->exchange_rate_snapshot
                ?? $this->currencyService->rateFor($currencyCode);
            $currencySnapshot = $payload['currency_snapshot']
                ?? $purchaseOrder->currency_snapshot
                ?? $this->currencyService->snapshot($currencyCode);

            $invoice = B2BProformaInvoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'purchase_order_id' => $purchaseOrder->id,
                'quotation_id' => $purchaseOrder->quotation_id,
                'buyer_user_id' => $purchaseOrder->buyer_user_id,
                'supplier_user_id' => $purchaseOrder->supplier_user_id,
                'buyer_company_id' => $purchaseOrder->buyer_company_id,
                'supplier_company_id' => $purchaseOrder->supplier_company_id,
                'currency' => $currencyCode,
                'exchange_rate_snapshot' => (float) $exchangeRateSnapshot,
                'currency_snapshot' => $currencySnapshot,
                'incoterm' => $payload['incoterm'] ?? $purchaseOrder->incoterms,
                'subtotal' => $payload['subtotal'],
                'tax_amount' => $payload['tax_amount'],
                'shipping_amount' => $payload['shipping_amount'],
                'discount_amount' => $payload['discount_amount'],
                'grand_total' => $payload['grand_total'],
                'platform_fee_percent_snapshot' => $payload['platform_fee_percent_snapshot'] ?? 0,
                'platform_fee_fixed_snapshot' => $payload['platform_fee_fixed_snapshot'] ?? 0,
                'platform_fee_amount' => $payload['platform_fee_amount'] ?? 0,
                'supplier_payout_amount' => $payload['supplier_payout_amount'] ?? $payload['grand_total'],
                'buyer_payable_total' => $payload['buyer_payable_total'] ?? $payload['grand_total'],
                'escrow_fee_percent_snapshot' => $payload['escrow_fee_percent_snapshot'] ?? 0,
                'escrow_fee_fixed_snapshot' => $payload['escrow_fee_fixed_snapshot'] ?? 0,
                'escrow_fee_amount' => $payload['escrow_fee_amount'] ?? 0,
                'escrow_status' => ($payload['escrow_fee_amount'] ?? 0) > 0
                    ? (($payload['status'] ?? 'draft') === 'accepted' ? 'awaiting_payment' : 'pending')
                    : 'not_applicable',
                'valid_until' => $payload['valid_until'],
                'notes' => $payload['notes'] ?? null,
                'status' => $payload['status'] ?? 'draft',
                'sent_at' => ($payload['status'] ?? 'draft') === 'sent' ? now() : null,
            ]);

            foreach ($payload['items'] as $item) {
                $invoice->items()->create($item);
            }

            if ($purchaseOrder->relationLoaded('negotiation')) {
                $negotiation = $purchaseOrder->negotiation;
            } else {
                $negotiation = $purchaseOrder->negotiation()->first();
            }

            if ($negotiation) {
                $this->addSystemMessage(
                    $negotiation,
                    $purchaseOrder->supplier_user_id,
                    $purchaseOrder->supplier_company_id,
                    'status_change',
                    'Proforma invoice generated for this purchase order.',
                    ['proforma_invoice_id' => $invoice->id]
                );
            }

            $this->b2bAuditService->log($purchaseOrder->supplier_user_id, $purchaseOrder->supplier_company_id, 'invoice_generated', $invoice, 'Proforma invoice generated from purchase order.', [
                'purchase_order_id' => $purchaseOrder->id,
            ]);

            $this->b2bNotificationService->notifyBuyerAboutProformaInvoice($invoice);

            return $invoice;
        });
    }

    public function addSystemMessage(B2BNegotiation $negotiation, ?int $senderUserId, ?int $senderCompanyId, string $messageType, string $message, array $meta = []): B2BNegotiationMessage
    {
        $entry = $negotiation->messages()->create([
            'sender_user_id' => $senderUserId ?? $negotiation->buyer_user_id,
            'sender_company_id' => $senderCompanyId,
            'sender_role' => 'system',
            'message_type' => $messageType,
            'message' => $message,
            'meta' => $meta,
            'buyer_read_at' => now(),
            'supplier_read_at' => null,
        ]);

        $negotiation->update(['last_message_at' => $entry->created_at]);

        return $entry;
    }

    protected function resolveDeliveryAddress(User $buyer, B2BCompany $buyerCompany, B2BRfq $rfq): string
    {
        $defaultAddress = Address::where('user_id', $buyer->id)->where('set_default', 1)->first();

        if ($defaultAddress) {
            return trim(collect([
                $defaultAddress->address,
                $defaultAddress->postal_code,
                optional($defaultAddress->city)->name,
                optional($defaultAddress->state)->name,
                optional($defaultAddress->country)->name,
                $defaultAddress->phone,
            ])->filter()->implode(', '));
        }

        return trim(collect([
            $buyerCompany->address,
            $rfq->destination_city,
            $rfq->destination_country,
            $buyerCompany->phone,
        ])->filter()->implode(', '));
    }

    protected function nextPurchaseOrderNumber(): string
    {
        $latest = B2BPurchaseOrder::latest('id')->value('id') ?? 0;
        return 'PO-' . now()->format('Ymd') . '-' . str_pad((string) ($latest + 1), 5, '0', STR_PAD_LEFT);
    }

    protected function nextInvoiceNumber(): string
    {
        $latest = B2BProformaInvoice::latest('id')->value('id') ?? 0;
        return 'PI-' . now()->format('Ymd') . '-' . str_pad((string) ($latest + 1), 5, '0', STR_PAD_LEFT);
    }
}
