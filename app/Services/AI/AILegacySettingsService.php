<?php

namespace App\Services\AI;

use App\Models\AIProviderSetting;
use App\Models\BusinessSetting;

class AILegacySettingsService
{
    public function sync(): void
    {
        $provider = AIProviderSetting::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (!$provider) {
            $provider = $this->ensureGeminiProvider();
        }

        BusinessSetting::updateOrCreate(
            ['type' => 'ai_activation'],
            ['value' => $provider?->is_active ? '1' : '0']
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

    public function ensureGeminiProvider(?string $apiKey = null, ?string $model = null, ?bool $isActive = null): AIProviderSetting
    {
        $configuredModel = $model
            ?: (string) (BusinessSetting::where('type', 'gemini_model')->value('value') ?: 'gemini-2.5-flash');
        $resolvedActive = $isActive ?? ((int) (BusinessSetting::where('type', 'ai_activation')->value('value') ?? 0) === 1);

        $provider = AIProviderSetting::query()
            ->where('provider', 'gemini')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (!$provider) {
            $provider = new AIProviderSetting([
                'provider' => 'gemini',
                'name' => 'Gemini',
            ]);
        }

        $provider->fill([
            'provider' => 'gemini',
            'name' => $provider->name ?: 'Gemini',
            'base_url' => config('ai.providers.gemini.base_url'),
            'model' => $configuredModel,
            'temperature' => $provider->temperature ?? 0.70,
            'max_tokens' => $provider->max_tokens ?? 1024,
            'timeout' => $provider->timeout ?? 30,
            'retry_count' => $provider->retry_count ?? 1,
            'is_active' => $resolvedActive,
            'is_default' => $provider->exists ? (bool) $provider->is_default : !AIProviderSetting::query()->where('is_default', true)->exists(),
            'settings' => $provider->settings ?? [],
        ]);

        if ($apiKey !== null && $apiKey !== '') {
            $provider->api_key = $apiKey;
        }

        $provider->save();

        return $provider->fresh();
    }
}
