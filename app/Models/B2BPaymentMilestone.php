<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BPaymentMilestone extends Model
{
    protected $table = 'b2b_payment_milestones';

    protected $fillable = [
        'purchase_order_id',
        'proforma_invoice_id',
        'buyer_company_id',
        'supplier_company_id',
        'title',
        'trigger_event',
        'sort_order',
        'percentage',
        'amount',
        'currency',
        'exchange_rate_snapshot',
        'currency_snapshot',
        'status',
        'scheduled_release_at',
        'due_at',
        'payment_transaction_id',
        'escrow_id',
        'approved_by',
        'approved_at',
        'paid_at',
        'released_at',
        'notes',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'scheduled_release_at' => 'datetime',
        'due_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(B2BPurchaseOrder::class, 'purchase_order_id');
    }

    public function proformaInvoice()
    {
        return $this->belongsTo(B2BProformaInvoice::class, 'proforma_invoice_id');
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(B2BPaymentTransaction::class, 'payment_transaction_id');
    }

    public function escrow()
    {
        return $this->belongsTo(B2BEscrow::class, 'escrow_id');
    }
}
