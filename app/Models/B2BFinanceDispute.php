<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BFinanceDispute extends Model
{
    protected $table = 'b2b_finance_disputes';

    protected $fillable = [
        'reference_type',
        'reference_id',
        'purchase_order_id',
        'proforma_invoice_id',
        'buyer_company_id',
        'supplier_company_id',
        'created_by',
        'category',
        'title',
        'description',
        'evidence',
        'status',
        'escrow_hold',
        'resolution',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'escrow_hold' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function reference()
    {
        return $this->morphTo();
    }

    public function messages()
    {
        return $this->hasMany(B2BFinanceDisputeMessage::class, 'dispute_id');
    }
}
