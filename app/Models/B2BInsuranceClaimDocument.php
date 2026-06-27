<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BInsuranceClaimDocument extends Model
{
    protected $table = 'b2b_insurance_claim_documents';

    protected $fillable = [
        'claim_id',
        'uploaded_by',
        'document_type',
        'title',
        'file_path',
        'mime_type',
        'file_size',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function claim()
    {
        return $this->belongsTo(B2BInsuranceClaim::class, 'claim_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
