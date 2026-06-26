<?php

namespace App\Http\Controllers;

use App\Models\B2BShippingProvider;
use App\Services\B2BAuditService;
use App\Services\B2BIntegrationManagementService;
use App\Services\B2BShipmentTrackingService;
use App\Services\B2BTradeService;
use App\Services\Carriers\CarrierManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class B2BShippingProviderController extends Controller
{
    public function __construct(
        protected CarrierManager $carrierManager,
        protected B2BAuditService $b2bAuditService,
        protected B2BIntegrationManagementService $integrationService,
        protected B2BShipmentTrackingService $shipmentTrackingService
    ) {
    }

    public function index()
    {
        $providers = B2BShippingProvider::latest()->paginate(20);

        return view('backend.b2b.shipping_providers.index', [
            'providers' => $providers,
            'transportModes' => B2BTradeService::TRANSPORT_MODES,
            'providerTypes' => B2BShippingProvider::PROVIDER_TYPES,
            'apiDrivers' => B2BShippingProvider::API_DRIVERS,
            'driverLabels' => B2BShippingProvider::DRIVER_LABELS,
            'modeDriverMap' => B2BShippingProvider::MODE_DRIVER_MAP,
            'driverCapabilities' => B2BShippingProvider::DRIVER_CAPABILITIES,
            'integrationPayloads' => $providers->getCollection()->mapWithKeys(fn ($provider) => [$provider->id => $this->integrationService->buildPayload($provider, 'carrier')]),
        ]);
    }

    public function store(Request $request)
    {
        $provider = B2BShippingProvider::create((new B2BShippingProvider())->filterPersistable($this->validatedData($request)));
        $this->integrationService->ensureWebhookSecret($provider);

        $this->b2bAuditService->log(auth()->id(), null, 'shipping_provider_created', $provider, 'Shipping provider created.');

        flash(translate('Shipping provider created successfully.'))->success();

        return back();
    }

    public function update(Request $request, $id)
    {
        $provider = B2BShippingProvider::findOrFail($id);
        $data = $this->validatedData($request);

        foreach (['api_key', 'api_secret', 'account_number', 'username', 'password', 'oauth_token', 'refresh_token', 'webhook_secret'] as $secretField) {
            if (!filled($data[$secretField] ?? null)) {
                unset($data[$secretField]);
            }
        }

        $provider->update($provider->filterPersistable($data));
        $this->integrationService->ensureWebhookSecret($provider);

        $this->b2bAuditService->log(auth()->id(), null, 'shipping_provider_updated', $provider, 'Shipping provider updated.');

        flash(translate('Shipping provider updated successfully.'))->success();

        return back();
    }

    public function testConnection($id)
    {
        $provider = B2BShippingProvider::findOrFail($id);
        $startedAt = microtime(true);
        $result = $this->carrierManager->testConnection($provider);
        $this->integrationService->recordConnectionResult($provider, $result, (int) round((microtime(true) - $startedAt) * 1000), $result['http_status'] ?? null);

        if (!request()->expectsJson()) {
            flash(translate($result['message'] ?? 'Connection test completed.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();
            return back();
        }

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'status' => $result['status'] ?? 'unknown',
            'message' => $result['message'] ?? 'No response returned.',
        ]);
    }

    public function testAuthentication($id)
    {
        $provider = B2BShippingProvider::findOrFail($id);
        $startedAt = microtime(true);
        $result = $this->carrierManager->validateCredentials($provider);
        $this->integrationService->recordConnectionResult($provider, $result, (int) round((microtime(true) - $startedAt) * 1000), $result['http_status'] ?? null);
        flash(translate($result['message'] ?? 'Authentication test completed.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();

        return back();
    }

    public function verifyCredentials($id)
    {
        return $this->testAuthentication($id);
    }

    public function testWebhook($id)
    {
        $provider = B2BShippingProvider::findOrFail($id);

        if (!filled($provider->webhook_secret)) {
            $this->integrationService->ensureWebhookSecret($provider);
            $provider->refresh();
        }

        $this->integrationService->recordWebhookReceived($provider, true);
        flash(translate('Webhook configuration verified successfully.'))->success();

        return back();
    }

    public function sendSampleWebhook(Request $request, $id)
    {
        $provider = B2BShippingProvider::findOrFail($id);
        $channel = $request->input('channel');
        $payload = $this->integrationService->sampleWebhookPayload($provider, 'carrier', $channel);
        $signature = (string) $this->integrationService->ensureWebhookSecret($provider);

        $result = $this->shipmentTrackingService->handleWebhook($provider, $payload, $signature);
        $this->integrationService->recordWebhookReceived($provider, (bool) ($result['success'] ?? false));

        flash(translate($result['message'] ?? 'Sample webhook sent.'))->{($result['success'] ?? false) ? 'success' : 'warning'}();

        return back();
    }

    public function regenerateSecret($id)
    {
        $provider = B2BShippingProvider::findOrFail($id);
        $this->integrationService->regenerateWebhookSecret($provider);
        flash(translate('Webhook secret regenerated successfully.'))->success();

        return back();
    }

    public function updateIntegrationEvents(Request $request, $id)
    {
        $provider = B2BShippingProvider::findOrFail($id);
        $events = $request->input('integration_events', []);
        $this->integrationService->updateEvents($provider, is_array($events) ? $events : []);
        flash(translate('Integration events updated successfully.'))->success();

        return back();
    }

    protected function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'transport_mode' => 'required|in:' . implode(',', B2BTradeService::TRANSPORT_MODES),
            'provider_type' => 'required|in:' . implode(',', B2BShippingProvider::PROVIDER_TYPES),
            'api_driver' => 'nullable|in:' . implode(',', B2BShippingProvider::API_DRIVERS),
            'api_base_url' => 'nullable|string|max:255',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'account_number' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'oauth_token' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'environment' => 'nullable|string|max:40',
            'custom_config' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'supported_countries' => 'nullable|string',
            'supported_services' => 'nullable|string',
            'default_shipping_cost' => 'nullable|numeric|min:0',
            'default_insurance_amount' => 'nullable|numeric|min:0',
            'default_customs_estimate' => 'nullable|numeric|min:0',
            'integration_events' => 'nullable',
            'notes' => 'nullable|string',
            'is_test_mode' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
        ]);

        $payload = $data + [
            'api_driver' => $data['provider_type'] === 'api' ? ($data['api_driver'] ?? 'custom') : null,
            'supported_countries' => $this->normalizeListInput($request->input('supported_countries')),
            'supported_services' => $this->normalizeListInput($request->input('supported_services')),
            'integration_events' => $this->normalizeListInput($request->input('integration_events')),
            'custom_config' => $this->normalizeJsonInput($request->input('custom_config')),
            'default_shipping_cost' => (float) ($data['default_shipping_cost'] ?? 0),
            'default_insurance_amount' => (float) ($data['default_insurance_amount'] ?? 0),
            'default_customs_estimate' => (float) ($data['default_customs_estimate'] ?? 0),
            'is_test_mode' => $request->boolean('is_test_mode', true),
            'is_active' => $request->boolean('is_active'),
            'is_verified' => $request->boolean('is_verified'),
        ];

        if (($payload['provider_type'] ?? null) === 'api' && filled($payload['api_driver'] ?? null)) {
            $allowedDrivers = B2BShippingProvider::driversForMode($payload['transport_mode'] ?? null);

            if (!in_array($payload['api_driver'], $allowedDrivers, true)) {
                throw ValidationException::withMessages([
                    'api_driver' => [translate('The selected API driver is not available for the chosen transport mode.')],
                ]);
            }
        }

        return $payload;
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
            array_map('trim', preg_split('/[\r\n,]+/', $value)),
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

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
