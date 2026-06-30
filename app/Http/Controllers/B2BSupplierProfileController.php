<?php

namespace App\Http\Controllers;

use App\Models\B2BCompanyCatalog;
use App\Models\B2BCompanyCertification;
use App\Models\Category;
use App\Models\Product;
use App\Services\B2BCompanyService;
use App\Services\B2BPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class B2BSupplierProfileController extends Controller
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPermissionService $b2bPermissionService
    ) {
    }

    public function edit()
    {
        $company = $this->getManageableSupplierCompany();

        return view('seller.b2b.company.public_profile', [
            'company' => $company,
            'categories' => Category::where('parent_id', 0)->orderBy('name')->get(),
            'certifications' => $company->certifications()->latest()->get(),
            'catalogs' => $company->catalogs()->with(['coverUpload', 'pdfUpload'])->latest()->get(),
        ]);
    }

    public function catalogs()
    {
        $company = $this->getManageableSupplierCompany();

        return view('seller.b2b.company.catalogs', [
            'company' => $company,
            'catalogs' => $company->catalogs()->with(['coverUpload', 'pdfUpload'])->latest()->get(),
        ]);
    }

    public function update(Request $request)
    {
        $company = $this->getManageableSupplierCompany();

        $data = $request->validate([
            'year_established' => 'nullable|integer|min:1900|max:' . now()->year,
            'employee_count' => 'nullable|string|max:255',
            'annual_revenue' => 'nullable|string|max:255',
            'main_markets' => 'nullable|string',
            'business_scope' => 'nullable|string',
            'production_capacity' => 'nullable|string',
            'export_percentage' => 'nullable|numeric|min:0|max:100',
            'factory_size' => 'nullable|string|max:255',
            'factory_location' => 'nullable|string|max:255',
            'quality_control' => 'nullable|string',
            'lead_time_summary' => 'nullable|string|max:255',
            'response_rate' => 'nullable|numeric|min:0|max:100',
            'response_time_hours' => 'nullable|integer|min:0',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $data['public_profile_enabled'] = $company->verification_status === 'approved'
            ? $request->boolean('public_profile_enabled')
            : false;

        $company->update($data);
        $company->categories()->sync($data['category_ids'] ?? []);

        flash(translate('Supplier public profile updated successfully.'))->success();

        return back();
    }

    public function storeCertification(Request $request)
    {
        $company = $this->getManageableSupplierCompany();

        $data = $this->validateCertification($request);
        $data['b2b_company_id'] = $company->id;
        $data['file'] = $this->storeFile($request, 'file');
        $data['verification_status'] = 'pending';
        $data['verified_by'] = null;
        $data['verified_at'] = null;

        B2BCompanyCertification::create($data);

        flash(translate('Certification added successfully.'))->success();

        return back();
    }

    public function updateCertification(Request $request, $id)
    {
        $company = $this->getManageableSupplierCompany();
        $certification = $company->certifications()->findOrFail($id);

        $data = $this->validateCertification($request);
        $data['file'] = $this->storeFile($request, 'file', $certification->file);
        $data['verification_status'] = 'pending';
        $data['verified_by'] = null;
        $data['verified_at'] = null;

        $certification->update($data);

        flash(translate('Certification updated successfully.'))->success();

        return back();
    }

    public function deleteCertification($id)
    {
        $company = $this->getManageableSupplierCompany();
        $certification = $company->certifications()->findOrFail($id);

        if ($certification->file && File::exists(public_path($certification->file))) {
            File::delete(public_path($certification->file));
        }

        $certification->delete();

        flash(translate('Certification deleted successfully.'))->success();

        return back();
    }

    public function storeCatalog(Request $request)
    {
        $company = $this->getManageableSupplierCompany();
        $data = $this->validateCatalog($request);

        $company->catalogs()->create([
            'title' => $data['title'],
            'slug' => $this->generateCatalogSlug($company->id, $data['title']),
            'description' => $data['description'] ?? null,
            'cover_image' => $data['cover_image'] ?? null,
            'pdf_file' => $data['pdf_file'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
        ]);

        flash(translate('Catalog created successfully.'))->success();

        return back();
    }

    public function updateCatalog(Request $request, $id)
    {
        $company = $this->getManageableSupplierCompany();
        $catalog = $company->catalogs()->findOrFail($id);
        $data = $this->validateCatalog($request);

        $catalog->update([
            'title' => $data['title'],
            'slug' => $this->generateCatalogSlug($company->id, $data['title'], $catalog->id),
            'description' => $data['description'] ?? null,
            'cover_image' => $data['cover_image'] ?? null,
            'pdf_file' => $data['pdf_file'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        flash(translate('Catalog updated successfully.'))->success();

        return back();
    }

    public function deleteCatalog($id)
    {
        $company = $this->getManageableSupplierCompany();
        $catalog = $company->catalogs()->findOrFail($id);

        Product::where('b2b_company_catalog_id', $catalog->id)->update(['b2b_company_catalog_id' => null]);
        $catalog->delete();

        flash(translate('Catalog deleted successfully.'))->success();

        return back();
    }

    protected function getManageableSupplierCompany()
    {
        $company = $this->b2bCompanyService->getCompanyByUser(Auth::id());

        abort_unless(
            $company &&
            $company->isSupplierSide() &&
            $this->b2bPermissionService->canManageSupplierProfile(Auth::id(), $company->id),
            403
        );

        return $company;
    }

    protected function validateCertification(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'issuing_authority' => 'nullable|string|max:255',
            'certificate_number' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
    }

    protected function validateCatalog(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|integer|exists:uploads,id',
            'pdf_file' => 'nullable|integer|exists:uploads,id',
            'is_active' => 'nullable|boolean',
        ]);
    }

    protected function generateCatalogSlug(int $companyId, string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'catalog';
        $slug = $baseSlug;
        $counter = 2;

        while (
            B2BCompanyCatalog::query()
                ->where('b2b_company_id', $companyId)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function storeFile(Request $request, string $field, ?string $oldFile = null): ?string
    {
        if (!$request->hasFile($field)) {
            return $oldFile;
        }

        $directory = public_path('uploads/b2b_certifications');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if ($oldFile && File::exists(public_path($oldFile))) {
            File::delete(public_path($oldFile));
        }

        $file = $request->file($field);
        $fileName = time() . '_' . uniqid('cert_') . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $fileName);

        return 'uploads/b2b_certifications/' . $fileName;
    }
}
