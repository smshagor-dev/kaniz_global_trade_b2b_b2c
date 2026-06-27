<?php

namespace App\Services\AI\Drivers;

use App\Models\AIProviderSetting;
use RuntimeException;

class OllamaDriver extends AbstractAIHttpDriver
{
    public function generate(AIProviderSetting $providerSetting, array $payload): array
    {
        $model = $payload['model'] ?? $providerSetting->model;
        $response = $this->client($providerSetting)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(rtrim((string) ($providerSetting->base_url ?: config('ai.providers.ollama.base_url')), '/') . '/api/generate', [
                'model' => $model,
                'prompt' => trim(($payload['system_prompt'] ? $payload['system_prompt'] . "\n\n" : '') . $payload['prompt']),
                'stream' => false,
                'options' => [
                    'temperature' => (float) ($payload['temperature'] ?? $providerSetting->temperature ?? 0.7),
                    'num_predict' => min((int) ($payload['max_tokens'] ?? $providerSetting->max_tokens ?? 1024), (int) config('ai.limits.max_tokens', 4096)),
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Ollama request failed: ' . $response->body());
        }

        $json = $response->json();
        $content = data_get($json, 'response');

        if (!$content) {
            throw new RuntimeException('Ollama returned an empty response.');
        }

        return $this->normalizedResult(
            $content,
            (string) $model,
            (int) data_get($json, 'prompt_eval_count', 0),
            (int) data_get($json, 'eval_count', 0),
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
