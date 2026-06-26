<?php

namespace App\Models;

use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Model;

class B2BQuotation extends Model
{
    protected $table = 'b2b_quotations';

    protected $fillable = [
        'rfq_id',
        'supplier_user_id',
        'supplier_company_id',
        'product_id',
        'price',
        'currency',
        'exchange_rate_snapshot',
        'currency_snapshot',
        'moq',
        'lead_time_days',
        'shipping_terms',
        'incoterm',
        'payment_terms',
        'message',
        'attachment',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'moq' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $quotation) {
            if (!$quotation->currency) {
                return;
            }

            $currencyService = app(CurrencyService::class);
            $quotation->exchange_rate_snapshot = $quotation->exchange_rate_snapshot ?: $currencyService->rateFor($quotation->currency);
            $quotation->currency_snapshot = $quotation->currency_snapshot ?: $currencyService->snapshot($quotation->currency);
        });
    }

    public function rfq()
    {
        return $this->belongsTo(B2BRfq::class, 'rfq_id');
    }

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_user_id');
    }

    public function supplierCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'supplier_company_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder()
    {
        return $this->hasOne(B2BPurchaseOrder::class, 'quotation_id');
    }

    public function negotiation()
    {
        return $this->hasOne(B2BNegotiation::class, 'quotation_id');
    }

    public function sampleOrders()
    {
        return $this->hasMany(B2BSampleOrder::class, 'quotation_id');
    }
}
