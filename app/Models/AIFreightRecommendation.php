<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIFreightRecommendation extends Model
{
    protected $table = 'ai_freight_recommendations';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider',
        'model',
        'confidence_score',
        'metadata',
        'freight_quote_id',
        'shipment_id',
        'forwarder_id',
        'recommended_mode',
        'recommended_strategy',
        'recommended_forwarder_name',
        'estimated_delivery_days',
        'estimated_customs_delay_days',
        'estimated_shipping_cost',
        'cost_saving_estimate',
        'carbon_estimate',
        'risk_score',
        'explanation',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'metadata' => 'array',
        'estimated_shipping_cost' => 'decimal:4',
        'cost_saving_estimate' => 'decimal:4',
        'carbon_estimate' => 'decimal:4',
    ];
}
