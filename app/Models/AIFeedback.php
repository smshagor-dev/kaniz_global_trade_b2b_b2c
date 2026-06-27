<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class AIFeedback extends Model
{
    use PreventDemoModeChanges;

    protected $table = 'ai_feedback';

    protected $fillable = [
        'ai_request_id',
        'user_id',
        'module',
        'rating',
        'feedback',
        'metadata',
    ];

    protected $casts = [
        'ai_request_id' => 'integer',
        'user_id' => 'integer',
        'rating' => 'integer',
        'metadata' => 'array',
    ];

    public function request()
    {
        return $this->belongsTo(AIRequest::class, 'ai_request_id');
    }
}
