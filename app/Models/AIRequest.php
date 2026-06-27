<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class AIRequest extends Model
{
    use PreventDemoModeChanges;

    protected $table = 'ai_requests';

    protected $fillable = [
        'provider_setting_id',
        'user_id',
        'company_id',
        'module',
        'provider',
        'model',
        'prompt_hash',
        'prompt_preview',
        'status',
        'latency_ms',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'latency_ms' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost' => 'decimal:8',
        'metadata' => 'array',
    ];

    public function providerSetting()
    {
        return $this->belongsTo(AIProviderSetting::class, 'provider_setting_id');
    }
}
