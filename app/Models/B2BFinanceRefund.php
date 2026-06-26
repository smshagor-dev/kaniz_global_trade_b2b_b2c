<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BFinanceRefund extends Model
{
    protected $table = 'b2b_finance_refunds';

    protected $fillable = [
        'reference_type',
        'reference_id',
        'payment_transaction_id',
        'escrow_id',
        'amount',
        'currency',
        'refund_type',
        'status',
        'reason',
        'requested_by',
        'approved_by',
        'completed_at',
        'notes',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function reference()
    {
        return $this->morphTo();
    }
}
