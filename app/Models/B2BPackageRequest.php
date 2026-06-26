<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BPackageRequest extends Model
{
    protected $table = 'b2b_package_requests';

    protected $fillable = [
        'b2b_company_id',
        'b2b_package_id',
        'request_type',
        'requested_by',
        'approved_by',
        'amount',
        'billing_cycle',
        'status',
        'note',
        'payment_reference',
        'payment_notes',
        'payment_submitted_at',
        'rejection_note',
        'requested_at',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_type' => 'string',
        'payment_submitted_at' => 'datetime',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(B2BCompany::class, 'b2b_company_id');
    }

    public function package()
    {
        return $this->belongsTo(B2BPackage::class, 'b2b_package_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
