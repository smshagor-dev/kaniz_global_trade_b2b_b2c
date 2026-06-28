<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReport extends Model
{
    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'reported_user_type',
        'report_type',
        'message',
        'evidence',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }
}
