<?php

namespace App\Services\AI;

use App\Models\AIProviderSetting;
use App\Models\AIRequest;
use App\Models\AIUsageLog;
use App\Models\B2BCompany;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AIRequestService
{
    public function __construct(
        protected AIManager $manager,
        protected AICostService $costService,
        protected AICacheService $cacheService
    ) {
    }

    public function request(array $payload): array
    {
        $user = $payload['user'] ?? auth()->user();
        $companyId = $payload['company_id'] ?? null;
        $module = (string) ($payload['module'] ?? 'general');
        $systemPrompt = $this->sanitizePrompt((string) ($payload['system_prompt'] ?? ''));
        $prompt = $this->sanitizePrompt((string) ($payload['prompt'] ?? ''));
        $refresh = (bool) ($payload['refresh'] ?? false);
        $preferredProviderId = $payload['provider_setting_id'] ?? null;

        if ($prompt === '') {
            throw new RuntimeException('AI prompt cannot be empty.');
        }

        $this->assertCompanyAccess($user, $companyId);
        $this->assertRateLimit($user, $module);

        $providers = $this->manager->activeProviders($preferredProviderId);
        if ($providers === []) {
            throw new RuntimeException('No active AI providers are configured.');
        }

        $promptHash = hash('sha256', implode('|', [
            $module,
            $systemPrompt,
            $prompt,
            (string) ($payload['image_hash'] ?? ''),
            json_encode($payload['cache_key'] ?? []),
        ]));

        foreach ($providers as $providerSetting) {
            $this->assertProviderLimit($providerSetting);
            $cacheModel = (string) ($payload['model'] ?? $providerSetting->model);

            if (!$refresh && ($cached = $this->cacheService->get($module, $cacheModel, $promptHash))) {
                return array_merge($cached, ['cached' => true]);
            }

            $started = microtime(true);
            $requestLog = AIRequest::query()->create([
                'provider_setting_id' => $providerSetting->id,
                'user_id' => $user?->id,
                'company_id' => $companyId,
                'module' => $module,
                'provider' => $providerSetting->provider,
                'model' => $cacheModel,
                'prompt_hash' => $promptHash,
                'prompt_preview' => Str::limit(strip_tags($prompt), 500),
                'status' => 'pending',
                'metadata' => (array) ($payload['metadata'] ?? []),
            ]);

            try {
                $result = $this->manager->driver($providerSetting->provider)->generate($providerSetting, [
                    'prompt' => $prompt,
                    'system_prompt' => $systemPrompt,
                    'model' => $cacheModel,
                    'temperature' => $payload['temperature'] ?? $providerSetting->temperature,
                    'max_tokens' => min((int) ($payload['max_tokens'] ?? $providerSetting->max_tokens), (int) config('ai.limits.max_tokens', 4096)),
                ]);

                $latency = (int) round((microtime(true) - $started) * 1000);
                $cost = $this->costService->estimate(
                    $providerSetting,
                    (int) $result['prompt_tokens'],
                    (int) $result['completion_tokens']
                );

                $requestLog->update([
                    'status' => 'success',
                    'latency_ms' => $latency,
                    'prompt_tokens' => (int) $result['prompt_tokens'],
                    'completion_tokens' => (int) $result['completion_tokens'],
                    'total_tokens' => (int) $result['total_tokens'],
                    'estimated_cost' => $cost,
                    'metadata' => array_merge((array) $requestLog->metadata, ['raw' => $result['raw']]),
                ]);

                AIUsageLog::query()->create([
                    'ai_request_id' => $requestLog->id,
                    'user_id' => $user?->id ?: 0,
                    'module' => $module,
                    'provider' => $providerSetting->provider,
                    'model' => $result['model'],
                    'status' => 'success',
                    'latency_ms' => $latency,
                    'prompt_tokens' => (int) $result['prompt_tokens'],
                    'completion_tokens' => (int) $result['completion_tokens'],
                    'total_tokens' => (int) $result['total_tokens'],
                    'estimated_cost' => $cost,
                    'metadata' => ['cached' => false],
                ]);

                $this->costService->record($requestLog->fresh());

                $response = [
                    'content' => $this->sanitizeResponse((string) $result['content']),
                    'provider' => $providerSetting->provider,
                    'model' => $result['model'],
                    'latency_ms' => $latency,
                    'prompt_tokens' => (int) $result['prompt_tokens'],
                    'completion_tokens' => (int) $result['completion_tokens'],
                    'total_tokens' => (int) $result['total_tokens'],
                    'estimated_cost' => $cost,
                    'request_id' => $requestLog->id,
                    'cached' => false,
                ];

                $this->cacheService->put($module, $cacheModel, $promptHash, $response);

                return $response;
            } catch (\Throwable $throwable) {
                $latency = (int) round((microtime(true) - $started) * 1000);
                $requestLog->update([
                    'status' => 'failed',
                    'latency_ms' => $latency,
                    'error_message' => Str::limit($throwable->getMessage(), 1000),
                ]);

                AIUsageLog::query()->create([
                    'ai_request_id' => $requestLog->id,
                    'user_id' => $user?->id ?: 0,
                    'module' => $module,
                    'provider' => $providerSetting->provider,
                    'model' => $cacheModel,
                    'status' => 'failed',
                    'latency_ms' => $latency,
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'total_tokens' => 0,
                    'estimated_cost' => 0,
                    'metadata' => ['cached' => false],
                ]);

                $this->costService->record($requestLog->fresh());
            }
        }

        throw new RuntimeException('All active AI providers failed for module "' . $module . '".');
    }

    public function dispatch(array $payload): void
    {
        \App\Jobs\RunAIRequestJob::dispatch($payload)
            ->onConnection(config('ai.queue.connection', 'database'))
            ->onQueue(config('ai.queue.queue', 'ai'));
    }

    protected function sanitizePrompt(string $prompt): string
    {
        $prompt = strip_tags($prompt);
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        return trim((string) $prompt);
    }

    protected function sanitizeResponse(string $content): string
    {
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/on\w+="[^"]*"/i', '', $content);
        $content = preg_replace("/on\w+='[^']*'/i", '', $content);
        $content = preg_replace('/javascript:/i', '', $content);

        return trim((string) $content);
    }

    protected function assertRateLimit(?User $user, string $module): void
    {
        $key = 'ai-rate:' . ($user?->id ?: 'guest') . ':' . $module . ':' . now()->format('YmdHi');
        $count = Cache::increment($key);
        if ($count === 1) {
            Cache::put($key, 1, now()->addMinute());
        }

        if ($count > (int) config('ai.limits.requests_per_minute', 20)) {
            throw new RuntimeException('AI rate limit exceeded for this user.');
        }
    }

    protected function assertProviderLimit(AIProviderSetting $providerSetting): void
    {
        if ($providerSetting->daily_limit) {
            $todayCount = AIRequest::query()
                ->where('provider_setting_id', $providerSetting->id)
                ->whereDate('created_at', now()->toDateString())
                ->count();

            if ($todayCount >= $providerSetting->daily_limit) {
                throw new RuntimeException('Daily AI request limit reached for provider ' . $providerSetting->name . '.');
            }
        }

        if ($providerSetting->monthly_limit) {
            $monthCount = AIRequest::query()
                ->where('provider_setting_id', $providerSetting->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();

            if ($monthCount >= $providerSetting->monthly_limit) {
                throw new RuntimeException('Monthly AI request limit reached for provider ' . $providerSetting->name . '.');
            }
        }
    }

    protected function assertCompanyAccess(?User $user, ?int $companyId): void
    {
        if (!$companyId || !$user) {
            return;
        }

        $companyIds = B2BCompany::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->merge(
                DB::table('b2b_company_members')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->pluck('b2b_company_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        if (!in_array((int) $companyId, $companyIds, true) && ($user->user_type ?? null) !== 'admin') {
            throw new RuntimeException('You do not have permission to use AI for this company.');
        }
    }
}
