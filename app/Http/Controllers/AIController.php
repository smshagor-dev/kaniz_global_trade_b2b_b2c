<?php

namespace App\Http\Controllers;

use App\Models\AICostReport;
use App\Models\AIBuyerRisk;
use App\Models\AICurrencyAnalysis;
use App\Models\AIDashboardInsight;
use App\Models\AIFeedback;
use App\Models\AIFreightRecommendation;
use App\Models\AINotificationEvent;
use App\Models\AIPriceRecommendation;
use App\Models\AIProviderSetting;
use App\Models\AIPromptTemplate;
use App\Models\AISupplierRisk;
use App\Models\AITradeOpportunity;
use App\Models\AIUsageLog;
use App\Services\AI\AILegacySettingsService;
use App\Services\AI\AIManager;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function __construct(
        protected AIManager $aiManager,
        protected AILegacySettingsService $legacySettingsService
    ) {
    }

    public function ai_token_usage(Request $request)
    {
        $query = AIUsageLog::with('user')->latest();

        if ($request->filled('date')) {
            $dates = explode(' to ', $request->date);

            if (count($dates) === 2) {
                try {
                    $startDate = Carbon::createFromFormat('d-m-Y', trim($dates[0]))->startOfDay();
                    $endDate = Carbon::createFromFormat('d-m-Y', trim($dates[1]))->endOfDay();
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                } catch (\Throwable $throwable) {
                }
            }
        }

        $logs = $query->paginate(20);
        $totalRequests = (clone $query)->count();
        $totalTokens = (clone $query)->sum('total_tokens');
        $totalCost = (float) (clone $query)->sum('estimated_cost');
        $avgPerRequest = $totalRequests > 0 ? round($totalTokens / $totalRequests) : 0;

        return view('backend.reports.ai_token_usage', [
            'logs' => $logs,
            'totalRequests' => $totalRequests,
            'totalTokens' => $totalTokens,
            'totalCost' => $totalCost,
            'avgPerRequest' => $avgPerRequest,
            'date' => $request->date,
        ]);
    }

    public function ai_configuration(Request $request)
    {
        $provider = $this->legacySettingsService->ensureGeminiProvider();
        $this->legacySettingsService->sync();
        $providerOptions = array_keys((array) config('ai.providers', []));
        $editProvider = $request->filled('edit')
            ? AIProviderSetting::query()->find($request->integer('edit'))
            : null;

        if (!$editProvider) {
            $preferredProvider = $request->string('provider')->toString();
            if ($preferredProvider !== '') {
                $editProvider = AIProviderSetting::query()
                    ->where('provider', $preferredProvider)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->first();
            }
        }

        return view('backend.setup_configurations.ai_configurations.ai_config', [
            'providers' => AIProviderSetting::query()
                ->orderByDesc('is_default')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'providerOptions' => $providerOptions,
            'providerMeta' => (array) config('ai.providers', []),
            'editProvider' => $editProvider,
        ]);
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->providerRules());
        $provider = AIProviderSetting::query()->firstOrNew(['provider' => $validated['provider']]);
        $provider->fill($this->providerPayload($validated, $provider));
        $provider->save();

        if (!empty($validated['is_default'])) {
            $this->setDefaultProviderId($provider->id);
        }

        $this->legacySettingsService->sync();
        flash(translate('AI configuration saved successfully'))->success();

        return back();
    }

    public function updateProvider(Request $request, $id): RedirectResponse
    {
        $provider = AIProviderSetting::query()
            ->findOrFail(decrypt($id));
        $validated = $request->validate($this->providerRules($provider->id));
        $payload = $this->providerPayload($validated, $provider);

        if (empty($validated['api_key'])) {
            unset($payload['api_key']);
        }

        $provider->update($payload);

        if (!empty($validated['is_default'])) {
            $this->setDefaultProviderId($provider->id);
        }

        $this->legacySettingsService->sync();
        flash(translate('AI configuration updated successfully'))->success();

        return redirect()->route('ai-config');
    }

    public function testProvider($id): RedirectResponse
    {
        $provider = AIProviderSetting::query()
            ->findOrFail(decrypt($id));

        try {
            $result = $this->aiManager->driver($provider->provider)->testConnection($provider);
            $provider->update([
                'last_tested_at' => now(),
                'last_status' => 'ok',
            ]);

            flash(translate('Provider test succeeded using ') . ($result['model'] ?? $provider->model))->success();
        } catch (\Throwable $throwable) {
            $provider->update([
                'last_tested_at' => now(),
                'last_status' => 'failed',
            ]);

            flash(translate('Provider test failed: ') . $throwable->getMessage())->error();
        }

        return back();
    }

    public function setDefaultProvider($id): RedirectResponse
    {
        $provider = AIProviderSetting::query()
            ->findOrFail(decrypt($id));
        $this->setDefaultProviderId($provider->id);
        $this->legacySettingsService->sync();

        flash(translate('Default AI provider updated successfully'))->success();

        return back();
    }

    public function toggleProvider($id): RedirectResponse
    {
        $provider = AIProviderSetting::query()
            ->findOrFail(decrypt($id));
        $provider->update(['is_active' => !$provider->is_active]);
        $this->legacySettingsService->sync();

        flash(translate('AI provider status updated successfully'))->success();

        return back();
    }

    public function ai_templates()
    {
        return view('backend.setup_configurations.ai_configurations.prompt_templates', [
            'prompt_templates' => AIPromptTemplate::query()->orderBy('module')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $validated = $request->validate([
            'module' => 'required|string|max:100',
            'name' => 'required|string|max:150',
            'system_prompt' => 'nullable|string',
            'user_prompt_template' => 'required|string',
            'variables' => 'nullable|string',
            'version' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $prompt = AIPromptTemplate::findOrFail(decrypt($id));
        $prompt->update([
            'module' => $validated['module'],
            'name' => $validated['name'],
            'system_prompt' => $validated['system_prompt'] ?? null,
            'user_prompt_template' => $validated['user_prompt_template'],
            'variables' => $validated['variables']
                ? array_values(array_filter(array_map('trim', explode(',', $validated['variables']))))
                : [],
            'version' => (int) $validated['version'],
            'is_active' => $request->boolean('is_active'),
        ]);

        flash(translate('Prompt template updated successfully'))->success();

        return back();
    }

    public function createPromptTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'module' => 'required|string|max:100',
            'name' => 'required|string|max:150',
            'system_prompt' => 'nullable|string',
            'user_prompt_template' => 'required|string',
            'variables' => 'nullable|string',
            'version' => 'required|integer|min:1',
        ]);

        AIPromptTemplate::query()->create([
            'module' => $validated['module'],
            'name' => $validated['name'],
            'system_prompt' => $validated['system_prompt'] ?? null,
            'user_prompt_template' => $validated['user_prompt_template'],
            'variables' => $validated['variables']
                ? array_values(array_filter(array_map('trim', explode(',', $validated['variables']))))
                : [],
            'version' => (int) $validated['version'],
            'is_active' => true,
        ]);

        flash(translate('Prompt template created successfully'))->success();

        return back();
    }

    public function costAnalytics()
    {
        return view('backend.setup_configurations.ai_configurations.cost_analytics', [
            'reports' => AICostReport::query()->latest('report_date')->paginate(20),
            'summary' => [
                'total_cost' => (float) AICostReport::query()->sum('estimated_cost'),
                'total_requests' => (int) AICostReport::query()->sum('total_requests'),
                'success_rate' => $this->successRate(),
            ],
        ]);
    }

    public function feedback()
    {
        return view('backend.setup_configurations.ai_configurations.feedback', [
            'feedback' => AIFeedback::with('request')->latest()->paginate(20),
        ]);
    }

    public function add_edit_products()
    {
        return view('backend.setup_configurations.ai_configurations.add_edit');
    }

    public function commercialDashboard()
    {
        $latestInsights = AIDashboardInsight::query()->latest()->limit(5)->get();
        $chart = [
            'labels' => collect(range(6, 0))->map(fn ($days) => now()->subDays($days)->format('M d'))->values(),
            'price' => collect(range(6, 0))->map(fn ($days) => AIPriceRecommendation::query()->whereDate('created_at', now()->subDays($days)->toDateString())->count())->values(),
            'risk' => collect(range(6, 0))->map(fn ($days) => AISupplierRisk::query()->whereDate('created_at', now()->subDays($days)->toDateString())->count())->values(),
            'freight' => collect(range(6, 0))->map(fn ($days) => AIFreightRecommendation::query()->whereDate('created_at', now()->subDays($days)->toDateString())->count())->values(),
        ];

        return view('backend.setup_configurations.ai_configurations.commercial_dashboard', [
            'summary' => [
                'price_count' => AIPriceRecommendation::count(),
                'supplier_risk_count' => AISupplierRisk::count(),
                'buyer_risk_count' => AIBuyerRisk::count(),
                'freight_count' => AIFreightRecommendation::count(),
                'currency_count' => AICurrencyAnalysis::count(),
                'opportunity_count' => AITradeOpportunity::count(),
                'notification_count' => AINotificationEvent::count(),
            ],
            'latestInsights' => $latestInsights,
            'chart' => $chart,
        ]);
    }

    public function commercialPriceRecommendations()
    {
        return view('backend.setup_configurations.ai_configurations.commercial_list', [
            'title' => translate('Price Recommendation'),
            'records' => AIPriceRecommendation::query()->latest()->paginate(20),
            'columns' => ['created_at' => 'Date', 'company_id' => 'Company', 'selling_price' => 'Selling', 'minimum_profitable_price' => 'Minimum', 'confidence_score' => 'Confidence', 'source' => 'Source'],
        ]);
    }

    public function commercialSupplierRisk()
    {
        return view('backend.setup_configurations.ai_configurations.commercial_list', [
            'title' => translate('Supplier Risk'),
            'records' => AISupplierRisk::query()->latest()->paginate(20),
            'columns' => ['created_at' => 'Date', 'supplier_company_id' => 'Supplier', 'risk_score' => 'Score', 'risk_level' => 'Level', 'confidence_score' => 'Confidence'],
        ]);
    }

    public function commercialBuyerRisk()
    {
        return view('backend.setup_configurations.ai_configurations.commercial_list', [
            'title' => translate('Buyer Risk'),
            'records' => AIBuyerRisk::query()->latest()->paginate(20),
            'columns' => ['created_at' => 'Date', 'buyer_company_id' => 'Buyer', 'trust_score' => 'Trust', 'risk_level' => 'Level', 'confidence_score' => 'Confidence'],
        ]);
    }

    public function commercialOpportunities()
    {
        return view('backend.setup_configurations.ai_configurations.commercial_list', [
            'title' => translate('Trade Opportunities'),
            'records' => AITradeOpportunity::query()->latest()->paginate(20),
            'columns' => ['created_at' => 'Date', 'company_id' => 'Company', 'opportunity_type' => 'Type', 'title' => 'Title', 'opportunity_score' => 'Score', 'confidence_score' => 'Confidence'],
        ]);
    }

    public function commercialCurrency()
    {
        return view('backend.setup_configurations.ai_configurations.commercial_list', [
            'title' => translate('Currency Analysis'),
            'records' => AICurrencyAnalysis::query()->latest()->paginate(20),
            'columns' => ['created_at' => 'Date', 'currency_code' => 'Currency', 'volatility_score' => 'Volatility', 'recommended_invoice_currency' => 'Recommended', 'confidence_score' => 'Confidence'],
        ]);
    }

    public function commercialFreight()
    {
        return view('backend.setup_configurations.ai_configurations.commercial_list', [
            'title' => translate('Freight Recommendation'),
            'records' => AIFreightRecommendation::query()->latest()->paginate(20),
            'columns' => ['created_at' => 'Date', 'recommended_mode' => 'Mode', 'recommended_forwarder_name' => 'Forwarder', 'estimated_shipping_cost' => 'Cost', 'risk_score' => 'Risk', 'confidence_score' => 'Confidence'],
        ]);
    }

    public function commercialNotifications()
    {
        return view('backend.setup_configurations.ai_configurations.commercial_list', [
            'title' => translate('AI Notifications'),
            'records' => AINotificationEvent::query()->latest()->paginate(20),
            'columns' => ['created_at' => 'Date', 'company_id' => 'Company', 'event_type' => 'Event', 'severity' => 'Severity', 'title' => 'Title', 'confidence_score' => 'Confidence'],
        ]);
    }

    protected function providerRules(?int $ignoreId = null): array
    {
        return [
            'provider' => 'required|string|in:' . implode(',', array_keys((array) config('ai.providers', []))),
            'name' => 'required|string|max:150',
            'api_key' => 'nullable|string|max:4000',
            'base_url' => 'nullable|string|max:255',
            'model' => 'required|string|max:150',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:' . config('ai.limits.max_tokens', 4096),
            'timeout' => 'nullable|integer|min:1|max:120',
            'retry_count' => 'nullable|integer|min:0|max:5',
            'daily_limit' => 'nullable|integer|min:0',
            'monthly_limit' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'settings_json' => 'nullable|string',
        ];
    }

    protected function providerPayload(array $validated, ?AIProviderSetting $provider = null): array
    {
        $settings = [];
        if (!empty($validated['settings_json'])) {
            $settings = json_decode($validated['settings_json'], true) ?: [];
        }

        $providerKey = (string) ($validated['provider'] ?? $provider?->provider ?? 'gemini');
        $providerConfig = (array) config('ai.providers.' . $providerKey, []);
        $providerLabel = (string) ($providerConfig['label'] ?? ucfirst($providerKey));

        return [
            'provider' => $providerKey,
            'name' => $validated['name'] ?: $providerLabel,
            'api_key' => $validated['api_key'] ?? null,
            'base_url' => $validated['base_url'] ?: ($providerConfig['base_url'] ?? null),
            'model' => $validated['model'],
            'temperature' => $validated['temperature'] ?? 0.7,
            'max_tokens' => $validated['max_tokens'] ?? 1024,
            'timeout' => $validated['timeout'] ?? 30,
            'retry_count' => $validated['retry_count'] ?? 1,
            'daily_limit' => $validated['daily_limit'] ?? null,
            'monthly_limit' => $validated['monthly_limit'] ?? null,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : true,
            'is_default' => isset($validated['is_default']) ? (bool) $validated['is_default'] : false,
            'settings' => $settings,
        ];
    }

    protected function setDefaultProviderId(int $providerId): void
    {
        AIProviderSetting::query()->update(['is_default' => false]);
        AIProviderSetting::query()->whereKey($providerId)->update(['is_default' => true, 'is_active' => true]);
    }

    protected function successRate(): float
    {
        $successful = (int) AICostReport::query()->sum('successful_requests');
        $total = (int) AICostReport::query()->sum('total_requests');

        return $total > 0 ? round(($successful / $total) * 100, 2) : 0.0;
    }
}
