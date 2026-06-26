<?php

namespace App\Models;

use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Model;

class B2BSampleOrder extends Model
{
    protected $table = 'b2b_sample_orders';

    protected $fillable = [
        'sample_number',
        'buyer_company_id',
        'supplier_company_id',
        'buyer_user_id',
        'supplier_user_id',
        'product_id',
        'rfq_id',
        'quotation_id',
        'currency',
        'exchange_rate_snapshot',
        'currency_snapshot',
        'quantity',
        'unit',
        'sample_price',
        'shipping_amount',
        'sample_processing_fee_fixed_snapshot',
        'sample_processing_fee_amount',
        'total_amount',
        'status',
        'payment_reference',
        'notes',
        'requested_at',
        'supplier_responded_at',
        'paid_at',
        'completed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'sample_price' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'sample_processing_fee_fixed_snapshot' => 'decimal:2',
        'sample_processing_fee_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'supplier_responded_at' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $sampleOrder) {
            if (!$sampleOrder->currency) {
                return;
            }

            $currencyService = app(CurrencyService::class);
            $sampleOrder->exchange_rate_snapshot = $sampleOrder->exchange_rate_snapshot ?: $currencyService->rateFor($sampleOrder->currency);
            $sampleOrder->currency_snapshot = $sampleOrder->currency_snapshot ?: $currencyService->snapshot($sampleOrder->currency);
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

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function rfq()
    {
        return $this->belongsTo(B2BRfq::class, 'rfq_id');
    }

    public function quotation()
    {
        return $this->belongsTo(B2BQuotation::class, 'quotation_id');
    }

    public function shippingQuotes()
    {
        return $this->hasMany(B2BShippingQuote::class, 'sample_order_id');
    }

    public function shipment()
    {
        return $this->hasOne(B2BShipment::class, 'sample_order_id');
    }

    public function freightQuotes()
    {
        return $this->hasMany(B2BFreightQuote::class, 'sample_order_id');
    }

    public function documents()
    {
        return $this->morphMany(B2BTradeDocument::class, 'documentable');
    }
}
