<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudRule extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'user_type',
        'event_type',
        'score',
        'severity',
        'is_active',
        'conditions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
    ];
}
