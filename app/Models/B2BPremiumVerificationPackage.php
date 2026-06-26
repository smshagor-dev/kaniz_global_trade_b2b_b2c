<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BPremiumVerificationPackage extends Model
{
    protected $table = 'b2b_premium_verification_packages';

    protected $fillable = [
        'name',
        'amount',
        'logo',
        'highlight_text',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function requests()
    {
        return $this->hasMany(B2BPremiumVerificationRequest::class, 'b2b_premium_verification_package_id');
    }
}
