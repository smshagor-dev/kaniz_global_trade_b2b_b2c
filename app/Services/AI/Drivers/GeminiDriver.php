<?php

namespace App\Services\AI\Drivers;

use App\Models\AIProviderSetting;
use RuntimeException;

class GeminiDriver extends AbstractAIHttpDriver
{
    public function generate(AIProviderSetting $providerSetting, array $payload): array
    {
        $model = $payload['model'] ?? $providerSetting->model;
        $baseUrl = rtrim((string) ($providerSetting->base_url ?: config('ai.providers.gemini.base_url')), '/');
        $apiKey = $providerSetting->api_key;
        $prompt = trim(($payload['system_prompt'] ? $payload['system_prompt'] . "\n\n" : '') . $payload['prompt']);

        $response = $this->client($providerSetting)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($baseUrl . '/models/' . $model . ':generateContent?key=' . urlencode((string) $apiKey), [
                'contents' => [[
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => (float) ($payload['temperature'] ?? $providerSetting->temperature ?? 0.7),
                    'maxOutputTokens' => min((int) ($payload['max_tokens'] ?? $providerSetting->max_tokens ?? 1024), (int) config('ai.limits.max_tokens', 4096)),
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gemini request failed: ' . $response->body());
        }

        $json = $response->json();
        $content = data_get($json, 'candidates.0.content.parts.0.text');

        if (!$content) {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return $this->normalizedResult(
            $content,
            (string) $model,
            (int) data_get($json, 'usageMetadata.promptTokenCount', 0),
            (int) data_get($json, 'usageMetadata.candidatesTokenCount', 0),
            $json
        );
    }

    public function testConnection(AIProviderSetting $providerSetting): array
    {
        return $this->generate($providerSetting, [
            'prompt' => 'Reply with OK',
            'system_prompt' => 'You are a connection test.',
            'temperature' => 0,
            'max_tokens' => 10,
        ]);
    }
}
