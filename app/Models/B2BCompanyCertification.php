<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCompanyCertification extends Model
{
    protected $table = 'b2b_company_certifications';

    protected $fillable = [
        'b2b_company_id',
        'name',
        'issuing_authority',
        'certificate_number',
        'issue_date',
        'expiry_date',
        'file',
        'verification_status',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'b2b_company_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
