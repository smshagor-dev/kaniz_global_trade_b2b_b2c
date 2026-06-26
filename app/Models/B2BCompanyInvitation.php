<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCompanyInvitation extends Model
{
    protected $table = 'b2b_company_invitations';

    protected $fillable = [
        'b2b_company_id',
        'email',
        'role',
        'token',
        'status',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'b2b_company_id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
