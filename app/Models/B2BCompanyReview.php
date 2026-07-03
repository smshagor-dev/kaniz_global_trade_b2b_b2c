<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCompanyReview extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'reviewer_user_id',
        'reviewer_company_id',
        'reviewed_user_id',
        'reviewed_company_id',
        'reviewer_role',
        'reviewed_role',
        'rating',
        'comment',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(B2BPurchaseOrder::class, 'purchase_order_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function reviewedUser()
    {
        return $this->belongsTo(User::class, 'reviewed_user_id');
    }

    public function reviewerCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'reviewer_company_id');
    }

    public function reviewedCompany()
    {
        return $this->belongsTo(B2BCompany::class, 'reviewed_company_id');
    }
}
