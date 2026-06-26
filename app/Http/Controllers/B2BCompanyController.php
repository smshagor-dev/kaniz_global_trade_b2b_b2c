<?php

namespace App\Http\Controllers;

use App\Models\B2BCompany;
use App\Models\B2BCompanyCertification;
use App\Models\B2BCompanyVerificationSubmission;
use App\Models\B2BVerificationRequirement;
use App\Models\User;
use App\Services\B2BCompanyService;
use App\Services\B2BPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class B2BCompanyController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPermissionService $b2bPermissionService
    )
    {
    }

    public function create()
    {
        $company = $this->b2bCompanyService->getOwnedCompanyByUser(Auth::id());

        if ($company) {
            flash(translate('You already have a B2B company profile.'))->warning();
            return redirect()->route('b2b.company.show');
        }

        $verificationRequirements = $this->activeVerificationRequirements();

        return view('b2b.company.create', compact('verificationRequirements'));
    }

    public function store(Request $request)
    {
        $existingCompany = $this->b2bCompanyService->getOwnedCompanyByUser(Auth::id());

        if ($existingCompany) {
            flash(translate('You already have a B2B company profile.'))->warning();
            return redirect()->route('b2b.company.edit');
        }

        $data = $this->validatedData($request);
        $data['user_id'] = Auth::id();
        $data['verification_status'] = 'pending';
        $data['logo'] = $this->storeFile($request, 'logo');
        $data['trade_license_file'] = $this->storeFile($request, 'trade_license_file');
        $data['tax_document_file'] = $this->storeFile($request, 'tax_document_file');
        $data['bank_check_file'] = $this->storeFile($request, 'bank_check_file');

        $company = B2BCompany::create($data);
        $this->syncVerificationSubmissions($request, $company);

        flash(translate('Your B2B company profile has been submitted successfully.'))->success();
        return redirect()->route('b2b.company.show');
    }

    public function edit()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        if (!$company) {
            flash(translate('Please create your B2B company profile first.'))->warning();
            return redirect()->route('b2b.company.create');
        }

        abort_unless($this->b2bPermissionService->canManageCompany(Auth::id(), $company->id), 403);

        $verificationRequirements = $this->activeVerificationRequirements($company->company_type);

        return view('b2b.company.edit', compact('company', 'verificationRequirements'));
    }

    public function update(Request $request)
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        if (!$company) {
            flash(translate('Please create your B2B company profile first.'))->warning();
            return redirect()->route('b2b.company.create');
        }

        abort_unless($this->b2bPermissionService->canManageCompany(Auth::id(), $company->id), 403);

        $data = $this->validatedData($request, $company);
        $data['logo'] = $this->storeFile($request, 'logo', $company->logo);
        $data['trade_license_file'] = $this->storeFile($request, 'trade_license_file', $company->trade_license_file);
        $data['tax_document_file'] = $this->storeFile($request, 'tax_document_file', $company->tax_document_file);
        $data['bank_check_file'] = $this->storeFile($request, 'bank_check_file', $company->bank_check_file);

        if (in_array($company->verification_status, ['pending', 'rejected'])) {
            $data['verification_status'] = 'pending';
            $data['verification_note'] = null;
            $data['verified_at'] = null;
            $data['verified_by'] = null;
        }

        $company->update($data);
        $this->syncVerificationSubmissions($request, $company);

        flash(translate('Your B2B company profile has been updated successfully.'))->success();
        return redirect()->route('b2b.company.show');
    }

    public function show()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        if (!$company) {
            flash(translate('Please create your B2B company profile first.'))->warning();
            return redirect()->route('b2b.company.create');
        }

        abort_unless($this->b2bPermissionService->canAccessCompany(Auth::id(), $company->id), 403);

        $canManageCompany = $this->b2bPermissionService->canManageCompany(Auth::id(), $company->id);
        $canInviteMembers = $this->b2bPermissionService->canInviteMembers(Auth::id(), $company->id);
        $availableCompanies = $this->b2bCompanyService->getAvailableCompaniesByUser(Auth::id());
        $canManageSupplierProfile = $company->isSupplierSide() && $this->b2bPermissionService->canManageSupplierProfile(Auth::id(), $company->id);

        return view('b2b.company.show', compact('company', 'canManageCompany', 'canInviteMembers', 'availableCompanies', 'canManageSupplierProfile'));
    }

    public function switchActiveCompany(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|integer|exists:b2b_companies,id',
        ]);

        abort_unless($this->b2bCompanyService->setActiveCompanyForUser(Auth::id(), (int) $data['company_id']), 403);

        flash(translate('Active B2B company switched successfully.'))->success();

        return back();
    }

    public function adminIndex(Request $request)
    {
        $companies = B2BCompany::with(['user', 'verifier'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('company_name', 'like', '%' . $search . '%')
                        ->orWhere('legal_name', 'like', '%' . $search . '%')
                        ->orWhere('business_email', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($request->verification_status, fn ($query, $status) => $query->where('verification_status', $status))
            ->when($request->company_type, fn ($query, $type) => $query->where('company_type', $type))
            ->when($request->premium_verified === '1', fn ($query) => $query->where('premium_verified', true))
            ->when($request->premium_verified === '0', fn ($query) => $query->where('premium_verified', false))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->appends($request->query());

        return view('backend.b2b.companies.index', compact('companies'));
    }

    public function adminCreate()
    {
        $users = User::query()
            ->whereNotIn('id', B2BCompany::query()->select('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $verificationRequirements = $this->activeVerificationRequirements();

        return view('backend.b2b.companies.create', compact('users', 'verificationRequirements'));
    }

    public function adminStore(Request $request)
    {
        $data = $this->validatedData($request);
        $data['user_id'] = (int) $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', Rule::unique('b2b_companies', 'user_id')],
        ])['user_id'];
        $data['verification_status'] = 'approved';
        $data['verified_at'] = now();
        $data['verified_by'] = Auth::id();
        $data['logo'] = $this->storeFile($request, 'logo');
        $data['trade_license_file'] = $this->storeFile($request, 'trade_license_file');
        $data['tax_document_file'] = $this->storeFile($request, 'tax_document_file');

        $company = B2BCompany::create($data);
        $this->syncVerificationSubmissions($request, $company);

        flash(translate('B2B company created successfully.'))->success();

        return redirect()->route('admin.b2b.companies.index');
    }

    public function adminShow($id)
    {
        $company = B2BCompany::with([
            'user',
            'verifier',
            'members.user',
            'members.inviter',
            'invitations.inviter',
            'categories',
            'certifications.verifier',
            'b2bPackage',
            'verificationSubmissions.requirement',
            'wholesaleProducts.thumbnail',
        ])->findOrFail($id);

        return view('backend.b2b.companies.show', compact('company'));
    }

    public function adminVerificationIndex(Request $request)
    {
        $verificationStatus = $request->get('verification_status', 'pending');

        $companies = B2BCompany::with(['user', 'verifier', 'verificationSubmissions.requirement'])
            ->withCount([
                'certifications',
                'certifications as pending_certifications_count' => fn ($query) => $query->where('verification_status', 'pending'),
                'verificationSubmissions as verification_submissions_count',
            ])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('company_name', 'like', '%' . $search . '%')
                        ->orWhere('legal_name', 'like', '%' . $search . '%')
                        ->orWhere('business_email', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->where('verification_status', $verificationStatus)
            ->when($request->premium_verified === '1', fn ($query) => $query->where('premium_verified', true))
            ->when($request->premium_verified === '0', fn ($query) => $query->where('premium_verified', false))
            ->orderByRaw("FIELD(verification_status, 'pending', 'rejected', 'approved')")
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'companies_page')
            ->appends($request->query());

        $companyStats = [
            'pending' => B2BCompany::where('verification_status', 'pending')->count(),
            'approved' => B2BCompany::where('verification_status', 'approved')->count(),
            'rejected' => B2BCompany::where('verification_status', 'rejected')->count(),
            'premium_verified' => B2BCompany::where('premium_verified', true)->count(),
        ];

        $certificationStats = [
            'pending' => B2BCompanyCertification::where('verification_status', 'pending')->count(),
            'approved' => B2BCompanyCertification::where('verification_status', 'approved')->count(),
            'rejected' => B2BCompanyCertification::where('verification_status', 'rejected')->count(),
        ];

        $requirements = $this->activeVerificationRequirements();

        return view('backend.b2b.companies.verification', compact('companies', 'companyStats', 'certificationStats', 'requirements', 'verificationStatus'));
    }

    public function adminVerificationShow($id)
    {
        $company = B2BCompany::with([
            'user',
            'verifier',
            'certifications.verifier',
            'verificationSubmissions.requirement',
        ])->findOrFail($id);

        $requirements = $this->activeVerificationRequirements($company->company_type);

        return view('backend.b2b.companies.verification_show', compact('company', 'requirements'));
    }

    public function approve($id)
    {
        $company = B2BCompany::findOrFail($id);
        $company->verification_status = 'approved';
        $company->verification_note = null;
        $company->verified_at = now();
        $company->verified_by = Auth::id();
        $company->save();

        flash(translate('B2B company profile approved successfully.'))->success();
        return back();
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'verification_note' => 'required|string|max:1000',
        ]);

        $company = B2BCompany::findOrFail($id);
        $company->verification_status = 'rejected';
        $company->verification_note = $request->verification_note;
        $company->verified_at = now();
        $company->verified_by = Auth::id();
        $company->save();

        flash(translate('B2B company profile rejected successfully.'))->success();
        return back();
    }

    public function updateSupplierControls(Request $request, $id)
    {
        $company = B2BCompany::findOrFail($id);

        $data = $request->validate([
            'verified_supplier_badge' => 'nullable|boolean',
            'featured_supplier' => 'nullable|boolean',
            'profile_score' => 'required|integer|min:0|max:100',
        ]);

        $company->update([
            'verified_supplier_badge' => $request->boolean('verified_supplier_badge'),
            'featured_supplier' => $request->boolean('featured_supplier'),
            'profile_score' => $data['profile_score'],
        ]);

        flash(translate('Supplier controls updated successfully.'))->success();

        return back();
    }

    public function approveCertification($id)
    {
        $certification = B2BCompanyCertification::findOrFail($id);
        $certification->update([
            'verification_status' => 'approved',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        flash(translate('Certification approved successfully.'))->success();

        return back();
    }

    public function rejectCertification($id)
    {
        $certification = B2BCompanyCertification::findOrFail($id);
        $certification->update([
            'verification_status' => 'rejected',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        flash(translate('Certification rejected successfully.'))->success();

        return back();
    }

    protected function validatedData(Request $request, ?B2BCompany $company = null): array
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_type' => ['required', Rule::in(['buyer', 'supplier', 'manufacturer', 'distributor', 'wholesaler', 'retailer'])],
            'legal_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'country' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'phone' => 'required|string|max:50',
            'business_email' => 'required|email|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'trade_license_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'tax_document_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_branch_name' => 'nullable|string|max:255',
            'bank_branch_address' => 'nullable|string|max:500',
            'bank_country' => 'nullable|string|max:100',
            'swift_code' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:100',
            'bank_check_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'requirement_text' => 'nullable|array',
            'requirement_file' => 'nullable|array',
        ]);

        $requirements = $this->activeVerificationRequirements($data['company_type'] ?? null);
        $dynamicRules = [];

        foreach ($requirements as $requirement) {
            $key = $requirement->id;
            $existingSubmission = $company?->verificationSubmissions()->where('b2b_verification_requirement_id', $requirement->id)->first();

            if ($requirement->field_type === 'file') {
                $dynamicRules["requirement_file.$key"] = (($requirement->is_required && !$existingSubmission?->value_file) ? 'required' : 'nullable') . '|file|mimes:pdf,jpg,jpeg,png|max:5120';
                continue;
            }

            $baseRule = $requirement->is_required ? 'required' : 'nullable';
            $extraRule = match ($requirement->field_type) {
                'email' => 'email',
                'url' => 'url',
                'number' => 'numeric',
                'date' => 'date',
                default => 'string|max:2000',
            };

            $dynamicRules["requirement_text.$key"] = $baseRule . '|' . $extraRule;
        }

        if (!empty($dynamicRules)) {
            $request->validate($dynamicRules);
        }

        return $data;
    }

    protected function activeVerificationRequirements(?string $companyType = null)
    {
        return B2BVerificationRequirement::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (B2BVerificationRequirement $requirement) => $requirement->appliesTo($companyType))
            ->values();
    }

    protected function syncVerificationSubmissions(Request $request, B2BCompany $company): void
    {
        $requirements = $this->activeVerificationRequirements($company->company_type);
        $textValues = $request->input('requirement_text', []);

        foreach ($requirements as $requirement) {
            $submission = B2BCompanyVerificationSubmission::firstOrNew([
                'b2b_company_id' => $company->id,
                'b2b_verification_requirement_id' => $requirement->id,
            ]);

            if ($requirement->field_type === 'file') {
                $submission->value_text = null;
                $submission->value_file = $this->storeFile(
                    $request,
                    "requirement_file.$requirement->id",
                    $submission->value_file
                );
            } else {
                if ($submission->value_file && File::exists(public_path($submission->value_file))) {
                    File::delete(public_path($submission->value_file));
                }
                $submission->value_file = null;
                $submission->value_text = $textValues[$requirement->id] ?? null;
            }

            if ($submission->value_text || $submission->value_file) {
                $submission->save();
            } elseif ($submission->exists) {
                $submission->delete();
            }
        }
    }

    protected function storeFile(Request $request, string $field, ?string $oldFile = null): ?string
    {
        if (!$request->hasFile($field)) {
            return $oldFile;
        }

        $directory = public_path('uploads/b2b_companies');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if ($oldFile && File::exists(public_path($oldFile))) {
            File::delete(public_path($oldFile));
        }

        $file = $request->file($field);
        $fileName = time() . '_' . $field . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $fileName);

        return 'uploads/b2b_companies/' . $fileName;
    }
}
