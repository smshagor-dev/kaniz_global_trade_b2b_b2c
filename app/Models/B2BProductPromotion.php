<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BProductPromotion extends Model
{
    protected $table = 'b2b_product_promotions';

    protected $fillable = [
        'b2b_company_id',
        'product_id',
        'b2b_product_promotion_package_id',
        'status',
        'started_at',
        'expires_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'b2b_company_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function package()
    {
        return $this->belongsTo(B2BProductPromotionPackage::class, 'b2b_product_promotion_package_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($subQuery) {
                $subQuery->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }
}
