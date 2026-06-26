<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCompanyVerificationSubmission extends Model
{
    protected $table = 'b2b_company_verification_submissions';

    protected $fillable = [
        'b2b_company_id',
        'b2b_verification_requirement_id',
        'value_text',
        'value_file',
    ];

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'b2b_company_id');
    }

    public function requirement()
    {
        return $this->belongsTo(B2BVerificationRequirement::class, 'b2b_verification_requirement_id');
    }
}
