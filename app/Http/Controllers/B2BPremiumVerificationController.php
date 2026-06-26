<?php

namespace App\Http\Controllers;

use App\Models\B2BCompany;
use App\Models\B2BPremiumVerificationPackage;
use App\Models\B2BPremiumVerificationRequest;
use App\Services\B2BCompanyService;
use App\Services\B2BPremiumVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BPremiumVerificationController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPremiumVerificationService $premiumVerificationService
    ) {
    }

    public function adminIndex()
    {
        $packages = B2BPremiumVerificationPackage::orderBy('sort_order')->orderBy('id')->get();
        $projection = $this->premiumVerificationService->revenueProjection(
            B2BCompany::where('verification_status', 'approved')->count()
        );

        return view('backend.b2b.premium_verifications.index', compact('packages', 'projection'));
    }

    public function adminCreate()
    {
        return view('backend.b2b.premium_verifications.create');
    }

    public function adminStore(Request $request)
    {
        B2BPremiumVerificationPackage::create($this->validatedData($request));

        flash(translate('Premium verification package created successfully.'))->success();

        return redirect()->route('admin.b2b.premium-verifications.index');
    }

    public function adminEdit($id)
    {
        $package = B2BPremiumVerificationPackage::findOrFail($id);

        return view('backend.b2b.premium_verifications.edit', compact('package'));
    }

    public function adminUpdate(Request $request, $id)
    {
        $package = B2BPremiumVerificationPackage::findOrFail($id);
        $package->update($this->validatedData($request));

        flash(translate('Premium verification package updated successfully.'))->success();

        return redirect()->route('admin.b2b.premium-verifications.index');
    }

    public function adminDestroy($id)
    {
        B2BPremiumVerificationPackage::findOrFail($id)->delete();

        flash(translate('Premium verification package deleted successfully.'))->success();

        return back();
    }

    public function adminRequests()
    {
        $requests = B2BPremiumVerificationRequest::with(['company.user', 'package', 'requester', 'approver'])
            ->latest()
            ->paginate(20);

        return view('backend.b2b.premium_verifications.requests', compact('requests'));
    }

    public function approveRequest($id)
    {
        $requestRecord = B2BPremiumVerificationRequest::with(['company', 'package'])->findOrFail($id);

        abort_if($requestRecord->status !== 'pending', 404);

        $this->premiumVerificationService->activatePackage($requestRecord->company, $requestRecord->package);

        $requestRecord->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_note' => null,
        ]);

        flash(translate('Premium verification request approved successfully.'))->success();

        return back();
    }

    public function rejectRequest(Request $request, $id)
    {
        $data = $request->validate([
            'rejection_note' => 'required|string|max:1000',
        ]);

        $requestRecord = B2BPremiumVerificationRequest::findOrFail($id);
        $requestRecord->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_note' => $data['rejection_note'],
        ]);

        flash(translate('Premium verification request rejected successfully.'))->success();

        return back();
    }

    public function companyIndex()
    {
        $company = $this->getApprovedCompany();
        $packages = B2BPremiumVerificationPackage::active()->orderBy('sort_order')->orderBy('amount')->get();
        $currentPackage = $this->premiumVerificationService->getActivePackageForCompany($company);
        $requests = $company->premiumVerificationRequests()->with('package')->latest()->get();
        $projection = $this->premiumVerificationService->revenueProjection(
            B2BCompany::where('verification_status', 'approved')->count()
        );

        return view('b2b.premium_verifications.index', compact(
            'company',
            'packages',
            'currentPackage',
            'requests',
            'projection'
        ));
    }

    public function activateFree($id)
    {
        $company = $this->getApprovedCompany();
        $package = B2BPremiumVerificationPackage::active()->findOrFail($id);

        if ((float) $package->amount > 0) {
            abort(403);
        }

        $this->premiumVerificationService->activatePackage($company, $package);

        flash(translate('Premium verification activated successfully.'))->success();

        return back();
    }

    public function requestPaid(Request $request, $id)
    {
        $company = $this->getApprovedCompany();
        $package = B2BPremiumVerificationPackage::active()->findOrFail($id);

        if ((float) $package->amount <= 0) {
            abort(403);
        }

        $request->validate([
            'note' => 'nullable|string|max:1000',
            'payment_reference' => 'required|string|max:255',
            'payment_notes' => 'nullable|string|max:2000',
        ]);

        $alreadyPending = $company->premiumVerificationRequests()
            ->where('b2b_premium_verification_package_id', $package->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            flash(translate('A pending premium verification request already exists for this package.'))->warning();
            return back();
        }

        $this->premiumVerificationService->createRequest($company, $package, Auth::id(), [
            'note' => $request->note,
            'payment_reference' => $request->payment_reference,
            'payment_notes' => $request->payment_notes,
        ]);

        flash(translate('Premium verification request submitted successfully. Admin approval is pending.'))->success();

        return back();
    }

    protected function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
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

    protected function getApprovedCompany(): B2BCompany
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless(
            $company &&
            (
                $this->b2bCompanyService->isApprovedSupplier(Auth::id(), $company->id)
                || $this->b2bCompanyService->isApprovedBuyer(Auth::id(), $company->id)
            ),
            403
        );

        return $company;
    }
}
