<?php

namespace App\Models;

use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Model;

class B2BPurchaseOrder extends Model
{
    protected $table = 'b2b_purchase_orders';

    protected $fillable = [
        'po_number',
        'buyer_user_id',
        'supplier_user_id',
        'buyer_company_id',
        'supplier_company_id',
        'rfq_id',
        'quotation_id',
        'currency',
        'exchange_rate_snapshot',
        'currency_snapshot',
        'payment_terms',
        'shipping_terms',
        'incoterms',
        'delivery_address',
        'delivery_deadline',
        'subtotal',
        'total_amount',
        'notes',
        'status',
        'sent_at',
        'supplier_reviewed_at',
        'accepted_at',
        'rejected_at',
        'cancelled_at',
        'completed_at',
    ];

    protected $casts = [
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'delivery_deadline' => 'date',
        'sent_at' => 'datetime',
        'supplier_reviewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $purchaseOrder) {
            if (!$purchaseOrder->currency) {
                return;
            }

            $currencyService = app(CurrencyService::class);
            $purchaseOrder->exchange_rate_snapshot = $purchaseOrder->exchange_rate_snapshot ?: $currencyService->rateFor($purchaseOrder->currency);
            $purchaseOrder->currency_snapshot = $purchaseOrder->currency_snapshot ?: $currencyService->snapshot($purchaseOrder->currency);
        });
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

    public function rfq()
    {
        return $this->belongsTo(B2BRfq::class, 'rfq_id');
    }

    public function quotation()
    {
        return $this->belongsTo(B2BQuotation::class, 'quotation_id');
    }

    public function items()
    {
        return $this->hasMany(B2BPurchaseOrderItem::class, 'purchase_order_id');
    }

    public function proformaInvoices()
    {
        return $this->hasMany(B2BProformaInvoice::class, 'purchase_order_id');
    }

    public function shippingQuotes()
    {
        return $this->hasMany(B2BShippingQuote::class, 'purchase_order_id');
    }

    public function shipments()
    {
        return $this->hasMany(B2BShipment::class, 'purchase_order_id');
    }

    public function freightQuotes()
    {
        return $this->hasMany(B2BFreightQuote::class, 'purchase_order_id');
    }

    public function documents()
    {
        return $this->morphMany(B2BTradeDocument::class, 'documentable');
    }

    public function customsDocuments()
    {
        return $this->morphMany(B2BCustomsDocument::class, 'documentable');
    }

    public function negotiation()
    {
        return $this->hasOne(B2BNegotiation::class, 'purchase_order_id');
    }

    public function auditLogs()
    {
        return $this->morphMany(B2BAuditLog::class, 'auditable');
    }

    public function paymentTransactions()
    {
        return $this->morphMany(B2BPaymentTransaction::class, 'reference');
    }

    public function milestones()
    {
        return $this->hasMany(B2BPaymentMilestone::class, 'purchase_order_id')->orderBy('sort_order');
    }

    public function lettersOfCredit()
    {
        return $this->hasMany(B2BLetterOfCredit::class, 'purchase_order_id');
    }

    public function financeDisputes()
    {
        return $this->hasMany(B2BFinanceDispute::class, 'purchase_order_id');
    }

    public function insuranceQuotes()
    {
        return $this->hasMany(B2BInsuranceQuote::class, 'purchase_order_id');
    }

    public function insurancePolicies()
    {
        return $this->hasMany(B2BInsurancePolicy::class, 'purchase_order_id');
    }

    public function insuranceClaims()
    {
        return $this->hasMany(B2BInsuranceClaim::class, 'purchase_order_id');
    }
}
