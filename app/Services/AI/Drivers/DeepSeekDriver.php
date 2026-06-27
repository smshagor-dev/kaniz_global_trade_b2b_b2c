<?php

namespace App\Services\AI\Drivers;

use App\Models\AIProviderSetting;
use RuntimeException;

class DeepSeekDriver extends AbstractAIHttpDriver
{
    public function generate(AIProviderSetting $providerSetting, array $payload): array
    {
        $model = $payload['model'] ?? $providerSetting->model;
        $response = $this->client($providerSetting)
            ->withToken((string) $providerSetting->api_key)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(rtrim((string) ($providerSetting->base_url ?: config('ai.providers.deepseek.base_url')), '/') . '/chat/completions', [
                'model' => $model,
                'messages' => array_values(array_filter([
                    $payload['system_prompt'] ? ['role' => 'system', 'content' => $payload['system_prompt']] : null,
                    ['role' => 'user', 'content' => $payload['prompt']],
                ])),
                'temperature' => (float) ($payload['temperature'] ?? $providerSetting->temperature ?? 0.7),
                'max_tokens' => min((int) ($payload['max_tokens'] ?? $providerSetting->max_tokens ?? 1024), (int) config('ai.limits.max_tokens', 4096)),
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('DeepSeek request failed: ' . $response->body());
        }

        $json = $response->json();
        $content = data_get($json, 'choices.0.message.content');

        if (!$content) {
            throw new RuntimeException('DeepSeek returned an empty response.');
        }

        return $this->normalizedResult(
            $content,
            (string) $model,
            (int) data_get($json, 'usage.prompt_tokens', 0),
            (int) data_get($json, 'usage.completion_tokens', 0),
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
