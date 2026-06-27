<?php

namespace App\Services\AI\Drivers;

use App\Models\AIProviderSetting;
use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class AbstractAIHttpDriver implements AIProviderInterface
{
    protected function client(AIProviderSetting $providerSetting): PendingRequest
    {
        return Http::timeout(max((int) $providerSetting->timeout, 1))
            ->retry(max((int) $providerSetting->retry_count, 0), 200, throw: false)
            ->acceptJson();
    }

    protected function normalizedResult(
        string $content,
        string $model,
        int $promptTokens = 0,
        int $completionTokens = 0,
        array $raw = []
    ): array {
        return [
            'content' => trim($content),
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
            'raw' => $raw,
        ];
    }
}
