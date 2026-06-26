<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BHsCode extends Model
{
    protected $table = 'b2b_hs_codes';

    protected $fillable = [
        'hs_code',
        'description',
        'country',
        'duty_percent',
        'vat_gst_percent',
        'restrictions',
        'is_dangerous_goods',
        'required_documents',
        'is_active',
    ];

    protected $casts = [
        'duty_percent' => 'decimal:3',
        'vat_gst_percent' => 'decimal:3',
        'required_documents' => 'array',
        'is_dangerous_goods' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
