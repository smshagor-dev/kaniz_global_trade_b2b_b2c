<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BNegotiation extends Model
{
    protected $table = 'b2b_negotiations';

    protected $fillable = [
        'rfq_id',
        'quotation_id',
        'purchase_order_id',
        'buyer_company_id',
        'supplier_company_id',
        'buyer_user_id',
        'supplier_user_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function rfq()
    {
        return $this->belongsTo(B2BRfq::class, 'rfq_id');
    }

    public function quotation()
    {
        return $this->belongsTo(B2BQuotation::class, 'quotation_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(B2BPurchaseOrder::class, 'purchase_order_id');
    }

    public function buyerCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'buyer_company_id');
    }

    public function supplierCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'supplier_company_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_user_id');
    }

    public function messages()
    {
        return $this->hasMany(B2BNegotiationMessage::class, 'negotiation_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(B2BNegotiationMessage::class, 'negotiation_id')->latestOfMany();
    }

    public function auditLogs()
    {
        return $this->morphMany(B2BAuditLog::class, 'auditable');
    }
}
