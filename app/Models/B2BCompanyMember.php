<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCompanyMember extends Model
{
    protected $table = 'b2b_company_members';

    protected $fillable = [
        'b2b_company_id',
        'user_id',
        'role',
        'custom_role_id',
        'status',
        'invited_by',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'b2b_company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function customRole()
    {
        return $this->belongsTo(B2BCompanyRole::class, 'custom_role_id');
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->customRole?->name ?: ucwords(str_replace('_', ' ', (string) $this->role));
    }
}
