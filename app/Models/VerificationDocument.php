<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationDocument extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'status',
        'rejection_reason',
        'extracted_text',
        'ai_analysis',
        'reviewed_by',
        'reviewed_at',
        'expires_at',
    ];

    protected $casts = [
        'ai_analysis' => 'array',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
