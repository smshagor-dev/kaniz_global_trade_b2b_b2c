<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudCheck extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'risk_score',
        'risk_level',
        'source',
        'status',
        'summary',
        'reasons',
        'rule_score',
        'ai_score',
        'manual_score',
        'final_score',
        'ai_provider',
        'ai_model',
        'ai_response',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reasons' => 'array',
        'ai_response' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function logs()
    {
        return $this->hasMany(FraudCheckLog::class);
    }
}
