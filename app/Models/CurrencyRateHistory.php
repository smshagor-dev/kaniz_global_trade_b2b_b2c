<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRateHistory extends Model
{
    protected $table = 'currency_rate_history';

    protected $fillable = [
        'base_currency_code',
        'currency_code',
        'rate',
        'provider',
        'sync_batch',
        'synced_at',
        'meta',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'synced_at' => 'datetime',
        'meta' => 'array',
    ];
}
