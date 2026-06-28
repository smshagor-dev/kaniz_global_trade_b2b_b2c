<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDeviceLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_hash',
        'country',
        'city',
        'login_at',
        'metadata',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'metadata' => 'array',
    ];
}
