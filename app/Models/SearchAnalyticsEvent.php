<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchAnalyticsEvent extends Model
{
    protected $fillable = [
        'event_type',
        'query',
        'provider',
        'document_id',
        'session_id',
        'user_id',
        'result_count',
        'response_time_ms',
        'filters',
        'metadata',
    ];

    protected $casts = [
        'filters' => 'array',
        'metadata' => 'array',
    ];
}
