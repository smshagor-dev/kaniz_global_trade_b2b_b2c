<?php

namespace App\Services\AI;

use App\Models\AIProviderSetting;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Drivers\ClaudeDriver;
use App\Services\AI\Drivers\GeminiDriver;
use App\Services\AI\Drivers\OpenAIDriver;
use InvalidArgumentException;

class AIManager
{
    public function driver(string $provider): AIProviderInterface
    {
        return match ($provider) {
            'gemini' => new GeminiDriver(),
            'openai' => new OpenAIDriver(),
            'claude' => new ClaudeDriver(),
            default => throw new InvalidArgumentException('Unsupported AI provider: ' . $provider),
        };
    }

    public function activeProviders(?int $preferredId = null): array
    {
        $query = AIProviderSetting::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id');

        $providers = $query->get()->all();

        if (!$preferredId) {
            return $providers;
        }

        usort($providers, function (AIProviderSetting $a, AIProviderSetting $b) use ($preferredId) {
            $aRank = $a->id === $preferredId ? 0 : 1;
            $bRank = $b->id === $preferredId ? 0 : 1;

            return $aRank <=> $bRank;
        });

        return $providers;
    }

    public function defaultProvider(): ?AIProviderSetting
    {
        return AIProviderSetting::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }
}
