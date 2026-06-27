<?php

namespace App\Services;

use App\Models\BusinessSetting;

class B2BGlobalConfigService
{
    public function aiSettings(): array
    {
        return [
            'enabled' => $this->bool('b2b_ai_tools_enabled', true),
            'visible' => $this->bool('b2b_ai_visible', true),
            'rfq_enabled' => $this->bool('b2b_ai_rfq_enabled', true),
            'product_description_enabled' => $this->bool('b2b_ai_product_description_enabled', true),
            'negotiation_enabled' => $this->bool('b2b_ai_negotiation_enabled', true),
            'translation_enabled' => $this->bool('b2b_ai_translation_enabled', true),
        ];
    }

    public function insuranceSettings(): array
    {
        return [
            'enabled' => $this->bool('b2b_insurance_module_enabled', true),
            'visible' => $this->bool('b2b_insurance_visible', true),
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
        $setting = BusinessSetting::query()->where('type', $type)->value('value');

        if ($setting === null) {
            return $default;
        }

        return filter_var($setting, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ((string) $setting === '1');
    }
}
