<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AINotificationEvent extends Model
{
    protected $table = 'ai_notification_events';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider',
        'model',
        'confidence_score',
        'metadata',
        'event_type',
        'audience_type',
        'audience_id',
        'severity',
        'title',
        'body',
        'reference_type',
        'reference_id',
        'is_read',
        'sent_at',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'metadata' => 'array',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
    ];
}
