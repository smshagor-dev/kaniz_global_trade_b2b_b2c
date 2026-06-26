<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BFinanceDisputeMessage extends Model
{
    protected $table = 'b2b_finance_dispute_messages';

    protected $fillable = [
        'dispute_id',
        'sender_user_id',
        'sender_company_id',
        'message',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];
}
