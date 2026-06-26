<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BPremiumVerificationRequest extends Model
{
    protected $table = 'b2b_premium_verification_requests';

    protected $fillable = [
        'b2b_company_id',
        'b2b_premium_verification_package_id',
        'requested_by',
        'approved_by',
        'amount',
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
        return $this->belongsTo(B2BPremiumVerificationPackage::class, 'b2b_premium_verification_package_id');
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
