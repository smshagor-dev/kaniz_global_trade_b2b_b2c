<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchIndexingFailure extends Model
{
    protected $fillable = [
        'run_id',
        'index_name',
        'model_type',
        'model_id',
        'operation',
        'provider',
        'message',
        'payload',
        'failed_at',
        'resolved_at',
        'attempts',
    ];

    protected $casts = [
        'payload' => 'array',
        'failed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function run()
    {
        return $this->belongsTo(SearchIndexingRun::class, 'run_id');
    }
}
