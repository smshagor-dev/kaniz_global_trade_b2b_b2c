<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudCheckLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'fraud_check_id',
        'user_id',
        'event_type',
        'old_score',
        'new_score',
        'reason',
        'metadata',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function fraudCheck()
    {
        return $this->belongsTo(FraudCheck::class);
    }
}
