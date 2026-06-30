<?php

namespace App\Services\AI\Drivers;

use App\Models\AIProviderSetting;
use RuntimeException;

class OpenAIDriver extends AbstractAIHttpDriver
{
    public function generate(AIProviderSetting $providerSetting, array $payload): array
    {
        $model = $payload['model'] ?? $providerSetting->model;
        $input = $payload['prompt'];

        if (!empty($payload['image']['base64'])) {
            $mimeType = (string) ($payload['image']['mime_type'] ?? 'image/jpeg');
            $input = [[
                'role' => 'user',
                'content' => array_values(array_filter([
                    $payload['prompt'] !== '' ? [
                        'type' => 'input_text',
                        'text' => $payload['prompt'],
                    ] : null,
                    [
                        'type' => 'input_image',
                        'image_url' => 'data:' . $mimeType . ';base64,' . $payload['image']['base64'],
                    ],
                ])),
            ]];
        }

        $response = $this->client($providerSetting)
            ->withToken((string) $providerSetting->api_key)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(rtrim((string) ($providerSetting->base_url ?: config('ai.providers.openai.base_url')), '/') . '/responses', [
                'model' => $model,
                'input' => $input,
                'instructions' => $payload['system_prompt'] ?: null,
                'temperature' => (float) ($payload['temperature'] ?? $providerSetting->temperature ?? 0.7),
                'max_output_tokens' => min((int) ($payload['max_tokens'] ?? $providerSetting->max_tokens ?? 1024), (int) config('ai.limits.max_tokens', 4096)),
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('OpenAI request failed: ' . $response->body());
        }

        $json = $response->json();
        $content = (string) ($json['output_text'] ?? '');

        if ($content === '') {
            throw new RuntimeException('OpenAI returned an empty response.');
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
