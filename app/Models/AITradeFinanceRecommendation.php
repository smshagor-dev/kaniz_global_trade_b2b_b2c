<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AITradeFinanceRecommendation extends Model
{
    protected $table = 'ai_trade_finance_recommendations';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider',
        'model',
        'confidence_score',
        'metadata',
        'reference_type',
        'reference_id',
        'recommended_term',
        'risk_score',
        'explanation',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'metadata' => 'array',
    ];
}
