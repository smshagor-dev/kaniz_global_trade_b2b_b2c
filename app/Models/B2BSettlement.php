<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BSettlement extends Model
{
    protected $table = 'b2b_settlements';

    protected $fillable = [
        'supplier_company_id',
        'payment_transaction_id',
        'escrow_id',
        'settlement_method',
        'currency',
        'amount',
        'fees',
        'net_amount',
        'reference',
        'destination_details',
        'status',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'destination_details' => 'array',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function escrow()
    {
        return $this->belongsTo(B2BEscrow::class, 'escrow_id');
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(B2BPaymentTransaction::class, 'payment_transaction_id');
    }

    public function logs()
    {
        return $this->hasMany(B2BSettlementLog::class, 'settlement_id');
    }
}
