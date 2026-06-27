<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class AICostReport extends Model
{
    use PreventDemoModeChanges;

    protected $table = 'ai_cost_reports';

    protected $fillable = [
        'report_date',
        'provider',
        'model',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_requests' => 'integer',
        'successful_requests' => 'integer',
        'failed_requests' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost' => 'decimal:8',
        'metadata' => 'array',
    ];
}
