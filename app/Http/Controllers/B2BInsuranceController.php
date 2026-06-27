<?php

namespace App\Http\Controllers;

use App\Models\B2BInsuranceClaim;
use App\Models\B2BInsurancePayment;
use App\Models\B2BInsurancePolicy;
use App\Models\B2BInsuranceProvider;
use App\Models\B2BInsuranceQuote;
use App\Services\B2BCompanyService;
use App\Services\B2BGlobalConfigService;
use App\Services\B2BInsuranceService;
use App\Services\B2BPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BInsuranceController extends Controller
{
    public function __construct(
        protected B2BCompanyService $companyService,
        protected B2BPermissionService $permissionService,
        protected B2BInsuranceService $insuranceService,
        protected B2BGlobalConfigService $globalConfigService
    ) {
    }

    public function buyerDashboard()
    {
        $this->ensureInsuranceEnabled();
        $company = $this->getCompany('buyer');
        $payload = [
            'stats' => $this->insuranceService->companyDashboard($company, 'buyer'),
            'policies' => B2BInsurancePolicy::where('buyer_company_id', $company->id)->latest()->paginate(10),
            'claims' => B2BInsuranceClaim::where('buyer_company_id', $company->id)->latest()->paginate(10),
            'quotes' => B2BInsuranceQuote::where('buyer_company_id', $company->id)->latest()->paginate(10),
        ];

        if (!request()->expectsJson()) {
            return view('b2b.insurance.dashboard', array_merge($payload, [
                'company' => $company,
                'panelRole' => 'buyer',
            ]));
        }

        return response()->json($payload);
    }

    public function supplierDashboard()
    {
        $this->ensureInsuranceEnabled();
        $company = $this->getCompany('supplier');
        $payload = [
            'stats' => $this->insuranceService->companyDashboard($company, 'supplier'),
            'policies' => B2BInsurancePolicy::where('supplier_company_id', $company->id)->latest()->paginate(10),
            'claims' => B2BInsuranceClaim::where('supplier_company_id', $company->id)->latest()->paginate(10),
            'quotes' => B2BInsuranceQuote::where('supplier_company_id', $company->id)->latest()->paginate(10),
        ];

        if (!request()->expectsJson()) {
            return view('b2b.insurance.dashboard', array_merge($payload, [
                'company' => $company,
                'panelRole' => 'supplier',
            ]));
        }

        return response()->json($payload);
    }

    public function adminDashboard()
    {
        $this->ensureInsuranceEnabled();
        $payload = [
            'stats' => $this->insuranceService->adminDashboard(),
            'providers' => B2BInsuranceProvider::latest()->paginate(10),
            'claims' => B2BInsuranceClaim::latest()->paginate(10),
            'policies' => B2BInsurancePolicy::latest()->paginate(10),
        ];

        if (!request()->expectsJson()) {
            return view('backend.b2b.insurance.dashboard', $payload);
        }

        return response()->json($payload);
    }

    public function storeProvider(Request $request)
    {
        $this->ensureInsuranceEnabled();
        $provider = $this->insuranceService->createProvider($this->validatedProvider($request), Auth::id());

        return response()->json(['data' => $this->providerResponse($provider)], 201);
    }

    public function updateProvider(Request $request, $providerId)
    {
        $this->ensureInsuranceEnabled();
        $provider = B2BInsuranceProvider::findOrFail($providerId);
        $updated = $this->insuranceService->updateProvider($provider, $this->validatedProvider($request, false), Auth::id());

        return response()->json(['data' => $this->providerResponse($updated)]);
    }

    public function requestQuote(Request $request)
    {
        $this->ensureInsuranceEnabled();
        $company = $this->companyService->getCompanyByUser(Auth::id());
        abort_unless($company && $this->permissionService->canManageInsurance(Auth::id(), $company->id), 403);

        $quote = $this->insuranceService->generateQuote($this->validatedQuote($request, $company), Auth::user(), $company);

        return response()->json(['data' => $quote], 201);
    }

    public function issuePolicy(Request $request, $quoteId)
    {
        $this->ensureInsuranceEnabled();
        $quote = B2BInsuranceQuote::findOrFail($quoteId);
        $companyId = $quote->buyer_company_id ?: $quote->supplier_company_id;
        abort_unless(auth()->user()->user_type === 'admin' || $this->permissionService->canManageInsurance(Auth::id(), $companyId), 403);

        $policy = $this->insuranceService->issuePolicy($quote, $request->validate([
            'policy_holder_user_id' => 'nullable|integer',
            'coverage_plan' => 'nullable|string|max:255',
            'status' => 'nullable|in:' . implode(',', B2BInsurancePolicy::STATUSES),
            'deductible_amount' => 'nullable|numeric|min:0',
            'coverage_start' => 'nullable|date',
            'coverage_end' => 'nullable|date|after_or_equal:coverage_start',
            'attachment_paths' => 'nullable|array',
            'attachment_paths.*' => 'string',
            'coverage_details' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]), Auth::user(), $companyId);

        return response()->json(['data' => $policy], 201);
    }

    public function submitClaim(Request $request, $policyId)
    {
        $this->ensureInsuranceEnabled();
        $policy = B2BInsurancePolicy::findOrFail($policyId);
        $company = $this->companyService->getCompanyByUser(Auth::id());
        abort_unless($company && in_array($company->id, array_filter([$policy->buyer_company_id, $policy->supplier_company_id]), true), 403);
        abort_unless($this->permissionService->canSubmitInsuranceClaim(Auth::id(), $company->id), 403);

        $claim = $this->insuranceService->submitClaim($policy, $request->validate([
            'claim_type' => 'required|string|max:80',
            'incident_country' => 'nullable|string|max:120',
            'incident_location' => 'nullable|string|max:255',
            'incident_reference' => 'nullable|string|max:255',
            'summary' => 'required|string|max:255',
            'description' => 'nullable|string',
            'claim_amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:20',
            'incident_at' => 'nullable|date',
            'evidence' => 'nullable|array',
            'documents' => 'nullable|array',
            'documents.*.document_type' => 'required|string|max:80',
            'documents.*.title' => 'nullable|string|max:255',
            'documents.*.file_path' => 'required|string|max:255',
            'documents.*.mime_type' => 'nullable|string|max:120',
            'documents.*.file_size' => 'nullable|integer|min:0',
            'documents.*.metadata' => 'nullable|array',
        ]), Auth::user(), $company->id);

        return response()->json(['data' => $claim], 201);
    }

    public function updateClaimStatus(Request $request, $claimId)
    {
        $this->ensureInsuranceEnabled();
        $claim = B2BInsuranceClaim::findOrFail($claimId);
        $companyId = $claim->buyer_company_id ?: $claim->supplier_company_id;
        abort_unless(auth()->user()->user_type === 'admin' || $this->permissionService->canManageInsurance(Auth::id(), $companyId), 403);

        $updated = $this->insuranceService->transitionClaim($claim, $request->validate([
            'status' => 'required|in:' . implode(',', B2BInsuranceClaim::STATUSES),
            'approved_amount' => 'nullable|numeric|min:0',
            'settled_amount' => 'nullable|numeric|min:0',
            'comment' => 'nullable|string',
            'notes' => 'nullable|string',
            'resolution_data' => 'nullable|array',
        ])['status'], $request->only(['approved_amount', 'settled_amount', 'comment', 'notes', 'resolution_data']), Auth::user(), $companyId);

        return response()->json(['data' => $updated]);
    }

    public function recordPayment(Request $request)
    {
        $this->ensureInsuranceEnabled();
        abort_unless(auth()->user()->user_type === 'admin', 403);

        $payment = $this->insuranceService->recordPayment($request->validate([
            'policy_id' => 'nullable|integer',
            'claim_id' => 'nullable|integer',
            'provider_id' => 'nullable|integer',
            'buyer_company_id' => 'nullable|integer',
            'supplier_company_id' => 'nullable|integer',
            'payment_type' => 'required|string|max:40',
            'payment_method' => 'nullable|string|max:60',
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'fees' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:20',
            'status' => 'nullable|string|max:40',
            'meta' => 'nullable|array',
            'paid_at' => 'nullable|date',
        ]), Auth::user());

        return response()->json(['data' => $payment], 201);
    }

    public function exportPolicy($policyId)
    {
        $this->ensureInsuranceEnabled();
        $policy = B2BInsurancePolicy::with(['provider', 'quote', 'buyerCompany', 'supplierCompany', 'claims'])->findOrFail($policyId);
        $this->authorizeInsuranceAccess($policy->buyer_company_id, $policy->supplier_company_id);

        $pdf = \PDF::loadView('backend.b2b.insurance.policy_pdf', compact('policy'))->output();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $policy->policy_number . '.pdf"',
        ]);
    }

    public function exportClaim($claimId)
    {
        $this->ensureInsuranceEnabled();
        $claim = B2BInsuranceClaim::with(['policy.provider', 'documents', 'buyerCompany', 'supplierCompany'])->findOrFail($claimId);
        $this->authorizeInsuranceAccess($claim->buyer_company_id, $claim->supplier_company_id);

        $pdf = \PDF::loadView('backend.b2b.insurance.claim_pdf', compact('claim'))->output();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $claim->claim_number . '.pdf"',
        ]);
    }

    protected function validatedProvider(Request $request, bool $requireName = true): array
    {
        return $request->validate([
            'name' => [$requireName ? 'required' : 'sometimes', 'string', 'max:255'],
            'company' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'logo' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:120',
            'coverage' => 'nullable|array',
            'integration_mode' => 'nullable|in:' . implode(',', B2BInsuranceProvider::INTEGRATION_MODES),
            'api_base_url' => 'nullable|string|max:255',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'credentials' => 'nullable|array',
            'webhook_url' => 'nullable|string|max:255',
            'webhook_secret' => 'nullable|string',
            'policy_types' => 'nullable|array',
            'supported_countries' => 'nullable|array',
            'premium_rules' => 'nullable|array',
            'claim_rules' => 'nullable|array',
            'custom_config' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'is_test_mode' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);
    }

    protected function validatedQuote(Request $request, $company): array
    {
        $validated = $request->validate([
            'provider_id' => 'nullable|integer',
            'buyer_company_id' => 'nullable|integer',
            'supplier_company_id' => 'nullable|integer',
            'shipment_id' => 'nullable|integer',
            'container_shipment_id' => 'nullable|integer',
            'freight_quote_id' => 'nullable|integer',
            'purchase_order_id' => 'nullable|integer',
            'proforma_invoice_id' => 'nullable|integer',
            'insurance_type' => 'required|string|max:80',
            'transport_mode' => 'nullable|string|max:50',
            'incoterm' => 'nullable|string|max:20',
            'container_type' => 'nullable|string|max:40',
            'origin_country' => 'nullable|string|max:120',
            'destination_country' => 'nullable|string|max:120',
            'origin_port' => 'nullable|string|max:255',
            'destination_port' => 'nullable|string|max:255',
            'commodity' => 'nullable|string|max:255',
            'hs_code' => 'nullable|string|max:40',
            'weight' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'shipment_value' => 'required|numeric|min:0.01',
            'coverage_amount' => 'nullable|numeric|min:0.01',
            'currency' => 'required|string|max:20',
        ]);

        if (in_array($company->company_type, ['supplier', 'manufacturer', 'distributor', 'wholesaler'], true)) {
            $validated['supplier_company_id'] = $request->input('supplier_company_id', $company->id);
        } else {
            $validated['buyer_company_id'] = $request->input('buyer_company_id', $company->id);
        }

        return $validated;
    }

    protected function getCompany(string $type)
    {
        $company = $this->companyService->getCompanyByUser(Auth::id());
        abort_unless($company, 403);

        if ($type === 'buyer') {
            abort_unless($this->companyService->isApprovedBuyer(Auth::id(), $company->id), 403);
        }

        if ($type === 'supplier') {
            abort_unless($this->companyService->isApprovedSupplier(Auth::id(), $company->id), 403);
        }

        return $company;
    }

    protected function authorizeInsuranceAccess(?int $buyerCompanyId, ?int $supplierCompanyId): void
    {
        if (auth()->user()->user_type === 'admin') {
            return;
        }

        $company = $this->companyService->getCompanyByUser(Auth::id());
        abort_unless($company, 403);
        abort_unless(in_array($company->id, array_filter([$buyerCompanyId, $supplierCompanyId]), true), 403);
    }

    protected function providerResponse(B2BInsuranceProvider $provider): array
    {
        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'company' => $provider->company,
            'slug' => $provider->slug,
            'country' => $provider->country,
            'coverage' => $provider->coverage,
            'integration_mode' => $provider->integration_mode,
            'policy_types' => $provider->policy_types,
            'supported_countries' => $provider->supported_countries,
            'is_active' => $provider->is_active,
            'is_default' => $provider->is_default,
            'is_test_mode' => $provider->is_test_mode,
        ];
    }

    protected function ensureInsuranceEnabled(): void
    {
        abort_unless($this->globalConfigService->insuranceEnabled(), 403, 'B2B insurance is disabled in Global B2B Config.');
    }
}
