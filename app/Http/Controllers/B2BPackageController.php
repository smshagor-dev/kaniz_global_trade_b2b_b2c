<?php

namespace App\Http\Controllers;

use App\Models\B2BCompany;
use App\Models\B2BPackage;
use App\Models\B2BPackageRequest;
use App\Services\B2BCompanyService;
use App\Services\B2BPackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class B2BPackageController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPackageService $b2bPackageService
    ) {
    }

    public function adminIndex()
    {
        $packages = B2BPackage::membership()->orderBy('package_for')->orderBy('sort_order')->orderBy('id')->get();

        return view('backend.b2b.packages.index', array_merge(
            compact('packages'),
            $this->packageViewContext()
        ));
    }

    public function adminCreate()
    {
        return view('backend.b2b.packages.create', $this->packageViewContext());
    }

    public function adminStore(Request $request)
    {
        $data = $this->validatedData($request);
        B2BPackage::create($data);

        flash($data['package_type'] === 'supplier_featured'
            ? translate('Supplier featured package created successfully.')
            : translate('B2B package created successfully.'))->success();

        return redirect()->route($data['package_type'] === 'supplier_featured'
            ? 'admin.b2b.featured-packages.index'
            : 'admin.b2b.packages.index');
    }

    public function adminEdit($id)
    {
        $package = B2BPackage::membership()->findOrFail($id);

        return view('backend.b2b.packages.edit', array_merge(
            compact('package'),
            $this->packageViewContext()
        ));
    }

    public function adminUpdate(Request $request, $id)
    {
        $package = B2BPackage::findOrFail($id);
        $data = $this->validatedData($request);
        $package->update($data);

        flash($package->isSupplierFeaturedPackage()
            ? translate('Supplier featured package updated successfully.')
            : translate('B2B package updated successfully.'))->success();

        return redirect()->route($package->isSupplierFeaturedPackage()
            ? 'admin.b2b.featured-packages.index'
            : 'admin.b2b.packages.index');
    }

    public function adminDestroy($id)
    {
        $package = B2BPackage::findOrFail($id);
        $isFeaturedPackage = $package->isSupplierFeaturedPackage();
        $package->delete();

        flash($isFeaturedPackage
            ? translate('Supplier featured package deleted successfully.')
            : translate('B2B package deleted successfully.'))->success();

        return back();
    }

    public function adminRequests()
    {
        $requests = B2BPackageRequest::with(['company.user', 'package', 'requester', 'approver'])
            ->where('request_type', 'membership')
            ->latest()
            ->paginate(20);

        return view('backend.b2b.packages.requests', array_merge(
            compact('requests'),
            $this->packageViewContext()
        ));
    }

    public function adminFeaturedSupplierIndex()
    {
        $packages = B2BPackage::featuredSupplierHomepage()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $featuredProjection = $this->b2bPackageService->featuredSupplierRevenueProjection(
            B2BCompany::publicSuppliers()->count()
        );

        return view('backend.b2b.packages.index', array_merge(
            compact('packages', 'featuredProjection'),
            $this->packageViewContext(true)
        ));
    }

    public function adminFeaturedSupplierCreate()
    {
        return view('backend.b2b.packages.create', $this->packageViewContext(true));
    }

    public function adminFeaturedSupplierEdit($id)
    {
        $package = B2BPackage::featuredSupplierHomepage()->findOrFail($id);

        return view('backend.b2b.packages.edit', array_merge(
            compact('package'),
            $this->packageViewContext(true)
        ));
    }

    public function adminFeaturedSupplierRequests()
    {
        $requests = B2BPackageRequest::with(['company.user', 'package', 'requester', 'approver'])
            ->where('request_type', 'supplier_featured')
            ->latest()
            ->paginate(20);

        return view('backend.b2b.packages.requests', array_merge(
            compact('requests'),
            $this->packageViewContext(true)
        ));
    }

    public function approveRequest($id)
    {
        $requestRecord = B2BPackageRequest::with(['company', 'package'])->findOrFail($id);

        abort_if($requestRecord->status !== 'pending', 404);

        $this->b2bPackageService->activatePackage($requestRecord->company, $requestRecord->package);

        $requestRecord->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_note' => null,
        ]);

        flash(($requestRecord->request_type ?? 'membership') === 'supplier_featured'
            ? translate('Supplier featured package request approved successfully.')
            : translate('Package request approved successfully.'))->success();

        return back();
    }

    public function rejectRequest(Request $request, $id)
    {
        $data = $request->validate([
            'rejection_note' => 'required|string|max:1000',
        ]);

        $requestRecord = B2BPackageRequest::findOrFail($id);
        $requestRecord->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_note' => $data['rejection_note'],
        ]);

        flash(($requestRecord->request_type ?? 'membership') === 'supplier_featured'
            ? translate('Supplier featured package request rejected successfully.')
            : translate('Package request rejected successfully.'))->success();

        return back();
    }

    public function companyIndex()
    {
        $company = $this->getCompany();
        $packageFor = $this->b2bPackageService->getPackageRoleForCompany($company);
        $packages = B2BPackage::active()
            ->membership()
            ->where('package_for', $packageFor)
            ->orderBy('sort_order')
            ->orderBy('amount')
            ->get();
        $currentPackage = $this->b2bPackageService->getActivePackageForCompany($company);
        $requests = $company->membershipPackageRequests()->with('package')->latest()->get();
        $featuredPackages = $packageFor === 'supplier'
            ? B2BPackage::featuredSupplierHomepage()->orderBy('sort_order')->orderBy('amount')->get()
            : collect();
        $currentFeaturedPackage = $packageFor === 'supplier'
            ? $this->b2bPackageService->getActiveFeaturedPackageForCompany($company)
            : null;
        $featuredRequests = $packageFor === 'supplier'
            ? $company->featuredPackageRequests()->with('package')->latest()->get()
            : collect();
        $featuredProjection = $packageFor === 'supplier'
            ? $this->b2bPackageService->featuredSupplierRevenueProjection(B2BCompany::publicSuppliers()->count())
            : null;

        return view('b2b.packages.index', compact(
            'company',
            'packages',
            'currentPackage',
            'requests',
            'packageFor',
            'featuredPackages',
            'currentFeaturedPackage',
            'featuredRequests',
            'featuredProjection'
        ));
    }

    public function activateFree($id)
    {
        $company = $this->getCompany();
        $package = B2BPackage::active()->findOrFail($id);

        abort_unless($package->package_for === $this->b2bPackageService->getPackageRoleForCompany($company), 403);

        if ((float) $package->amount > 0) {
            abort(403);
        }

        $this->b2bPackageService->activatePackage($company, $package);

        flash(translate('Package activated successfully.'))->success();

        return back();
    }

    public function requestPaid(Request $request, $id)
    {
        $company = $this->getCompany();
        $package = B2BPackage::active()->findOrFail($id);

        abort_unless($package->package_for === $this->b2bPackageService->getPackageRoleForCompany($company), 403);

        if ((float) $package->amount <= 0) {
            abort(403);
        }

        $request->validate([
            'note' => 'nullable|string|max:1000',
            'payment_reference' => 'required|string|max:255',
            'payment_notes' => 'nullable|string|max:2000',
        ]);

        $alreadyPending = $company->packageRequests()
            ->where('b2b_package_id', $package->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            flash(translate('A pending request already exists for this package.'))->warning();
            return back();
        }

        $this->b2bPackageService->createRequest($company, $package, Auth::id(), [
            'note' => $request->note,
            'payment_reference' => $request->payment_reference,
            'payment_notes' => $request->payment_notes,
        ]);

        flash(translate('Package request submitted successfully. Admin approval is pending.'))->success();

        return back();
    }

    protected function validatedData(Request $request): array
    {
        $forceSupplierFeatured = $request->boolean('force_supplier_featured_package');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'package_for' => ['required', Rule::in(['buyer', 'supplier'])],
            'package_type' => ['nullable', Rule::in(['membership', 'supplier_featured'])],
            'amount' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1|max:3650',
            'rfq_limit' => 'required|integer|min:0',
            'quotation_limit' => 'required|integer|min:0',
            'product_limit' => 'required|integer|min:0',
            'member_limit' => 'required|integer|min:1|max:500',
            'priority_listing' => 'nullable|boolean',
            'featured_profile' => 'nullable|boolean',
            'verified_badge' => 'nullable|boolean',
            'analytics_access' => 'nullable|boolean',
            'dedicated_support' => 'nullable|boolean',
            'logo' => 'nullable|string|max:255',
            'highlight_text' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $data['priority_listing'] = $request->boolean('priority_listing');
        $data['featured_profile'] = $request->boolean('featured_profile');
        $data['verified_badge'] = $request->boolean('verified_badge');
        $data['analytics_access'] = $request->boolean('analytics_access');
        $data['dedicated_support'] = $request->boolean('dedicated_support');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($request->sort_order ?? 0);
        $data['package_type'] = $request->input('package_type', 'membership');

        if ($forceSupplierFeatured) {
            $data['package_for'] = 'supplier';
            $data['package_type'] = 'supplier_featured';
            $data['featured_profile'] = true;
        }

        return $data;
    }

    protected function packageViewContext(bool $featuredSupplierOnly = false): array
    {
        if ($featuredSupplierOnly) {
            return [
                'pageTitle' => translate('Supplier Featured Packages'),
                'pageDescription' => translate('Dedicated supplier homepage placement packages.'),
                'indexRoute' => 'admin.b2b.featured-packages.index',
                'createRoute' => 'admin.b2b.featured-packages.create',
                'storeRoute' => 'admin.b2b.featured-packages.store',
                'editRoute' => 'admin.b2b.featured-packages.edit',
                'updateRoute' => 'admin.b2b.featured-packages.update',
                'deleteRoute' => 'admin.b2b.featured-packages.delete',
                'requestsRoute' => 'admin.b2b.featured-package-requests.index',
                'approveRoute' => 'admin.b2b.featured-package-requests.approve',
                'rejectRoute' => 'admin.b2b.featured-package-requests.reject',
                'requestsButtonLabel' => translate('Featured Package Requests'),
                'forceSupplierFeaturedPackage' => true,
            ];
        }

        return [
            'pageTitle' => translate('B2B Packages'),
            'pageDescription' => translate('Manage buyer and supplier package plans.'),
            'indexRoute' => 'admin.b2b.packages.index',
            'createRoute' => 'admin.b2b.packages.create',
            'storeRoute' => 'admin.b2b.packages.store',
            'editRoute' => 'admin.b2b.packages.edit',
            'updateRoute' => 'admin.b2b.packages.update',
            'deleteRoute' => 'admin.b2b.packages.delete',
            'requestsRoute' => 'admin.b2b.package-requests.index',
            'approveRoute' => 'admin.b2b.package-requests.approve',
            'rejectRoute' => 'admin.b2b.package-requests.reject',
            'requestsButtonLabel' => translate('Membership Package Requests'),
            'forceSupplierFeaturedPackage' => false,
        ];
    }

    protected function getCompany(): B2BCompany
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless($company, 403);

        return $company;
    }
}
