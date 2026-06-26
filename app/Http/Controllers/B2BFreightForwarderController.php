<?php

namespace App\Http\Controllers;

use App\Models\B2BFreightForwarder;
use App\Services\B2BFreightService;
use App\Services\B2BIntegrationManagementService;
use App\Services\Freight\FreightForwarderManager;
use Illuminate\Http\Request;

class B2BFreightForwarderController extends Controller
{
    public function __construct(
        protected FreightForwarderManager $manager,
        protected B2BIntegrationManagementService $integrationService,
        protected B2BFreightService $freightService
    )
    {
    }

    public function adminIndex()
    {
        $forwarders = B2BFreightForwarder::query()->latest()->paginate(20);

        if (request()->expectsJson()) {
            return response()->json(['forwarders' => $forwarders]);
        }

        $integrationPayloads = $forwarders->getCollection()
            ->mapWithKeys(fn ($forwarder) => [$forwarder->id => $this->integrationService->buildPayload($forwarder, 'freight')]);

        return view('backend.b2b.freight_forwarders.index', compact('forwarders', 'integrationPayloads'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $forwarder = B2BFreightForwarder::create((new B2BFreightForwarder())->filterPersistable($data));
        $this->integrationService->ensureWebhookSecret($forwarder);

        if (!request()->expectsJson()) {
            flash(translate('Freight forwarder created successfully.'))->success();
            return back();
        }

        return response()->json(['success' => true, 'forwarder_id' => $forwarder->id]);
    }

    public function update(Request $request, $id)
    {
        $forwarder = B2BFreightForwarder::findOrFail($id);
        $data = $this->validatedData($request, $forwarder);

        foreach (['api_key', 'api_secret', 'username', 'password', 'account_number', 'oauth_token', 'refresh_token', 'webhook_secret'] as $secretField) {
            if (!filled($data[$secretField] ?? null)) {
                unset($data[$secretField]);
            }
        }

        $forwarder->update($forwarder->filterPersistable($data));
        $this->integrationService->ensureWebhookSecret($forwarder);

        flash(translate('Freight forwarder updated successfully.'))->success();

        return back();
    }

    public function testConnection($id)
    {
        $forwarder = B2BFreightForwarder::findOrFail($id);
        $startedAt = microtime(true);
        $result = $this->manager->testConnection($forwarder);
        $responseTime = (int) round((microtime(true) - $startedAt) * 1000);
        $forwarder->update($forwarder->filterPersistable([
            'last_api_test_status' => ($result['success'] ?? false) ? 'success' : ($result['status'] ?? 'failed'),
            'last_api_test_message' => $result['message'] ?? null,
            'last_api_tested_at' => now(),
        ]));
        $this->integrationService->recordConnectionResult($forwarder, $result, $responseTime, $result['http_status'] ?? null);

        if (!request()->expectsJson()) {
            flash(translate($result['message'] ?? 'Connection test completed.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();
            return back();
        }

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function testAuthentication($id)
    {
        $forwarder = B2BFreightForwarder::findOrFail($id);
        $startedAt = microtime(true);
        $result = $this->manager->validateCredentials($forwarder);
        $this->integrationService->recordConnectionResult($forwarder, $result, (int) round((microtime(true) - $startedAt) * 1000), $result['http_status'] ?? null);

        flash(translate($result['message'] ?? 'Authentication test completed.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();

        return back();
    }

    public function verifyCredentials($id)
    {
        return $this->testAuthentication($id);
    }

    public function testWebhook(Request $request, $id)
    {
        $forwarder = B2BFreightForwarder::findOrFail($id);

        if (!filled($forwarder->webhook_secret)) {
            $this->integrationService->ensureWebhookSecret($forwarder);
            $forwarder->refresh();
        }

        $this->integrationService->recordWebhookReceived($forwarder, true);
        flash(translate('Webhook configuration verified successfully.'))->success();

        return back();
    }

    public function sendSampleWebhook(Request $request, $id)
    {
        $forwarder = B2BFreightForwarder::findOrFail($id);
        $channel = $request->input('channel');
        $payload = $this->integrationService->sampleWebhookPayload($forwarder, 'freight', $channel);
        $signature = (string) $this->integrationService->ensureWebhookSecret($forwarder);

        $result = $this->freightService->handleWebhook($forwarder, $payload, $signature);
        $this->integrationService->recordWebhookReceived($forwarder, (bool) ($result['success'] ?? false));

        flash(translate($result['message'] ?? 'Sample webhook sent.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();

        return back();
    }

    public function regenerateSecret($id)
    {
        $forwarder = B2BFreightForwarder::findOrFail($id);
        $this->integrationService->regenerateWebhookSecret($forwarder);

        flash(translate('Webhook secret regenerated successfully.'))->success();

        return back();
    }

    public function updateIntegrationEvents(Request $request, $id)
    {
        $forwarder = B2BFreightForwarder::findOrFail($id);
        $events = $request->input('integration_events', []);
        $this->integrationService->updateEvents($forwarder, is_array($events) ? $events : []);

        flash(translate('Integration events updated successfully.'))->success();

        return back();
    }

    protected function validatedData(Request $request, ?B2BFreightForwarder $forwarder = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|in:' . implode(',', B2BFreightForwarder::DRIVERS),
            'logo' => 'nullable|string|max:255',
            'banner' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'support_email' => 'nullable|email|max:255',
            'support_phone' => 'nullable|string|max:50',
            'provider_type' => 'nullable|string|max:40',
            'api_base_url' => 'nullable|string|max:255',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'account_number' => 'nullable|string',
            'oauth_token' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'environment' => 'nullable|string|max:40',
            'is_test_mode' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'supported_modes' => 'nullable',
            'supported_services' => 'nullable',
            'supported_countries' => 'nullable',
            'container_types' => 'nullable',
            'default_freight_cost' => 'nullable|numeric|min:0',
            'default_insurance_cost' => 'nullable|numeric|min:0',
            'default_customs_estimate' => 'nullable|numeric|min:0',
            'credentials' => 'nullable',
            'custom_config' => 'nullable',
            'notes' => 'nullable|string',
        ]) + [
            'webhook_secret' => $request->input('webhook_secret') ?: null,
            'supported_modes' => $this->normalizeValue($request->input('supported_modes')),
            'supported_services' => $this->normalizeValue($request->input('supported_services')),
            'supported_countries' => $this->normalizeValue($request->input('supported_countries')),
            'container_types' => $this->normalizeValue($request->input('container_types')),
            'default_freight_cost' => (float) ($request->input('default_freight_cost', 0) ?: 0),
            'default_insurance_cost' => (float) ($request->input('default_insurance_cost', 0) ?: 0),
            'default_customs_estimate' => (float) ($request->input('default_customs_estimate', 0) ?: 0),
            'credentials' => $this->normalizeJsonValue($request->input('credentials')),
            'custom_config' => $this->normalizeJsonValue($request->input('custom_config')),
            'integration_events' => $this->normalizeValue($request->input('integration_events')),
            'provider_type' => $request->input('provider_type', 'ocean_carrier'),
            'is_test_mode' => $request->boolean('is_test_mode', true),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    protected function normalizeValue(mixed $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), fn ($item) => filled($item)));
        }

        $decoded = json_decode((string) $value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter(array_map('trim', $decoded), fn ($item) => filled($item)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $value)), fn ($item) => filled($item)));
    }

    protected function normalizeJsonValue(mixed $value): ?array
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
