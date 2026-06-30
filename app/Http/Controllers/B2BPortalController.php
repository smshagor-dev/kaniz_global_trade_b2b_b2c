<?php

namespace App\Http\Controllers;

use App\Jobs\RunFraudCheckJob;
use App\Models\B2BCompany;
use App\Models\B2BVerificationRequirement;
use App\Models\Shop;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Services\B2BCompanyService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class B2BPortalController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService
    ) {
    }

    public function becomeSupplier()
    {
        if (!Auth::check()) {
            return $this->registrationView('supplier');
        }

        return $this->handlePortalEntry('supplier');
    }

    public function buyerPortal()
    {
        if (!Auth::check()) {
            return $this->registrationView('buyer');
        }

        return $this->handlePortalEntry('buyer');
    }

    public function supplierPortal()
    {
        if (!Auth::check()) {
            return $this->registrationView('supplier');
        }

        return $this->handlePortalEntry('supplier');
    }

    public function registerSupplier(Request $request)
    {
        return $this->registerPortalUser($request, 'supplier');
    }

    public function registerBuyer(Request $request)
    {
        return $this->registerPortalUser($request, 'buyer');
    }

    public function buyerOnboarding()
    {
        return $this->renderOnboarding('buyer');
    }

    public function supplierOnboarding()
    {
        return $this->renderOnboarding('supplier');
    }

    public function status(string $portal)
    {
        abort_unless(in_array($portal, ['buyer', 'supplier'], true), 404);

        if (!Auth::check()) {
            return redirect()->guest(route('user.login'));
        }

        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());
        $switchableCompanies = $this->b2bCompanyService->getSwitchableCompaniesByUser(Auth::id());

        return view('b2b.company.portal_status', [
            'portal' => $portal,
            'company' => $company,
            'switchableCompanies' => $switchableCompanies,
            'supportsPortal' => $company ? $this->supportsPortal($company, $portal) : false,
            'hasActivePackage' => $company ? $this->hasActivePackageForPortal($company, $portal) : false,
        ]);
    }

    protected function handlePortalEntry(string $portal)
    {
        if (!Auth::check()) {
            return $this->registrationView($portal);
        }

        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        if (!$company) {
            return redirect()->route($portal . '.onboarding');
        }

        if ($this->isApprovedForPortal($company, $portal) && $this->hasActivePackageForPortal($company, $portal)) {
            return redirect()->route($portal . '.dashboard');
        }

        if ($this->isApprovedForPortal($company, $portal)) {
            return redirect()->route('b2b.packages.index');
        }

        if ($this->supportsPortal($company, $portal)) {
            return redirect()->route('b2b.portal.status', ['portal' => $portal]);
        }

        return redirect()->route($portal . '.onboarding');
    }

    protected function resolveOnboardingCompany(string $portal): array
    {
        $company = $this->b2bCompanyService->getOwnedCompanyByUser(Auth::id());

        if (!$company) {
            return [null, 'create'];
        }

        if ($this->isApprovedForPortal($company, $portal) && $this->hasActivePackageForPortal($company, $portal)) {
            return [$company, 'dashboard'];
        }

        if ($this->isApprovedForPortal($company, $portal)) {
            return [$company, 'packages'];
        }

        if ($this->supportsPortal($company, $portal)) {
            return [$company, 'edit'];
        }

        return [$company, 'status'];
    }

    protected function supportsPortal(B2BCompany $company, string $portal): bool
    {
        return $portal === 'supplier'
            ? $company->isSupplierSide()
            : $company->isBuyerSide();
    }

    protected function isApprovedForPortal(B2BCompany $company, string $portal): bool
    {
        return $company->verification_status === 'approved' && $this->supportsPortal($company, $portal);
    }

    protected function hasActivePackageForPortal(B2BCompany $company, string $portal): bool
    {
        return $portal === 'supplier'
            ? $this->b2bCompanyService->hasActiveSupplierPackage($company->user_id, $company->id)
            : $this->b2bCompanyService->hasActiveBuyerPackage($company->user_id, $company->id);
    }

    protected function renderOnboarding(string $portal)
    {
        if (!Auth::check()) {
            return redirect()->guest(route('user.login'));
        }

        [$company, $mode] = $this->resolveOnboardingCompany($portal);

        if ($mode === 'dashboard') {
            return redirect()->route($portal . '.dashboard');
        }

        if ($mode === 'packages') {
            return redirect()->route('b2b.packages.index');
        }

        if ($mode === 'status') {
            return redirect()->route('b2b.portal.status', ['portal' => $portal]);
        }

        $lockedCompanyType = old('company_type', $company?->company_type ?? $portal);
        $verificationRequirements = $this->verificationRequirementsFor($lockedCompanyType);

        return view('b2b.company.onboarding', [
            'portal' => $portal,
            'company' => $company,
            'mode' => $company ? 'edit' : 'create',
            'verificationRequirements' => $verificationRequirements,
            'lockedCompanyType' => $lockedCompanyType,
            'allowedCompanyTypes' => $portal === 'supplier'
                ? ['supplier', 'manufacturer', 'wholesaler', 'distributor', 'exporter']
                : ['buyer', 'retailer', 'importer'],
            'portalRedirectRoute' => $portal . '.dashboard',
        ]);
    }

    protected function verificationRequirementsFor(?string $companyType): Collection
    {
        return B2BVerificationRequirement::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (B2BVerificationRequirement $requirement) => $requirement->appliesTo($companyType))
            ->values();
    }

    protected function registrationView(string $portal)
    {
        $companyType = $portal === 'supplier' ? 'supplier' : 'buyer';

        return view('frontend.b2b.register', [
            'portal' => $portal,
            'action' => $portal === 'supplier'
                ? route('b2b.portal.become-supplier.register')
                : route('buyer.portal.register'),
            'companyType' => $companyType,
            'companyTypeLabel' => ucfirst($companyType),
        ]);
    }

    protected function registerPortalUser(Request $request, string $portal)
    {
        if (Auth::check()) {
            return redirect()->route($portal === 'supplier' ? 'supplier.onboarding' : 'buyer.onboarding');
        }

        $companyType = $portal === 'supplier' ? 'supplier' : 'buyer';

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:50|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'company_name' => 'required|string|max:255',
            'company_type' => 'required|in:' . $companyType,
            'business_email' => 'required|email|max:255',
            'country' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'trade_license_file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'tax_document_file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        DB::transaction(function () use ($data, $portal, &$user, &$company) {
            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->phone = $data['phone'];
            $user->user_type = $portal === 'supplier' ? 'seller' : 'customer';
            $user->password = Hash::make($data['password']);
            $user->email_verified_at = now();
            $user->remember_token = Str::random(10);
            $user->banned = 0;
            $user->save();

            if ($portal === 'supplier') {
                $shop = new Shop();
                $shop->user_id = $user->id;
                $shop->name = $data['company_name'];
                $shop->address = $data['address'] ?? null;
                $shop->registration_approval = 0;
                $shop->slug = Str::slug($data['company_name'] . '-' . $user->id);
                $shop->save();
            }

            $company = B2BCompany::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'company_type' => $data['company_type'],
                'country' => $data['country'],
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'website' => $data['website'] ?? null,
                'phone' => $data['phone'],
                'business_email' => $data['business_email'],
                'logo' => $this->storeRegistrationFile(request(), 'logo'),
                'trade_license_file' => $this->storeRegistrationFile(request(), 'trade_license_file'),
                'tax_document_file' => $this->storeRegistrationFile(request(), 'tax_document_file'),
                'verification_status' => 'pending',
            ]);

            $this->syncFraudDocumentsFromCompany($user, $company);
        });

        Auth::login($user, true);
        $this->b2bCompanyService->setActiveCompanyForUser($user->id, $company->id);
        RunFraudCheckJob::dispatch($user->id, [
            'event_type' => 'registration',
            'reason' => 'Fraud check triggered after B2B registration.',
        ]);

        flash(translate('Your B2B registration has been submitted successfully.'))->success();

        return redirect()->route($portal === 'supplier' ? 'supplier.portal' : 'buyer.portal');
    }

    protected function storeRegistrationFile(Request $request, string $field): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        $directory = public_path('uploads/b2b_companies');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file = $request->file($field);
        $fileName = time() . '_' . $field . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $fileName);

        return 'uploads/b2b_companies/' . $fileName;
    }

    protected function syncFraudDocumentsFromCompany(User $user, B2BCompany $company): void
    {
        $map = [
            'trade_license_file' => 'business_license',
            'tax_document_file' => 'tax_certificate',
        ];

        foreach ($map as $field => $documentType) {
            if (!$company->{$field}) {
                continue;
            }

            VerificationDocument::query()->create([
                'user_id' => $user->id,
                'user_type' => $user->user_type === 'seller' ? 'supplier' : 'buyer',
                'document_type' => $documentType,
                'file_path' => $company->{$field},
                'original_name' => basename($company->{$field}),
                'mime_type' => pathinfo($company->{$field}, PATHINFO_EXTENSION),
                'status' => 'pending',
            ]);
        }
    }
}
