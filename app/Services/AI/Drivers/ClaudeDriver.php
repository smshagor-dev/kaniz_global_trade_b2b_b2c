<?php

namespace App\Services\AI\Drivers;

use App\Models\AIProviderSetting;
use RuntimeException;

class ClaudeDriver extends AbstractAIHttpDriver
{
    public function generate(AIProviderSetting $providerSetting, array $payload): array
    {
        $model = $payload['model'] ?? $providerSetting->model;
        $response = $this->client($providerSetting)
            ->withHeaders([
                'x-api-key' => (string) $providerSetting->api_key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post(rtrim((string) ($providerSetting->base_url ?: config('ai.providers.claude.base_url')), '/') . '/messages', [
                'model' => $model,
                'system' => $payload['system_prompt'] ?: null,
                'messages' => [[
                    'role' => 'user',
                    'content' => $payload['prompt'],
                ]],
                'temperature' => (float) ($payload['temperature'] ?? $providerSetting->temperature ?? 0.7),
                'max_tokens' => min((int) ($payload['max_tokens'] ?? $providerSetting->max_tokens ?? 1024), (int) config('ai.limits.max_tokens', 4096)),
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Claude request failed: ' . $response->body());
        }

        $json = $response->json();
        $content = data_get($json, 'content.0.text');

        if (!$content) {
            throw new RuntimeException('Claude returned an empty response.');
        }

        return $this->normalizedResult(
            $content,
            (string) $model,
            (int) data_get($json, 'usage.input_tokens', 0),
            (int) data_get($json, 'usage.output_tokens', 0),
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
