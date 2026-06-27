<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BInsuranceApiLog extends Model
{
    protected $table = 'b2b_insurance_api_logs';

    protected $fillable = [
        'provider_id',
        'loggable_type',
        'loggable_id',
        'direction',
        'endpoint',
        'request_method',
        'http_status',
        'status',
        'latency_ms',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(B2BInsuranceProvider::class, 'provider_id');
    }

    public function loggable()
    {
        return $this->morphTo();
    }
}
