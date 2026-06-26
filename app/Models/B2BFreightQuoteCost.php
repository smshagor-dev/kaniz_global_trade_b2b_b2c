<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BFreightQuoteCost extends Model
{
    public const PAYERS = ['buyer', 'supplier', 'platform'];

    public const COST_TYPES = [
        'base_freight_cost',
        'fuel_surcharge',
        'port_handling_charge',
        'terminal_handling_charge',
        'documentation_fee',
        'customs_clearance_fee',
        'customs_duty',
        'vat_gst',
        'insurance',
        'warehouse_charge',
        'pickup_cost',
        'delivery_cost',
        'inspection_fee',
        'inspection_service_charge',
        'demurrage',
        'detention',
        'miscellaneous_fee',
        'platform_service_fee',
        'platform_site_charge',
        'forwarder_margin',
        'supplier_margin',
        'discount',
        'tax',
    ];

    protected $table = 'b2b_freight_quote_costs';

    protected $fillable = [
        'freight_quote_id',
        'cost_type',
        'description',
        'amount',
        'currency',
        'exchange_rate_snapshot',
        'payer',
        'is_billable',
        'is_optional',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate_snapshot' => 'decimal:6',
        'is_billable' => 'boolean',
        'is_optional' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function freightQuote()
    {
        return $this->belongsTo(B2BFreightQuote::class, 'freight_quote_id');
    }

    public function amountInBaseCurrency(): float
    {
        return round((float) $this->amount * (float) ($this->exchange_rate_snapshot ?: 1), 2);
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
