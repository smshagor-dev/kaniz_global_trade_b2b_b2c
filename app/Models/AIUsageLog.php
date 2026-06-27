<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class AIUsageLog extends Model
{
    use PreventDemoModeChanges;

    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'ai_request_id',
        'user_id',
        'module',
        'provider',
        'model',
        'status',
        'latency_ms',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'metadata',
    ];

    protected $casts = [
        'ai_request_id' => 'integer',
        'user_id' => 'integer',
        'latency_ms' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost' => 'decimal:8',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function request()
    {
        return $this->belongsTo(AIRequest::class, 'ai_request_id');
    }
}
