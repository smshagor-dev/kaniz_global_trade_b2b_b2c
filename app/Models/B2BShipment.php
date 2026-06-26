<?php

namespace App\Models;

use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BShipment extends Model
{
    protected $table = 'b2b_shipments';

    protected $fillable = [
        'shipment_number',
        'purchase_order_id',
        'proforma_invoice_id',
        'sample_order_id',
        'shipping_quote_id',
        'supplier_company_id',
        'buyer_company_id',
        'shipping_provider_id',
        'created_by',
        'transport_mode',
        'incoterm',
        'tracking_number',
        'carrier_reference',
        'carrier_service',
        'service_type',
        'delivery_priority',
        'carrier_status',
        'last_tracked_at',
        'tracking_url',
        'live_tracking_enabled',
        'sync_error',
        'currency',
        'declared_value',
        'insurance_amount',
        'total_weight',
        'package_length',
        'package_width',
        'package_height',
        'origin_country',
        'destination_country',
        'estimated_departure',
        'estimated_arrival',
        'actual_departure_at',
        'delivered_at',
        'status',
        'notes',
        'carrier_payload',
        'last_carrier_response',
        'current_location',
        'current_country',
        'estimated_delivery_at',
        'signed_receiver',
        'proof_of_delivery_url',
        'pickup_scheduled_at',
        'pickup_confirmation',
        'pickup_status',
        'latest_label_path',
        'latest_label_format',
        'rate_request_payload',
        'rate_response_payload',
        'exchange_rate_snapshot',
        'currency_snapshot',
    ];

    protected $casts = [
        'declared_value' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'total_weight' => 'decimal:3',
        'package_length' => 'decimal:2',
        'package_width' => 'decimal:2',
        'package_height' => 'decimal:2',
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'estimated_departure' => 'date',
        'estimated_arrival' => 'date',
        'last_tracked_at' => 'datetime',
        'live_tracking_enabled' => 'boolean',
        'actual_departure_at' => 'datetime',
        'delivered_at' => 'datetime',
        'carrier_payload' => 'array',
        'estimated_delivery_at' => 'datetime',
        'pickup_scheduled_at' => 'datetime',
        'rate_request_payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $shipment) {
            if (!$shipment->currency) {
                return;
            }

            $currencyService = app(CurrencyService::class);
            $shipment->exchange_rate_snapshot = $shipment->exchange_rate_snapshot ?: $currencyService->rateFor($shipment->currency);
            $shipment->currency_snapshot = $shipment->currency_snapshot ?: $currencyService->snapshot($shipment->currency);
        });
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

    public function shippingQuote()
    {
        return $this->belongsTo(B2BShippingQuote::class, 'shipping_quote_id');
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

    public function events()
    {
        return $this->hasMany(B2BShipmentEvent::class, 'shipment_id')->orderBy('event_at');
    }

    public function documents()
    {
        return $this->morphMany(B2BTradeDocument::class, 'documentable');
    }

    public function freightQuotes()
    {
        return $this->hasMany(B2BFreightQuote::class, 'shipment_id');
    }

    public function containerShipments()
    {
        return $this->hasMany(B2BContainerShipment::class, 'shipment_id');
    }

    public function customsDocuments()
    {
        return $this->morphMany(B2BCustomsDocument::class, 'documentable');
    }

    public function auditLogs()
    {
        return $this->morphMany(B2BAuditLog::class, 'auditable');
    }

    public function usesLiveTracking(): bool
    {
        return $this->live_tracking_enabled && $this->shippingProvider?->isApiProvider();
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
