<?php

namespace App\Services;

use App\Models\AIProviderSetting;
use App\Models\B2BInsuranceProvider;
use App\Models\BusinessSetting;

class B2BGlobalConfigService
{
    public function aiSettings(): array
    {
        $selectedProviderId = (int) ($this->value('b2b_ai_provider_id') ?? 0);
        $provider = AIProviderSetting::query()
            ->when($selectedProviderId > 0, fn ($query) => $query->whereKey($selectedProviderId))
            ->first();

        if (!$provider) {
            $provider = AIProviderSetting::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
        }

        return [
            'enabled' => $this->bool('b2b_ai_tools_enabled', true),
            'visible' => $this->bool('b2b_ai_visible', true),
            'rfq_enabled' => $this->bool('b2b_ai_rfq_enabled', true),
            'product_description_enabled' => $this->bool('b2b_ai_product_description_enabled', true),
            'negotiation_enabled' => $this->bool('b2b_ai_negotiation_enabled', true),
            'translation_enabled' => $this->bool('b2b_ai_translation_enabled', true),
            'global_price' => (float) ($this->value('b2b_ai_global_price') ?? 0),
            'provider_id' => $provider?->id,
            'provider_key' => $provider?->provider ?: 'gemini',
            'provider_name' => $provider?->name ?: 'Gemini',
            'model' => $provider?->model ?: (string) ($this->value('gemini_model') ?: 'gemini-2.5-flash'),
            'has_api_key' => filled($provider?->api_key),
            'provider_active' => (bool) ($provider?->is_active ?? false),
        ];
    }

    public function insuranceSettings(): array
    {
        $provider = B2BInsuranceProvider::query()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        return [
            'enabled' => $this->bool('b2b_insurance_module_enabled', true),
            'visible' => $this->bool('b2b_insurance_visible', true),
            'provider_name' => $provider?->name ?: 'Trade Insurance Provider',
            'provider_company' => $provider?->company ?: '',
            'provider_country' => $provider?->country ?: '',
            'integration_mode' => $provider?->integration_mode ?: 'manual',
            'api_base_url' => $provider?->api_base_url ?: '',
            'has_api_key' => filled($provider?->api_key),
            'provider_active' => (bool) ($provider?->is_active ?? false),
        ];
    }

    public function aiEnabled(): bool
    {
        return $this->aiSettings()['enabled'];
    }

    public function aiVisible(): bool
    {
        return $this->aiSettings()['visible'];
    }

    public function aiToolEnabled(string $tool): bool
    {
        return (bool) data_get($this->aiSettings(), $tool, false);
    }

    public function insuranceEnabled(): bool
    {
        return $this->insuranceSettings()['enabled'];
    }

    public function insuranceVisible(): bool
    {
        return $this->insuranceSettings()['visible'];
    }

    public function updateMany(array $settings): void
    {
        foreach ($settings as $type => $value) {
            BusinessSetting::updateOrCreate(
                ['type' => $type],
                ['value' => (int) (bool) $value]
            );
        }
    }

    protected function bool(string $type, bool $default = false): bool
    {
        $setting = $this->value($type);

        if ($setting === null) {
            return $default;
        }

        return filter_var($setting, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ((string) $setting === '1');
    }

    protected function value(string $type): mixed
    {
        return BusinessSetting::query()->where('type', $type)->value('value');
    }
}
