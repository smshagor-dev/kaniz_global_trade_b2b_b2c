<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchDocument extends Model
{
    protected $fillable = [
        'engine_document_id',
        'index_name',
        'type',
        'entity_subtype',
        'model_type',
        'model_id',
        'title',
        'subtitle',
        'summary',
        'url',
        'search_text',
        'keywords',
        'filters',
        'metadata',
        'visibility',
        'is_active',
        'rank_exact',
        'rank_popularity',
        'rank_sales',
        'rank_verified',
        'rank_featured',
        'rank_supplier_score',
        'rank_rating',
        'rank_trade_volume',
        'rank_response_rate',
        'rank_recency',
        'rank_ai_score',
        'last_indexed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'rank_exact' => 'float',
        'rank_popularity' => 'float',
        'rank_sales' => 'float',
        'rank_verified' => 'float',
        'rank_featured' => 'float',
        'rank_supplier_score' => 'float',
        'rank_rating' => 'float',
        'rank_trade_volume' => 'float',
        'rank_response_rate' => 'float',
        'rank_recency' => 'float',
        'rank_ai_score' => 'float',
        'last_indexed_at' => 'datetime',
    ];
}
