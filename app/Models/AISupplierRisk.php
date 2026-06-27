<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AISupplierRisk extends Model
{
    protected $table = 'ai_supplier_risk';

    protected $fillable = [
        'company_id',
        'user_id',
        'provider',
        'model',
        'confidence_score',
        'metadata',
        'supplier_company_id',
        'subject_user_id',
        'risk_score',
        'risk_level',
        'explanation',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'metadata' => 'array',
    ];
}
