<?php

namespace App\Models;

use App\Services\Currency\CurrencyService;
use Illuminate\Database\Eloquent\Model;

class B2BRfq extends Model
{
    protected $table = 'b2b_rfqs';

    protected $fillable = [
        'user_id',
        'b2b_company_id',
        'supplier_company_id',
        'product_id',
        'category_id',
        'title',
        'description',
        'quantity',
        'unit',
        'target_price',
        'currency',
        'exchange_rate_snapshot',
        'currency_snapshot',
        'incoterm',
        'destination_country',
        'destination_city',
        'expected_delivery_date',
        'attachment',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'expected_delivery_date' => 'date',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $rfq) {
            if (!$rfq->currency) {
                return;
            }

            $currencyService = app(CurrencyService::class);
            $rfq->exchange_rate_snapshot = $rfq->exchange_rate_snapshot ?: $currencyService->rateFor($rfq->currency);
            $rfq->currency_snapshot = $rfq->currency_snapshot ?: $currencyService->snapshot($rfq->currency);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'b2b_company_id');
    }

    public function targetSupplierCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'supplier_company_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function quotations()
    {
        return $this->hasMany(B2BQuotation::class, 'rfq_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(B2BPurchaseOrder::class, 'rfq_id');
    }

    public function sampleOrders()
    {
        return $this->hasMany(B2BSampleOrder::class, 'rfq_id');
    }

    public function negotiations()
    {
        return $this->hasMany(B2BNegotiation::class, 'rfq_id');
    }
}
