<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BEscrow extends Model
{
    protected $table = 'b2b_escrows';

    protected $fillable = [
        'reference_type',
        'reference_id',
        'buyer_company_id',
        'supplier_company_id',
        'payment_transaction_id',
        'currency',
        'settlement_currency',
        'exchange_rate_snapshot',
        'settlement_exchange_rate_snapshot',
        'amount',
        'funded_amount',
        'released_amount',
        'refunded_amount',
        'status',
        'funded_at',
        'released_at',
        'refunded_at',
        'disputed_at',
        'last_action_by',
        'currency_snapshot',
        'meta',
    ];

    protected $casts = [
        'exchange_rate_snapshot' => 'decimal:8',
        'settlement_exchange_rate_snapshot' => 'decimal:8',
        'amount' => 'decimal:2',
        'funded_amount' => 'decimal:2',
        'released_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'funded_at' => 'datetime',
        'released_at' => 'datetime',
        'refunded_at' => 'datetime',
        'disputed_at' => 'datetime',
        'currency_snapshot' => 'array',
        'meta' => 'array',
    ];

    public function reference()
    {
        return $this->morphTo();
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(B2BPaymentTransaction::class, 'payment_transaction_id');
    }

    public function logs()
    {
        return $this->hasMany(B2BEscrowLog::class, 'escrow_id');
    }

    public function settlements()
    {
        return $this->hasMany(B2BSettlement::class, 'escrow_id');
    }
}
