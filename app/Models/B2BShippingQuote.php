<?php

namespace App\Models;

use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BShippingQuote extends Model
{
    protected $table = 'b2b_shipping_quotes';

    protected $fillable = [
        'quote_number',
        'purchase_order_id',
        'sample_order_id',
        'supplier_company_id',
        'buyer_company_id',
        'shipping_provider_id',
        'created_by',
        'transport_mode',
        'origin_country',
        'destination_country',
        'incoterm',
        'service_type',
        'delivery_priority',
        'total_weight',
        'package_length',
        'package_width',
        'package_height',
        'currency',
        'exchange_rate_snapshot',
        'currency_snapshot',
        'estimated_days',
        'shipping_cost',
        'insurance_amount',
        'customs_estimate',
        'subtotal_cost',
        'site_charge_percent_snapshot',
        'site_charge_fixed_snapshot',
        'site_charge_amount',
        'total_cost',
        'status',
        'notes',
        'rate_request_payload',
        'rate_response_payload',
    ];

    protected $casts = [
        'total_weight' => 'decimal:3',
        'package_length' => 'decimal:2',
        'package_width' => 'decimal:2',
        'package_height' => 'decimal:2',
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'estimated_days' => 'integer',
        'shipping_cost' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'customs_estimate' => 'decimal:2',
        'subtotal_cost' => 'decimal:2',
        'site_charge_percent_snapshot' => 'decimal:3',
        'site_charge_fixed_snapshot' => 'decimal:2',
        'site_charge_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'rate_request_payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $shippingQuote) {
            if (!$shippingQuote->currency) {
                return;
            }

            $currencyService = app(CurrencyService::class);
            $shippingQuote->exchange_rate_snapshot = $shippingQuote->exchange_rate_snapshot ?: $currencyService->rateFor($shippingQuote->currency);
            $shippingQuote->currency_snapshot = $shippingQuote->currency_snapshot ?: $currencyService->snapshot($shippingQuote->currency);
        });
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(B2BPurchaseOrder::class, 'purchase_order_id');
    }

    public function sampleOrder()
    {
        return $this->belongsTo(B2BSampleOrder::class, 'sample_order_id');
    }

    public function supplierCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'supplier_company_id');
    }

    public function buyerCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'buyer_company_id');
    }

    public function shippingProvider()
    {
        return $this->belongsTo(B2BShippingProvider::class, 'shipping_provider_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shipment()
    {
        return $this->hasOne(B2BShipment::class, 'shipping_quote_id');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
