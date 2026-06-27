<?php

namespace App\Services\AI;

use App\Models\AICostReport;
use App\Models\AIProviderSetting;
use App\Models\AIRequest;

class AICostService
{
    public function estimate(AIProviderSetting $providerSetting, int $promptTokens, int $completionTokens): float
    {
        $pricing = (array) data_get($providerSetting->settings, 'pricing', []);
        $promptRate = (float) data_get($pricing, 'prompt_per_1k', data_get(config('ai.pricing.' . $providerSetting->provider, []), 'prompt_per_1k', 0));
        $completionRate = (float) data_get($pricing, 'completion_per_1k', data_get(config('ai.pricing.' . $providerSetting->provider, []), 'completion_per_1k', 0));

        return round((($promptTokens / 1000) * $promptRate) + (($completionTokens / 1000) * $completionRate), 6);
    }

    public function record(AIRequest $request): void
    {
        $report = AICostReport::query()->firstOrCreate(
            [
                'report_date' => now()->toDateString(),
                'provider' => $request->provider,
                'model' => $request->model,
            ],
            ['currency' => 'USD']
        );

        $report->increment('total_requests');
        if ($request->status === 'success') {
            $report->increment('successful_requests');
        } else {
            $report->increment('failed_requests');
        }

        $report->increment('prompt_tokens', (int) $request->prompt_tokens);
        $report->increment('completion_tokens', (int) $request->completion_tokens);
        $report->increment('total_tokens', (int) $request->total_tokens);
        $report->increment('estimated_cost', (float) $request->estimated_cost);
    }
}
