<?php

namespace App\Http\Controllers;

use App\Models\B2BInsuranceClaim;
use App\Models\B2BInsuranceApiLog;
use App\Models\B2BInsurancePayment;
use App\Models\B2BInsurancePolicy;
use App\Models\B2BInsuranceProvider;
use App\Models\B2BInsuranceQuote;
use App\Models\BusinessSetting;
use App\Services\B2BIntegrationManagementService;
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
        protected B2BGlobalConfigService $globalConfigService,
        protected B2BIntegrationManagementService $integrationService
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
            'apiLogs' => B2BInsuranceApiLog::with('provider')->latest()->limit(20)->get(),
            'providerOptions' => $this->insuranceService->providerOptions(),
        ];

        $payload['integrationPayloads'] = $payload['providers']->getCollection()
            ->mapWithKeys(fn ($provider) => [$provider->id => $this->integrationService->buildPayload($provider, 'insurance')]);

        if (!request()->expectsJson()) {
            return view('backend.b2b.insurance.dashboard', $payload);
        }

        return response()->json($payload);
    }

    public function adminConfig()
    {
        $presetProviders = collect($this->insuranceService->providerOptions()['real_providers'] ?? [])
            ->map(function (array $preset, string $key) {
                $provider = B2BInsuranceProvider::query()
                    ->where('custom_config->provider_key', $key)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->first();

                return [
                    'key' => $key,
                    'preset' => $preset,
                    'provider' => $provider,
                ];
            })
            ->values();

        return view('backend.b2b.insurance.config', [
            'insuranceSettings' => $this->globalConfigService->insuranceSettings(),
            'providers' => B2BInsuranceProvider::query()
                ->orderByDesc('is_default')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'providerOptions' => $this->insuranceService->providerOptions(),
            'presetProviders' => $presetProviders,
        ]);
    }

    public function updateConfig(Request $request)
    {
        $data = $request->validate([
            'save_scope' => 'nullable|string|in:module,provider',
            'b2b_insurance_module_enabled' => 'nullable|boolean',
            'b2b_insurance_visible' => 'nullable|boolean',
            'provider_id' => 'nullable|integer|exists:b2b_insurance_providers,id',
            'provider_key' => 'required|string|in:' . implode(',', $this->insuranceService->configuredProviderKeys()),
            'api_key' => 'nullable|string|max:4000',
            'api_secret' => 'nullable|string|max:4000',
            'username' => 'nullable|string|max:4000',
            'password' => 'nullable|string|max:4000',
            'is_test_mode' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $settings = [
            'b2b_insurance_module_enabled' => $request->boolean('b2b_insurance_module_enabled'),
            'b2b_insurance_visible' => $request->boolean('b2b_insurance_visible'),
        ];

        $this->globalConfigService->updateMany($settings);

        if (($data['save_scope'] ?? 'provider') === 'module') {
            flash(translate('B2B insurance module controls updated successfully.'))->success();

            return back();
        }

        $provider = !empty($data['provider_id'])
            ? B2BInsuranceProvider::query()->find($data['provider_id'])
            : B2BInsuranceProvider::query()->orderByDesc('is_default')->orderBy('id')->first();
        $testMode = $request->boolean('is_test_mode', true);
        $payload = $this->insuranceService->applyProviderPreset((string) $data['provider_key'], $testMode, [
            'is_active' => $request->boolean('is_active', $settings['b2b_insurance_module_enabled']),
            'is_default' => $request->boolean('is_default'),
            'is_test_mode' => $testMode,
        ]);

        foreach (['api_key', 'api_secret', 'username', 'password'] as $secretField) {
            if ($request->filled($secretField)) {
                $payload[$secretField] = $data[$secretField];
            }
        }

        if ($provider) {
            $provider = $this->insuranceService->updateProvider($provider, $payload, auth()->id());
        } else {
            $provider = $this->insuranceService->createProvider($payload, auth()->id());
        }

        if ($provider->is_default) {
            BusinessSetting::updateOrCreate(
                ['type' => 'b2b_insurance_default_provider_id'],
                ['value' => (string) $provider->id]
            );
        }

        flash(translate('B2B insurance configuration updated successfully.'))->success();

        return back();
    }

    public function storeProvider(Request $request)
    {
        $this->ensureInsuranceEnabled();
        $provider = $this->insuranceService->createProvider($this->validatedProvider($request), Auth::id());
        $this->integrationService->ensureWebhookSecret($provider);

        if (!request()->expectsJson()) {
            flash(translate('Insurance provider created successfully.'))->success();
            return back();
        }

        return response()->json(['data' => $this->providerResponse($provider)], 201);
    }

    public function updateProvider(Request $request, $providerId)
    {
        $this->ensureInsuranceEnabled();
        $provider = B2BInsuranceProvider::findOrFail($providerId);
        $data = $this->validatedProvider($request, false);

        foreach (['api_key', 'api_secret', 'username', 'password', 'webhook_secret'] as $secretField) {
            if (!filled($data[$secretField] ?? null)) {
                unset($data[$secretField]);
            }
        }

        $updated = $this->insuranceService->updateProvider($provider, $data, Auth::id());
        $this->integrationService->ensureWebhookSecret($updated);

        if (!request()->expectsJson()) {
            flash(translate('Insurance provider updated successfully.'))->success();
            return back();
        }

        return response()->json(['data' => $this->providerResponse($updated)]);
    }

    public function testConnection($providerId)
    {
        $this->ensureInsuranceEnabled();
        $provider = B2BInsuranceProvider::findOrFail($providerId);
        $result = $this->insuranceService->testConnection($provider);
        $this->integrationService->recordConnectionResult($provider, $result, (int) ($result['latency_ms'] ?? 0), $result['http_status'] ?? null);

        if (!request()->expectsJson()) {
            flash(translate($result['message'] ?? 'Insurance API test completed.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();
            return back();
        }

        return response()->json($result);
    }

    public function testAuthentication($providerId)
    {
        return $this->testConnection($providerId);
    }

    public function verifyCredentials($providerId)
    {
        return $this->testConnection($providerId);
    }

    public function testWebhook($providerId)
    {
        $this->ensureInsuranceEnabled();
        $provider = B2BInsuranceProvider::findOrFail($providerId);

        if (!filled($provider->webhook_secret)) {
            $this->integrationService->ensureWebhookSecret($provider);
            $provider->refresh();
        }

        $this->integrationService->recordWebhookReceived($provider, true);
        $this->insuranceService->logProviderCall($provider, $provider, [
            'direction' => 'inbound',
            'endpoint' => route('b2b.insurance-webhooks.handle', ['provider' => $provider->id]),
            'request_method' => 'POST',
            'status' => 'processed',
            'response_payload' => ['message' => 'Webhook configuration verified successfully.'],
        ]);

        flash(translate('Insurance webhook configuration verified successfully.'))->success();

        return back();
    }

    public function sendSampleWebhook(Request $request, $providerId)
    {
        $this->ensureInsuranceEnabled();
        $provider = B2BInsuranceProvider::findOrFail($providerId);

        if (!filled($provider->webhook_secret)) {
            $this->integrationService->ensureWebhookSecret($provider);
            $provider->refresh();
        }

        $samplePayload = [
            'event' => $request->input('channel', 'policy.updated'),
            'provider' => $provider->slug,
            'policy_number' => 'IP-SAMPLE-' . now()->format('His'),
            'claim_number' => 'IC-SAMPLE-' . now()->format('His'),
            'status' => 'processed',
            'occurred_at' => now()->toIso8601String(),
        ];

        $this->integrationService->recordWebhookReceived($provider, true);
        $this->insuranceService->logProviderCall($provider, $provider, [
            'direction' => 'inbound',
            'endpoint' => route('b2b.insurance-webhooks.handle', ['provider' => $provider->id]),
            'request_method' => 'POST',
            'status' => 'processed',
            'request_payload' => $samplePayload,
            'response_payload' => ['message' => 'Sample insurance webhook recorded.'],
        ]);

        flash(translate('Sample insurance webhook recorded successfully.'))->success();

        return back();
    }

    public function regenerateSecret($providerId)
    {
        $this->ensureInsuranceEnabled();
        $provider = B2BInsuranceProvider::findOrFail($providerId);
        $this->integrationService->regenerateWebhookSecret($provider);
        flash(translate('Insurance webhook secret regenerated successfully.'))->success();

        return back();
    }

    public function updateIntegrationEvents(Request $request, $providerId)
    {
        $this->ensureInsuranceEnabled();
        $provider = B2BInsuranceProvider::findOrFail($providerId);
        $events = $request->input('integration_events', []);
        $this->integrationService->updateEvents($provider, is_array($events) ? $events : []);
        flash(translate('Insurance integration events updated successfully.'))->success();

        return back();
    }

    public function handleWebhook(Request $request, $providerId)
    {
        $provider = B2BInsuranceProvider::findOrFail($providerId);
        $signature = (string) $request->header('X-Webhook-Signature', '');

        if (filled($provider->webhook_secret) && !hash_equals((string) $provider->webhook_secret, $signature)) {
            $this->insuranceService->logProviderCall($provider, $provider, [
                'direction' => 'inbound',
                'endpoint' => $request->fullUrl(),
                'request_method' => $request->method(),
                'status' => 'invalid_signature',
                'request_payload' => $request->all(),
                'error_message' => 'Invalid webhook signature.',
            ]);

            return response()->json(['message' => 'Invalid webhook signature.'], 403);
        }

        $this->integrationService->recordWebhookReceived($provider, true);
        $this->insuranceService->logProviderCall($provider, $provider, [
            'direction' => 'inbound',
            'endpoint' => $request->fullUrl(),
            'request_method' => $request->method(),
            'status' => 'processed',
            'request_payload' => $request->all(),
            'response_payload' => ['message' => 'Insurance webhook processed successfully.'],
        ]);

        return response()->json(['message' => 'Insurance webhook processed successfully.']);
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
        $data = $request->validate([
            'provider_key' => 'nullable|string|in:' . implode(',', $this->insuranceService->configuredProviderKeys()),
            'name' => [$requireName ? 'required' : 'sometimes', 'string', 'max:255'],
            'company' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'logo' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:120',
            'coverage' => 'nullable',
            'integration_mode' => 'nullable|in:' . implode(',', B2BInsuranceProvider::INTEGRATION_MODES),
            'api_base_url' => 'nullable|string|max:255',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'credentials' => 'nullable',
            'webhook_url' => 'nullable|string|max:255',
            'webhook_secret' => 'nullable|string',
            'policy_types' => 'nullable',
            'supported_countries' => 'nullable',
            'premium_rules' => 'nullable',
            'claim_rules' => 'nullable',
            'custom_config' => 'nullable',
            'integration_events' => 'nullable',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'is_test_mode' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $providerKey = (string) $request->input('provider_key', '');
        if ($providerKey !== '') {
            $data = $this->insuranceService->applyProviderPreset($providerKey, $request->boolean('is_test_mode', true), $data);
        }

        return $data + [
            'coverage' => $this->normalizeListInput($request->input('coverage')),
            'credentials' => $this->normalizeJsonInput($request->input('credentials')),
            'policy_types' => $this->normalizeListInput($request->input('policy_types')),
            'supported_countries' => $this->normalizeListInput($request->input('supported_countries')),
            'premium_rules' => $this->normalizeJsonInput($request->input('premium_rules')),
            'claim_rules' => $this->normalizeJsonInput($request->input('claim_rules')),
            'custom_config' => $this->normalizeJsonInput($request->input('custom_config')),
            'integration_events' => $this->normalizeListInput($request->input('integration_events')),
            'is_active' => $request->boolean('is_active'),
            'is_default' => $request->boolean('is_default'),
            'is_test_mode' => $request->boolean('is_test_mode', true),
        ];
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
            'provider_key' => data_get($provider->custom_config, 'provider_key'),
        ];
    }

    protected function ensureInsuranceEnabled(): void
    {
        abort_unless($this->globalConfigService->insuranceEnabled(), 403, 'B2B insurance is disabled in Global B2B Config.');
    }

    protected function normalizeListInput(mixed $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), fn ($item) => filled($item)));
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter(array_map('trim', $decoded), fn ($item) => filled($item)));
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/[\r\n,]+/', (string) $value)),
            fn ($item) => filled($item)
        ));
    }

    protected function normalizeJsonInput(mixed $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
