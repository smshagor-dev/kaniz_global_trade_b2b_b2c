<?php

namespace App\Models;

use App\Services\Currency\CurrencyService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BInsurancePolicy extends Model
{
    protected $table = 'b2b_insurance_policies';

    public const STATUSES = [
        'draft',
        'quoted',
        'approved',
        'active',
        'in_transit',
        'claim_submitted',
        'expired',
        'cancelled',
        'settled',
    ];

    protected $fillable = [
        'policy_number',
        'provider_id',
        'quote_id',
        'buyer_company_id',
        'supplier_company_id',
        'policy_holder_user_id',
        'issued_by',
        'shipment_id',
        'container_shipment_id',
        'freight_quote_id',
        'purchase_order_id',
        'proforma_invoice_id',
        'finance_reference_type',
        'finance_reference_id',
        'insurance_type',
        'transport_mode',
        'coverage_plan',
        'status',
        'coverage_amount',
        'premium',
        'tax_amount',
        'deductible_amount',
        'insured_value',
        'currency',
        'exchange_rate_snapshot',
        'currency_snapshot',
        'coverage_details',
        'premium_breakdown',
        'attachment_paths',
        'metadata',
        'coverage_start',
        'coverage_end',
        'issued_at',
        'activated_at',
        'expired_at',
        'cancelled_at',
    ];

    protected $casts = [
        'coverage_amount' => 'decimal:2',
        'premium' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'deductible_amount' => 'decimal:2',
        'insured_value' => 'decimal:2',
        'exchange_rate_snapshot' => 'decimal:8',
        'currency_snapshot' => 'array',
        'coverage_details' => 'array',
        'premium_breakdown' => 'array',
        'attachment_paths' => 'array',
        'metadata' => 'array',
        'coverage_start' => 'date',
        'coverage_end' => 'date',
        'issued_at' => 'datetime',
        'activated_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $policy) {
            if (!$policy->currency) {
                return;
            }

            $currencyService = app(CurrencyService::class);
            $policy->exchange_rate_snapshot = $policy->exchange_rate_snapshot ?: $currencyService->rateFor($policy->currency);
            $policy->currency_snapshot = $policy->currency_snapshot ?: $currencyService->snapshot($policy->currency);
        });
    }

    public function provider()
    {
        return $this->belongsTo(B2BInsuranceProvider::class, 'provider_id');
    }

    public function quote()
    {
        return $this->belongsTo(B2BInsuranceQuote::class, 'quote_id');
    }

    public function buyerCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'buyer_company_id');
    }

    public function supplierCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'supplier_company_id');
    }

    public function policyHolder()
    {
        return $this->belongsTo(User::class, 'policy_holder_user_id');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
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

    public function claims()
    {
        return $this->hasMany(B2BInsuranceClaim::class, 'policy_id');
    }

    public function payments()
    {
        return $this->hasMany(B2BInsurancePayment::class, 'policy_id');
    }

    public function events()
    {
        return $this->morphMany(B2BInsuranceEvent::class, 'eventable');
    }

    public function apiLogs()
    {
        return $this->morphMany(B2BInsuranceApiLog::class, 'loggable');
    }

    public function isActiveForDate($date = null): bool
    {
        $date = $date ? Carbon::parse($date) : now();

        return in_array($this->status, ['approved', 'active', 'in_transit', 'claim_submitted'], true)
            && (!$this->coverage_start || $date->greaterThanOrEqualTo($this->coverage_start))
            && (!$this->coverage_end || $date->lessThanOrEqualTo($this->coverage_end));
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
