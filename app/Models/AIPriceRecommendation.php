<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIPriceRecommendation extends Model
{
    protected $table = 'ai_price_recommendations';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider',
        'model',
        'confidence_score',
        'metadata',
        'product_id',
        'country',
        'currency',
        'supplier_cost',
        'shipping_cost',
        'customs_cost',
        'tax_cost',
        'vat_cost',
        'platform_fee',
        'selling_price',
        'minimum_profitable_price',
        'wholesale_price',
        'distributor_price',
        'export_price',
        'profit_margin',
        'source',
        'explanation',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'metadata' => 'array',
        'supplier_cost' => 'decimal:4',
        'shipping_cost' => 'decimal:4',
        'customs_cost' => 'decimal:4',
        'tax_cost' => 'decimal:4',
        'vat_cost' => 'decimal:4',
        'platform_fee' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'minimum_profitable_price' => 'decimal:4',
        'wholesale_price' => 'decimal:4',
        'distributor_price' => 'decimal:4',
        'export_price' => 'decimal:4',
        'profit_margin' => 'decimal:4',
    ];
}
