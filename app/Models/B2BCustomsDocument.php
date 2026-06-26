<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BCustomsDocument extends Model
{
    protected $table = 'b2b_customs_documents';

    protected $fillable = [
        'uploaded_by',
        'company_id',
        'document_type',
        'title',
        'status',
        'revision_number',
        'file_path',
        'issued_at',
        'expires_at',
        'verified_at',
        'verified_by',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'verified_at' => 'datetime',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'company_id');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
