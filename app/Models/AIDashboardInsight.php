<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIDashboardInsight extends Model
{
    protected $table = 'ai_dashboard_insights';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider',
        'model',
        'confidence_score',
        'metadata',
        'insight_date',
        'scope',
        'title',
        'summary',
        'insights',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'metadata' => 'array',
        'insights' => 'array',
        'insight_date' => 'date',
    ];
}
