<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BPaymentTransaction extends Model
{
    protected $table = 'b2b_payment_transactions';

    protected $fillable = [
        'reference_type',
        'reference_id',
        'buyer_company_id',
        'supplier_company_id',
        'payment_gateway',
        'payment_method',
        'currency',
        'settlement_currency',
        'exchange_rate_snapshot',
        'settlement_exchange_rate_snapshot',
        'amount',
        'gateway_reference',
        'reference_number',
        'swift',
        'iban',
        'receipt_path',
        'status',
        'paid_at',
        'verified_by',
        'verified_at',
        'meta',
        'currency_snapshot',
    ];

    protected $casts = [
        'exchange_rate_snapshot' => 'decimal:8',
        'settlement_exchange_rate_snapshot' => 'decimal:8',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'meta' => 'array',
        'currency_snapshot' => 'array',
    ];

    public function reference()
    {
        return $this->morphTo();
    }

    public function escrow()
    {
        return $this->hasOne(B2BEscrow::class, 'payment_transaction_id');
    }
}
