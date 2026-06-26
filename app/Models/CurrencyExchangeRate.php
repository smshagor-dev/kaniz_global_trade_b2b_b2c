<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyExchangeRate extends Model
{
    protected $fillable = [
        'base_currency_code',
        'currency_code',
        'rate',
        'provider',
        'is_manual_override',
        'synced_at',
        'source_updated_at',
        'meta',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'is_manual_override' => 'boolean',
        'synced_at' => 'datetime',
        'source_updated_at' => 'datetime',
        'meta' => 'array',
    ];
}
