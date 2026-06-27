<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AICurrencyAnalysis extends Model
{
    protected $table = 'ai_currency_analysis';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider',
        'model',
        'confidence_score',
        'metadata',
        'currency_code',
        'base_currency_code',
        'amount',
        'volatility_score',
        'fx_exposure',
        'recommended_invoice_currency',
        'profit_impact',
        'hedging_suggestion',
        'summary',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'metadata' => 'array',
        'amount' => 'decimal:4',
        'fx_exposure' => 'decimal:4',
        'profit_impact' => 'decimal:4',
    ];
}
