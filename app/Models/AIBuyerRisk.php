<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIBuyerRisk extends Model
{
    protected $table = 'ai_buyer_risk';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider',
        'model',
        'confidence_score',
        'metadata',
        'buyer_company_id',
        'subject_user_id',
        'trust_score',
        'risk_level',
        'explanation',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'metadata' => 'array',
    ];
}
