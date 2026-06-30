<?php

namespace App\Http\Controllers;

use App\Models\B2BCompany;
use App\Models\B2BProductPromotionPackage;
use App\Models\B2BProductPromotionRequest;
use App\Models\Product;
use App\Services\B2BCompanyService;
use App\Services\B2BProductPromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class B2BProductPromotionController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BProductPromotionService $productPromotionService
    ) {
    }

    public function adminIndex()
    {
        $packages = B2BProductPromotionPackage::orderBy('sort_order')->orderBy('id')->get();
        $projection = $this->productPromotionService->revenueProjection(
            Product::where('wholesale_product', 1)
                ->where('approved', 1)
                ->where('published', 1)
                ->count()
        );

        return view('backend.b2b.product_promotions.index', compact('packages', 'projection'));
    }

    public function adminCreate()
    {
        return view('backend.b2b.product_promotions.create');
    }

    public function adminStore(Request $request)
    {
        B2BProductPromotionPackage::create($this->validatedData($request));

        flash(translate('Sponsored product package created successfully.'))->success();

        return redirect()->route('admin.b2b.product-promotions.index');
    }

    public function adminEdit($id)
    {
        $package = B2BProductPromotionPackage::findOrFail($id);

        return view('backend.b2b.product_promotions.edit', compact('package'));
    }

    public function adminUpdate(Request $request, $id)
    {
        $package = B2BProductPromotionPackage::findOrFail($id);
        $package->update($this->validatedData($request));

        flash(translate('Sponsored product package updated successfully.'))->success();

        return redirect()->route('admin.b2b.product-promotions.index');
    }

    public function adminDestroy($id)
    {
        B2BProductPromotionPackage::findOrFail($id)->delete();

        flash(translate('Sponsored product package deleted successfully.'))->success();

        return back();
    }

    public function adminRequests()
    {
        $requests = B2BProductPromotionRequest::with(['company.user', 'package', 'requester', 'approver'])
            ->latest()
            ->paginate(20);

        return view('backend.b2b.product_promotions.requests', compact('requests'));
    }

    public function approveRequest($id)
    {
        $requestRecord = B2BProductPromotionRequest::with(['company', 'package'])->findOrFail($id);

        abort_if($requestRecord->status !== 'pending', 404);

        $this->productPromotionService->activatePackage($requestRecord->company, $requestRecord->package);

        $requestRecord->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_note' => null,
        ]);

        flash(translate('Sponsored product package request approved successfully.'))->success();

        return back();
    }

    public function rejectRequest(Request $request, $id)
    {
        $data = $request->validate([
            'rejection_note' => 'required|string|max:1000',
        ]);

        $requestRecord = B2BProductPromotionRequest::findOrFail($id);
        $requestRecord->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_note' => $data['rejection_note'],
        ]);

        flash(translate('Sponsored product package request rejected successfully.'))->success();

        return back();
    }

    public function companyIndex()
    {
        $company = $this->getSupplierCompany();
        $packages = B2BProductPromotionPackage::active()->orderBy('sort_order')->orderBy('amount')->get();
        $currentPackage = $this->productPromotionService->getActivePackageForCompany($company);
        $requests = $company->productPromotionRequests()->with('package')->latest()->get();
        $promotedProducts = $company->productPromotions()->active()->with('product')->latest()->get();
        $remainingSlots = $this->productPromotionService->getRemainingPromotionSlots($company);
        $projection = $this->productPromotionService->revenueProjection(
            Product::where('wholesale_product', 1)
                ->where('approved', 1)
                ->where('published', 1)
                ->count()
        );

        return view('b2b.product_promotions.index', compact(
            'company',
            'packages',
            'currentPackage',
            'requests',
            'promotedProducts',
            'remainingSlots',
            'projection'
        ));
    }

    public function activateFree($id)
    {
        $company = $this->getSupplierCompany();
        $package = B2BProductPromotionPackage::active()->findOrFail($id);

        if ((float) $package->amount > 0) {
            abort(403);
        }

        $this->productPromotionService->activatePackage($company, $package);

        flash(translate('Sponsored product package activated successfully.'))->success();

        return back();
    }

    public function requestPaid(Request $request, $id)
    {
        $company = $this->getSupplierCompany();
        $package = B2BProductPromotionPackage::active()->findOrFail($id);

        if ((float) $package->amount <= 0) {
            abort(403);
        }

        $request->validate([
            'note' => 'nullable|string|max:1000',
            'payment_reference' => 'required|string|max:255',
            'payment_notes' => 'nullable|string|max:2000',
        ]);

        $alreadyPending = $company->productPromotionRequests()
            ->where('b2b_product_promotion_package_id', $package->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            flash(translate('A pending sponsored product request already exists for this package.'))->warning();
            return back();
        }

        $this->productPromotionService->createRequest($company, $package, Auth::id(), [
            'note' => $request->note,
            'payment_reference' => $request->payment_reference,
            'payment_notes' => $request->payment_notes,
        ]);

        flash(translate('Sponsored product package request submitted successfully. Admin approval is pending.'))->success();

        return back();
    }

    public function purchasePackage(Request $request, $id)
    {
        $company = $this->getSupplierCompany();
        $package = B2BProductPromotionPackage::active()->findOrFail($id);

        if ((float) $package->amount <= 0) {
            $this->productPromotionService->activatePackage($company, $package);
            flash(translate('Sponsored product package activated successfully.'))->success();

            return back();
        }

        $request->validate([
            'payment_option' => 'required|string|max:100',
        ]);

        if ($this->productPromotionService->getActivePackageForCompany($company)?->id === $package->id) {
            flash(translate('This sponsored product package is already active.'))->warning();

            return back();
        }

        $paymentData = [
            'seller_package_id' => 0,
            'b2b_package_id' => 0,
            'b2b_premium_verification_package_id' => 0,
            'b2b_product_promotion_package_id' => $package->id,
            'b2b_company_id' => $company->id,
            'b2b_user_id' => Auth::id(),
            'payment_method' => $request->payment_option,
        ];

        $request->session()->put('payment_type', 'seller_package_payment');
        $request->session()->put('payment_data', $paymentData);

        $decorator = 'App\\Http\\Controllers\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . 'Controller';

        if (class_exists($decorator)) {
            return (new $decorator())->pay($request);
        }

        Session::forget('payment_type');
        Session::forget('payment_data');
        flash(translate('Selected payment method is not available right now.'))->warning();

        return back();
    }

    public function purchasePaymentDone(array $paymentData, ?string $payment = null)
    {
        $package = B2BProductPromotionPackage::active()->findOrFail($paymentData['b2b_product_promotion_package_id']);
        $company = B2BCompany::findOrFail($paymentData['b2b_company_id']);
        $userId = (int) ($paymentData['b2b_user_id'] ?? Auth::id());

        if ($userId && Auth::id() !== $userId) {
            Auth::loginUsingId($userId);
        }

        $this->productPromotionService->activatePackage($company, $package);
        $this->productPromotionService->recordAutomatedPurchase(
            $company,
            $package,
            $userId ?: Auth::id(),
            (string) ($paymentData['payment_method'] ?? 'online_payment'),
            $payment
        );

        Session::forget('payment_type');
        Session::forget('payment_data');

        flash(translate('Sponsored product package purchasing successful.'))->success();

        return redirect()->route('seller.b2b.product-promotions.index');
    }

    public function toggleProduct(Request $request, $productId)
    {
        $data = $request->validate([
            'status' => 'required|boolean',
        ]);

        $company = $this->getSupplierCompany();
        $product = Product::where('id', $productId)
            ->where('user_id', $company->user_id)
            ->where('wholesale_product', 1)
            ->firstOrFail();

        try {
            if ((bool) $data['status']) {
                $this->productPromotionService->promoteProduct($company, $product);

                return response()->json([
                    'status' => 1,
                    'message' => translate('Product added to sponsored promotion successfully.'),
                    'remaining_slots' => $this->productPromotionService->getRemainingPromotionSlots($company),
                ]);
            }

            $this->productPromotionService->unpromoteProduct($company, $product);

            return response()->json([
                'status' => 1,
                'message' => translate('Product removed from sponsored promotion successfully.'),
                'remaining_slots' => $this->productPromotionService->getRemainingPromotionSlots($company),
            ]);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'status' => 0,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    protected function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1|max:3650',
            'product_limit' => 'required|integer|min:1|max:100000',
            'logo' => 'nullable|string|max:255',
            'highlight_text' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    protected function getSupplierCompany(): B2BCompany
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless(
            $company &&
            $this->b2bCompanyService->isApprovedSupplier(Auth::id(), $company->id),
            403
        );

        return $company;
    }
}
