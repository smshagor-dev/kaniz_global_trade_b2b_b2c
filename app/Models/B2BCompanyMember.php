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
}
