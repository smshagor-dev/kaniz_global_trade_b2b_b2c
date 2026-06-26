<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BPackage extends Model
{
    protected $table = 'b2b_packages';

    protected $fillable = [
        'name',
        'package_for',
        'package_type',
        'amount',
        'duration',
        'rfq_limit',
        'quotation_limit',
        'product_limit',
        'member_limit',
        'priority_listing',
        'featured_profile',
        'verified_badge',
        'analytics_access',
        'dedicated_support',
        'logo',
        'highlight_text',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'duration' => 'integer',
        'package_type' => 'string',
        'rfq_limit' => 'integer',
        'quotation_limit' => 'integer',
        'product_limit' => 'integer',
        'member_limit' => 'integer',
        'priority_listing' => 'boolean',
        'featured_profile' => 'boolean',
        'verified_badge' => 'boolean',
        'analytics_access' => 'boolean',
        'dedicated_support' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function companies()
    {
        return $this->hasMany(B2BCompany::class, 'b2b_package_id');
    }

    public function requests()
    {
        return $this->hasMany(B2BPackageRequest::class, 'b2b_package_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMembership($query)
    {
        return $query->where('package_type', 'membership');
    }

    public function scopeSupplierFeatured($query)
    {
        return $query->where('package_type', 'supplier_featured');
    }

    public function scopeFeaturedSupplierHomepage($query)
    {
        return $query->active()
            ->supplierFeatured()
            ->where('package_for', 'supplier')
            ->where('featured_profile', true);
    }

    public function isSupplierFeaturedPackage(): bool
    {
        return $this->package_type === 'supplier_featured';
    }

    public function monthlyEquivalent(): float
    {
        $duration = max((int) $this->duration, 1);

        return round(((float) $this->amount / $duration) * 30, 2);
    }
}
