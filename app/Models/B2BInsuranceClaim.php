<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class B2BInsuranceClaim extends Model
{
    protected $table = 'b2b_insurance_claims';

    public const STATUSES = [
        'submitted',
        'review',
        'investigation',
        'approved',
        'rejected',
        'partial_settlement',
        'settled',
        'appealed',
    ];

    protected $fillable = [
        'claim_number',
        'policy_id',
        'provider_id',
        'buyer_company_id',
        'supplier_company_id',
        'claimant_user_id',
        'claimant_company_id',
        'reviewed_by',
        'shipment_id',
        'container_shipment_id',
        'freight_quote_id',
        'purchase_order_id',
        'proforma_invoice_id',
        'status',
        'claim_type',
        'incident_country',
        'incident_location',
        'incident_reference',
        'summary',
        'description',
        'claim_amount',
        'approved_amount',
        'settled_amount',
        'currency',
        'evidence',
        'timeline',
        'comments',
        'validation_summary',
        'fraud_signals',
        'resolution_data',
        'incident_at',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'settled_at',
        'appealed_at',
    ];

    protected $casts = [
        'claim_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'settled_amount' => 'decimal:2',
        'evidence' => 'array',
        'timeline' => 'array',
        'comments' => 'array',
        'validation_summary' => 'array',
        'fraud_signals' => 'array',
        'resolution_data' => 'array',
        'incident_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'settled_at' => 'datetime',
        'appealed_at' => 'datetime',
    ];

    public function policy()
    {
        return $this->belongsTo(B2BInsurancePolicy::class, 'policy_id');
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

    public function claimant()
    {
        return $this->belongsTo(User::class, 'claimant_user_id');
    }

    public function claimantCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'claimant_company_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function documents()
    {
        return $this->hasMany(B2BInsuranceClaimDocument::class, 'claim_id');
    }

    public function payments()
    {
        return $this->hasMany(B2BInsurancePayment::class, 'claim_id');
    }

    public function events()
    {
        return $this->morphMany(B2BInsuranceEvent::class, 'eventable');
    }

    public function apiLogs()
    {
        return $this->morphMany(B2BInsuranceApiLog::class, 'loggable');
    }

    public function filterPersistable(array $attributes): array
    {
        static $columns;
        $columns ??= Schema::getColumnListing($this->getTable());

        return Arr::only($attributes, $columns);
    }
}
