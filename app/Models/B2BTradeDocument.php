<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BTradeDocument extends Model
{
    protected $table = 'b2b_trade_documents';

    protected $fillable = [
        'uploaded_by',
        'company_id',
        'document_type',
        'title',
        'file_path',
        'issued_at',
        'expires_at',
        'notes',
        'service_fee_fixed_snapshot',
        'service_fee_amount',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'service_fee_fixed_snapshot' => 'decimal:2',
        'service_fee_amount' => 'decimal:2',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'company_id');
    }
}
