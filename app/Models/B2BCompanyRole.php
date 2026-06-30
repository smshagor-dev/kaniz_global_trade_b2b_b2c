<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCompanyRole extends Model
{
    protected $table = 'b2b_company_roles';

    protected $fillable = [
        'b2b_company_id',
        'name',
        'slug',
        'permissions',
        'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'b2b_company_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(B2BCompanyMember::class, 'custom_role_id');
    }

    public function invitations()
    {
        return $this->hasMany(B2BCompanyInvitation::class, 'custom_role_id');
    }
}
