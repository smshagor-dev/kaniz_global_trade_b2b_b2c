<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BNegotiationMessage extends Model
{
    protected $table = 'b2b_negotiation_messages';

    protected $fillable = [
        'negotiation_id',
        'sender_user_id',
        'sender_company_id',
        'sender_role',
        'message_type',
        'message',
        'attachment',
        'meta',
        'buyer_read_at',
        'supplier_read_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'buyer_read_at' => 'datetime',
        'supplier_read_at' => 'datetime',
    ];

    public function negotiation()
    {
        return $this->belongsTo(B2BNegotiation::class, 'negotiation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function senderCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'sender_company_id');
    }
}
