<?php

namespace App\Models;

use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BFreightQuote extends Model
{
    protected $table = 'b2b_freight_quotes';

    protected $fillable = [
        'quote_number',
        'buyer_company_id',
        'supplier_company_id',
        'forwarder_id',
        'purchase_order_id',
        'proforma_invoice_id',
        'sample_order_id',
        'shipment_id',
        'rfq_id',
        'created_by',
        'pricing_rule_id',
        'origin_country',
        'origin_port_id',
        'destination_country',
        'destination_port_id',
        'freight_mode',
        'service_type',
        'incoterm',
        'container_type',
        'container_count',
        'cargo_weight',
        'cargo_volume',
        'hs_code',
        'hs_code_record_id',
        'goods_description',
        'pickup_address',
        'delivery_address',
        'estimated_days',
        'freight_cost',
        'insurance_cost',
        'customs_estimate',
        'total_cost',
        'total_cost_base_currency',
        'landed_cost_total',
        'currency',
        'exchange_rate_snapshot',
        'currency_snapshot',
        'base_currency',
        'status',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'container_count' => 'integer',
        'cargo_weight' => 'decimal:3',
        'cargo_volume' => 'decimal:3',
        'estimated_days' => 'integer',
        'freight_cost' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'customs_estimate' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'total_cost_base_currency' => 'decimal:2',
        'landed_cost_total' => 'decimal:2',
        'request_payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $freightQuote) {
            if (!$freightQuote->currency) {
                return;
            }

            $currencyService = app(CurrencyService::class);
            $freightQuote->exchange_rate_snapshot = $freightQuote->exchange_rate_snapshot ?: $currencyService->rateFor($freightQuote->currency);
            $freightQuote->currency_snapshot = $freightQuote->currency_snapshot ?: $currencyService->snapshot($freightQuote->currency);
        });
    }

    public function buyerCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'buyer_company_id');
    }

    public function supplierCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'supplier_company_id');
    }

    public function forwarder()
    {
        return $this->belongsTo(B2BFreightForwarder::class, 'forwarder_id');
    }

    public function pricingRule()
    {
        return $this->belongsTo(B2BFreightPricingRule::class, 'pricing_rule_id');
    }

    public function originPort()
    {
        return $this->belongsTo(B2BPort::class, 'origin_port_id');
    }

    public function destinationPort()
    {
        return $this->belongsTo(B2BPort::class, 'destination_port_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(B2BPurchaseOrder::class, 'purchase_order_id');
    }

    public function proformaInvoice()
    {
        return $this->belongsTo(B2BProformaInvoice::class, 'proforma_invoice_id');
    }

    public function sampleOrder()
    {
        return $this->belongsTo(B2BSampleOrder::class, 'sample_order_id');
    }

    public function shipment()
    {
        return $this->belongsTo(B2BShipment::class, 'shipment_id');
    }

    public function containerShipments()
    {
        return $this->hasMany(B2BContainerShipment::class, 'freight_quote_id');
    }

    public function costs()
    {
        return $this->hasMany(B2BFreightQuoteCost::class, 'freight_quote_id')->orderBy('sort_order')->orderBy('id');
    }

    public function customsDocuments()
    {
        return $this->morphMany(B2BCustomsDocument::class, 'documentable');
    }

    public function hsCodeRecord()
    {
        return $this->belongsTo(B2BHsCode::class, 'hs_code_record_id');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
