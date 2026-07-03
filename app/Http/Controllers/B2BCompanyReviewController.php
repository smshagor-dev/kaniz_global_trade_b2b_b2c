<?php

namespace App\Http\Controllers;

use App\Models\B2BCompanyReview;
use App\Models\B2BPurchaseOrder;
use App\Services\B2BCompanyService;
use App\Services\B2BPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BCompanyReviewController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPermissionService $b2bPermissionService
    ) {
    }

    public function store(Request $request, int $purchaseOrderId)
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());
        abort_unless($company && $this->b2bPermissionService->canAccessCompany(Auth::id(), $company->id), 403);

        $purchaseOrder = B2BPurchaseOrder::with(['buyerCompany', 'supplierCompany', 'companyReviews'])->findOrFail($purchaseOrderId);

        if ($purchaseOrder->status !== 'completed') {
            flash(translate('Reviews can only be submitted after the purchase order is completed.'))->warning();
            return back();
        }

        if ((int) $company->id === (int) $purchaseOrder->buyer_company_id) {
            $reviewerRole = 'buyer';
            $reviewedRole = 'supplier';
            $reviewedCompany = $purchaseOrder->supplierCompany;
            $reviewedUserId = $purchaseOrder->supplier_user_id;
        } elseif ((int) $company->id === (int) $purchaseOrder->supplier_company_id) {
            $reviewerRole = 'supplier';
            $reviewedRole = 'buyer';
            $reviewedCompany = $purchaseOrder->buyerCompany;
            $reviewedUserId = $purchaseOrder->buyer_user_id;
        } else {
            abort(403);
        }

        if (!$reviewedCompany) {
            flash(translate('The counterparty company could not be found for this purchase order.'))->warning();
            return back();
        }

        $existingReview = $purchaseOrder->companyReviews
            ->first(fn (B2BCompanyReview $review) => (int) $review->reviewer_company_id === (int) $company->id);

        if ($existingReview) {
            flash(translate('You have already submitted a review for this purchase order.'))->warning();
            return back();
        }

        B2BCompanyReview::create([
            'purchase_order_id' => $purchaseOrder->id,
            'reviewer_user_id' => Auth::id(),
            'reviewer_company_id' => $company->id,
            'reviewed_user_id' => $reviewedUserId,
            'reviewed_company_id' => $reviewedCompany->id,
            'reviewer_role' => $reviewerRole,
            'reviewed_role' => $reviewedRole,
            'rating' => (int) $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        flash(translate('Review submitted successfully.'))->success();

        return back();
    }
}
