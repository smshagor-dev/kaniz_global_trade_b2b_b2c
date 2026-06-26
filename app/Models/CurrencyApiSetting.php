<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyApiSetting extends Model
{
    protected $fillable = [
        'provider',
        'driver',
        'base_currency_code',
        'default_display_currency_code',
        'sync_frequency',
        'auto_sync_enabled',
        'is_active',
        'credentials',
        'custom_rates',
        'last_sync_at',
        'last_sync_status',
        'last_error',
        'last_response',
    ];

    protected $casts = [
        'auto_sync_enabled' => 'boolean',
        'is_active' => 'boolean',
        'credentials' => 'encrypted:array',
        'custom_rates' => 'array',
        'last_response' => 'array',
        'last_sync_at' => 'datetime',
    ];

    public function getApiKey(): ?string
    {
        return $this->credentials['api_key'] ?? null;
    }
}
