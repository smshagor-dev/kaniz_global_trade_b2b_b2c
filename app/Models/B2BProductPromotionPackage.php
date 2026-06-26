<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BProductPromotionPackage extends Model
{
    protected $table = 'b2b_product_promotion_packages';

    protected $fillable = [
        'name',
        'amount',
        'duration',
        'product_limit',
        'logo',
        'highlight_text',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'duration' => 'integer',
        'product_limit' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function requests()
    {
        return $this->hasMany(B2BProductPromotionRequest::class, 'b2b_product_promotion_package_id');
    }

    public function promotions()
    {
        return $this->hasMany(B2BProductPromotion::class, 'b2b_product_promotion_package_id');
    }

    public function monthlyEquivalent(): float
    {
        $duration = max((int) $this->duration, 1);

        return round(((float) $this->amount / $duration) * 30, 2);
    }
}
