<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BVerificationRequirement extends Model
{
    protected $table = 'b2b_verification_requirements';

    public const FIELD_TYPES = [
        'text',
        'textarea',
        'email',
        'phone',
        'url',
        'number',
        'date',
        'file',
    ];

    protected $fillable = [
        'label',
        'slug',
        'field_type',
        'help_text',
        'placeholder',
        'company_types',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'company_types' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function submissions()
    {
        return $this->hasMany(B2BCompanyVerificationSubmission::class, 'b2b_verification_requirement_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function appliesTo(?string $companyType): bool
    {
        if (!$companyType || empty($this->company_types)) {
            return true;
        }

        return in_array($companyType, $this->company_types, true);
    }
}
