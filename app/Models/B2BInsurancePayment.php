<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BInsurancePayment extends Model
{
    protected $table = 'b2b_insurance_payments';

    protected $fillable = [
        'policy_id',
        'claim_id',
        'provider_id',
        'buyer_company_id',
        'supplier_company_id',
        'recorded_by',
        'payment_type',
        'payment_method',
        'reference',
        'amount',
        'tax_amount',
        'fees',
        'currency',
        'status',
        'meta',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'meta' => 'array',
        'paid_at' => 'datetime',
    ];

    public function policy()
    {
        return $this->belongsTo(B2BInsurancePolicy::class, 'policy_id');
    }

    public function claim()
    {
        return $this->belongsTo(B2BInsuranceClaim::class, 'claim_id');
    }

    public function provider()
    {
        return $this->belongsTo(B2BInsuranceProvider::class, 'provider_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
