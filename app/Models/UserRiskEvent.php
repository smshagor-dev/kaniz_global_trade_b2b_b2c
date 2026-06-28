<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRiskEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_type',
        'event_type',
        'score',
        'reason',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
}
