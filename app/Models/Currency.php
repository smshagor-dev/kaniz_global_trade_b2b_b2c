<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Currency extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'name',
        'symbol',
        'exchange_rate',
        'decimal_places',
        'symbol_position',
        'decimal_separator',
        'thousands_separator',
        'status',
        'code',
        'is_base_currency',
        'is_default_display_currency',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:8',
        'decimal_places' => 'integer',
        'is_base_currency' => 'boolean',
        'is_default_display_currency' => 'boolean',
        'status' => 'boolean',
    ];
}
