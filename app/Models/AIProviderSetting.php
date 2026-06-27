<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AIProviderSetting extends Model
{
    use PreventDemoModeChanges;

    protected $table = 'ai_provider_settings';

    protected $fillable = [
        'provider',
        'name',
        'api_key',
        'base_url',
        'model',
        'temperature',
        'max_tokens',
        'timeout',
        'retry_count',
        'daily_limit',
        'monthly_limit',
        'is_active',
        'is_default',
        'settings',
        'last_tested_at',
        'last_status',
    ];

    protected $casts = [
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'timeout' => 'integer',
        'retry_count' => 'integer',
        'daily_limit' => 'integer',
        'monthly_limit' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'settings' => 'array',
        'last_tested_at' => 'datetime',
    ];

    public function setApiKeyAttribute($value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiKeyAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $throwable) {
            return $value;
        }
    }

    public function requests()
    {
        return $this->hasMany(AIRequest::class, 'provider_setting_id');
    }
}
