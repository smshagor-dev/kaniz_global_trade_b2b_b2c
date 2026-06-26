<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BAuditLog extends Model
{
    protected $table = 'b2b_audit_logs';

    protected $fillable = [
        'actor_user_id',
        'actor_company_id',
        'event_type',
        'auditable_type',
        'auditable_id',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'actor_company_id');
    }

    public function auditable()
    {
        return $this->morphTo();
    }
}
