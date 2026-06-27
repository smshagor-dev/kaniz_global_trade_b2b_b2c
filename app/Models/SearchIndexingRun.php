<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchIndexingRun extends Model
{
    protected $fillable = [
        'entity',
        'provider',
        'chunk_size',
        'is_queue',
        'is_dry_run',
        'status',
        'total_models',
        'processed_models',
        'failed_models',
        'queued_chunks',
        'processed_chunks',
        'current_model_class',
        'last_processed_id',
        'summary',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'is_queue' => 'boolean',
        'is_dry_run' => 'boolean',
        'summary' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
