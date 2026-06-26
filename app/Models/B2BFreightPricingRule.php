<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BFreightPricingRule extends Model
{
    protected $table = 'b2b_freight_pricing_rules';

    protected $fillable = [
        'name',
        'forwarder_id',
        'freight_mode',
        'service_type',
        'origin_country',
        'destination_country',
        'container_type',
        'incoterm',
        'min_weight',
        'max_weight',
        'min_volume',
        'max_volume',
        'base_price',
        'price_per_kg',
        'price_per_cbm',
        'fuel_surcharge_percent',
        'platform_fee_percent',
        'platform_fee_fixed',
        'currency',
        'active',
    ];

    protected $casts = [
        'min_weight' => 'decimal:3',
        'max_weight' => 'decimal:3',
        'min_volume' => 'decimal:3',
        'max_volume' => 'decimal:3',
        'base_price' => 'decimal:2',
        'price_per_kg' => 'decimal:4',
        'price_per_cbm' => 'decimal:4',
        'fuel_surcharge_percent' => 'decimal:3',
        'platform_fee_percent' => 'decimal:3',
        'platform_fee_fixed' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function forwarder()
    {
        return $this->belongsTo(B2BFreightForwarder::class, 'forwarder_id');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
