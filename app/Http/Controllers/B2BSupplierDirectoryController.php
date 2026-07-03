<?php

namespace App\Http\Controllers;

use App\Models\B2BCompany;
use App\Models\Category;
use App\Services\Fraud\FraudRestrictionService;
use Illuminate\Http\Request;

class B2BSupplierDirectoryController extends Controller
{
    public function __construct(
        protected FraudRestrictionService $fraudRestrictionService
    ) {
    }

    public function index(Request $request)
    {
        $requestCountry = $request->country ?: selected_delivery_country_name();

        $suppliers = B2BCompany::with(['categories', 'certifications', 'wholesaleProducts.thumbnail', 'b2bPackage'])
            ->publicSuppliers()
            ->when($request->keyword, function ($query, $keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('company_name', 'like', '%' . $keyword . '%')
                        ->orWhere('country', 'like', '%' . $keyword . '%')
                        ->orWhere('city', 'like', '%' . $keyword . '%')
                        ->orWhere('business_scope', 'like', '%' . $keyword . '%');
                });
            })
            ->when($requestCountry, fn ($query, $country) => $query->where('country', $country))
            ->when($request->company_type, fn ($query, $companyType) => $query->where('company_type', $companyType))
            ->when($request->category, function ($query, $categoryId) {
                $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->where('categories.id', $categoryId));
            })
            ->when($request->boolean('verified_supplier_badge'), fn ($query) => $query->where('verified_supplier_badge', true))
            ->when($request->boolean('featured_supplier'), fn ($query) => $query->homepageFeaturedSuppliers());

        $sort = $request->get('sort', 'featured');
        $suppliers = match ($sort) {
            'newest' => $suppliers->latest(),
            'response_rate' => $suppliers->orderByDesc('response_rate')->orderByDesc('profile_score'),
            'profile_score' => $suppliers->orderByDesc('profile_score')->orderByDesc('featured_supplier'),
            default => $suppliers->orderByDesc('featured_supplier')->orderByDesc('verified_supplier_badge')->orderByDesc('profile_score')->latest(),
        };

        return view('b2b.suppliers.index', [
            'suppliers' => $suppliers->paginate(12)->appends($request->query()),
            'countries' => B2BCompany::publicSuppliers()->whereNotNull('country')->distinct()->orderBy('country')->pluck('country'),
            'categories' => Category::where('parent_id', 0)->orderBy('name')->get(),
            'sort' => $sort,
        ]);
    }

    public function show(string $slug)
    {
        $supplier = B2BCompany::with([
            'categories',
            'certifications' => fn ($query) => $query->where('verification_status', 'approved')->latest(),
            'catalogs' => fn ($query) => $query->where('is_active', true)->with(['coverUpload', 'pdfUpload'])->latest(),
            'wholesaleProducts.thumbnail',
            'user.shop',
            'b2bPackage',
            'reviewsReceived.reviewerCompany',
        ])
            ->publicSuppliers()
            ->where('public_slug', $slug)
            ->firstOrFail();

        if (!supplier_matches_selected_delivery_country($supplier)) {
            abort(404);
        }

        $trustStatus = $this->fraudRestrictionService->userFacingStatus($supplier->user?->latestFraudCheck);

        return view('b2b.suppliers.show', compact('supplier', 'trustStatus'));
    }
}
