<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BSettlementLog extends Model
{
    protected $table = 'b2b_settlement_logs';

    protected $fillable = [
        'settlement_id',
        'action',
        'performed_by',
        'notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
