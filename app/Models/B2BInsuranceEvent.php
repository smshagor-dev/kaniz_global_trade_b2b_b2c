<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BInsuranceEvent extends Model
{
    protected $table = 'b2b_insurance_events';

    protected $fillable = [
        'eventable_type',
        'eventable_id',
        'provider_id',
        'company_id',
        'user_id',
        'event_type',
        'title',
        'description',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function eventable()
    {
        return $this->morphTo();
    }

    public function provider()
    {
        return $this->belongsTo(B2BInsuranceProvider::class, 'provider_id');
    }

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
