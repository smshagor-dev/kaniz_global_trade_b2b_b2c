<?php

namespace App\Models;

use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Model;

class B2BProformaInvoice extends Model
{
    public const ESCROW_STATUSES = [
        'pending',
        'awaiting_payment',
        'funded',
        'released',
        'disputed',
        'refunded',
        'cancelled',
        'not_applicable',
    ];

    protected $table = 'b2b_proforma_invoices';

    protected $fillable = [
        'invoice_number',
        'purchase_order_id',
        'quotation_id',
        'buyer_user_id',
        'supplier_user_id',
        'buyer_company_id',
        'supplier_company_id',
        'currency',
        'exchange_rate_snapshot',
        'currency_snapshot',
        'incoterm',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'grand_total',
        'platform_fee_percent_snapshot',
        'platform_fee_fixed_snapshot',
        'platform_fee_amount',
        'supplier_payout_amount',
        'buyer_payable_total',
        'escrow_fee_percent_snapshot',
        'escrow_fee_fixed_snapshot',
        'escrow_fee_amount',
        'escrow_status',
        'escrow_payment_reference',
        'escrow_funded_at',
        'escrow_released_at',
        'escrow_disputed_at',
        'escrow_refunded_at',
        'escrow_cancelled_at',
        'supplier_paid_out_at',
        'escrow_dispute_reason',
        'escrow_resolution',
        'escrow_resolution_notes',
        'valid_until',
        'notes',
        'status',
        'sent_at',
        'accepted_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'platform_fee_percent_snapshot' => 'decimal:3',
        'platform_fee_fixed_snapshot' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'supplier_payout_amount' => 'decimal:2',
        'buyer_payable_total' => 'decimal:2',
        'escrow_fee_percent_snapshot' => 'decimal:3',
        'escrow_fee_fixed_snapshot' => 'decimal:2',
        'escrow_fee_amount' => 'decimal:2',
        'escrow_funded_at' => 'datetime',
        'escrow_released_at' => 'datetime',
        'escrow_disputed_at' => 'datetime',
        'escrow_refunded_at' => 'datetime',
        'escrow_cancelled_at' => 'datetime',
        'supplier_paid_out_at' => 'datetime',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invoice) {
            if (!$invoice->currency) {
                return;
            }

            $currencyService = app(CurrencyService::class);
            $invoice->exchange_rate_snapshot = $invoice->exchange_rate_snapshot ?: $currencyService->rateFor($invoice->currency);
            $invoice->currency_snapshot = $invoice->currency_snapshot ?: $currencyService->snapshot($invoice->currency);
        });
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(B2BPurchaseOrder::class, 'purchase_order_id');
    }

    public function quotation()
    {
        return $this->belongsTo(B2BQuotation::class, 'quotation_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_user_id');
    }

    public function buyerCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'buyer_company_id');
    }

    public function supplierCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'supplier_company_id');
    }

    public function items()
    {
        return $this->hasMany(B2BProformaInvoiceItem::class, 'proforma_invoice_id');
    }

    public function auditLogs()
    {
        return $this->morphMany(B2BAuditLog::class, 'auditable');
    }

    public function shipments()
    {
        return $this->hasMany(B2BShipment::class, 'proforma_invoice_id');
    }

    public function freightQuotes()
    {
        return $this->hasMany(B2BFreightQuote::class, 'proforma_invoice_id');
    }

    public function documents()
    {
        return $this->morphMany(B2BTradeDocument::class, 'documentable');
    }

    public function customsDocuments()
    {
        return $this->morphMany(B2BCustomsDocument::class, 'documentable');
    }

    public function paymentTransactions()
    {
        return $this->morphMany(B2BPaymentTransaction::class, 'reference');
    }

    public function escrows()
    {
        return $this->morphMany(B2BEscrow::class, 'reference');
    }

    public function milestones()
    {
        return $this->hasMany(B2BPaymentMilestone::class, 'proforma_invoice_id')->orderBy('sort_order');
    }

    public function lettersOfCredit()
    {
        return $this->hasMany(B2BLetterOfCredit::class, 'proforma_invoice_id');
    }

    public function financeDisputes()
    {
        return $this->hasMany(B2BFinanceDispute::class, 'proforma_invoice_id');
    }

    public function financeRefunds()
    {
        return $this->morphMany(B2BFinanceRefund::class, 'reference');
    }

    public function usesEscrow(): bool
    {
        return (float) $this->escrow_fee_amount > 0;
    }

    public function escrowStatusLabel(): string
    {
        return ucwords(str_replace('_', ' ', (string) $this->escrow_status));
    }

    public function canFundEscrow(): bool
    {
        return $this->usesEscrow()
            && $this->status === 'accepted'
            && in_array($this->escrow_status, ['pending', 'awaiting_payment'], true);
    }

    public function canReleaseEscrow(): bool
    {
        return $this->usesEscrow()
            && $this->status === 'accepted'
            && in_array($this->escrow_status, ['funded', 'disputed'], true);
    }

    public function canDisputeEscrow(): bool
    {
        return $this->usesEscrow()
            && $this->status === 'accepted'
            && $this->escrow_status === 'funded';
    }
}
