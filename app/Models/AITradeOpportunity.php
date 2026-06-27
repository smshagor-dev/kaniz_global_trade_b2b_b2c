<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AITradeOpportunity extends Model
{
    protected $table = 'ai_trade_opportunities';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider',
        'model',
        'confidence_score',
        'metadata',
        'product_id',
        'category_id',
        'opportunity_type',
        'title',
        'summary',
        'market_country',
        'opportunity_score',
        'estimated_revenue_increase',
        'estimated_savings',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'metadata' => 'array',
        'estimated_revenue_increase' => 'decimal:4',
        'estimated_savings' => 'decimal:4',
    ];
}
