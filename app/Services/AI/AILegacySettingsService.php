<?php

namespace App\Services\AI;

use App\Models\AIProviderSetting;
use App\Models\BusinessSetting;

class AILegacySettingsService
{
    public function sync(): void
    {
        $defaultProvider = AIProviderSetting::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        BusinessSetting::updateOrCreate(
            ['type' => 'ai_activation'],
            ['value' => $defaultProvider ? '1' : '0']
        );

        $geminiProvider = AIProviderSetting::query()
            ->where('provider', 'gemini')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if ($geminiProvider) {
            BusinessSetting::updateOrCreate(
                ['type' => 'gemini_model'],
                ['value' => $geminiProvider->model]
            );
        }
    }
}
