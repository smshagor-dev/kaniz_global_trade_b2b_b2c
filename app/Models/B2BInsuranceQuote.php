<?php

namespace App\Models;

use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BInsuranceQuote extends Model
{
    protected $table = 'b2b_insurance_quotes';

    protected $fillable = [
        'quote_number',
        'provider_id',
        'buyer_company_id',
        'supplier_company_id',
        'created_by',
        'shipment_id',
        'container_shipment_id',
        'freight_quote_id',
        'purchase_order_id',
        'proforma_invoice_id',
        'insurance_type',
        'transport_mode',
        'incoterm',
        'container_type',
        'origin_country',
        'destination_country',
        'origin_port',
        'destination_port',
        'commodity',
        'hs_code',
        'weight',
        'volume',
        'shipment_value',
        'coverage_amount',
        'currency',
        'exchange_rate_snapshot',
        'currency_snapshot',
        'risk_score',
        'risk_breakdown',
        'premium',
        'tax_amount',
        'additional_charges',
        'platform_fee',
        'discount_amount',
        'final_amount',
        'premium_breakdown',
        'calculation_history',
        'request_payload',
        'response_payload',
        'ai_recommendation',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'weight' => 'decimal:3',
        'volume' => 'decimal:3',
        'shipment_value' => 'decimal:2',
        'coverage_amount' => 'decimal:2',
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'risk_score' => 'decimal:2',
        'risk_breakdown' => 'array',
        'premium' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'additional_charges' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'premium_breakdown' => 'array',
        'calculation_history' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'ai_recommendation' => 'array',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $quote) {
            if (!$quote->currency) {
                return;
            }

            $currencyService = app(CurrencyService::class);
            $quote->exchange_rate_snapshot = $quote->exchange_rate_snapshot ?: $currencyService->rateFor($quote->currency);
            $quote->currency_snapshot = $quote->currency_snapshot ?: $currencyService->snapshot($quote->currency);
        });
    }

    public function provider()
    {
        return $this->belongsTo(B2BInsuranceProvider::class, 'provider_id');
    }

    public function buyerCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'buyer_company_id');
    }

    public function supplierCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'supplier_company_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shipment()
    {
        return $this->belongsTo(B2BShipment::class, 'shipment_id');
    }

    public function containerShipment()
    {
        return $this->belongsTo(B2BContainerShipment::class, 'container_shipment_id');
    }

    public function freightQuote()
    {
        return $this->belongsTo(B2BFreightQuote::class, 'freight_quote_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(B2BPurchaseOrder::class, 'purchase_order_id');
    }

    public function proformaInvoice()
    {
        return $this->belongsTo(B2BProformaInvoice::class, 'proforma_invoice_id');
    }

    public function policy()
    {
        return $this->hasOne(B2BInsurancePolicy::class, 'quote_id');
    }

    public function events()
    {
        return $this->morphMany(B2BInsuranceEvent::class, 'eventable');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
