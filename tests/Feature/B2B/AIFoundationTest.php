<?php

namespace Tests\Feature\B2B;

use App\Jobs\RunAIRequestJob;
use App\Models\AICostReport;
use App\Models\AIProviderSetting;
use App\Models\AIPromptTemplate;
use App\Models\AIRequest;
use App\Models\AIUsageLog;
use App\Services\AI\AIManager;
use App\Services\AI\AIPromptService;
use App\Services\AI\AIRequestService;
use App\Services\AiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class AIFoundationTest extends B2BFeatureTestCase
{
    public function test_provider_creation_encrypts_api_key(): void
    {
        $provider = AIProviderSetting::query()->create([
            'provider' => 'gemini',
            'name' => 'Gemini Primary',
            'api_key' => 'secret-key',
            'model' => 'gemini-2.5-flash',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->assertSame('secret-key', $provider->fresh()->api_key);
        $this->assertNotSame('secret-key', DB::table('ai_provider_settings')->where('id', $provider->id)->value('api_key'));
    }

    public function test_default_provider_and_switching_work(): void
    {
        $primary = AIProviderSetting::query()->create([
            'provider' => 'gemini',
            'name' => 'Gemini Primary',
            'api_key' => 'secret-key',
            'model' => 'gemini-2.5-flash',
            'is_active' => true,
            'is_default' => true,
        ]);

        $secondary = AIProviderSetting::query()->create([
            'provider' => 'openai',
            'name' => 'OpenAI Backup',
            'api_key' => 'openai-key',
            'model' => 'gpt-4.1-mini',
            'is_active' => true,
            'is_default' => false,
        ]);

        $manager = app(AIManager::class);

        $this->assertSame($primary->id, $manager->defaultProvider()->id);

        AIProviderSetting::query()->update(['is_default' => false]);
        $secondary->update(['is_default' => true]);

        $this->assertSame($secondary->id, $manager->defaultProvider()->id);
    }

    public function test_prompt_template_creation_and_rendering_work(): void
    {
        AIPromptTemplate::query()->create([
            'module' => 'rfq_assistant',
            'name' => 'default',
            'system_prompt' => 'You help with RFQs.',
            'user_prompt_template' => 'Improve {title} for {country}',
            'variables' => ['title', 'country'],
            'version' => 1,
            'is_active' => true,
        ]);

        $rendered = app(AIPromptService::class)->render('rfq_assistant', [
            'title' => 'Need bolts',
            'country' => 'Bangladesh',
        ]);

        $this->assertSame('You help with RFQs.', $rendered['system_prompt']);
        $this->assertSame('Improve Need bolts for Bangladesh', $rendered['user_prompt']);
    }

    public function test_usage_logging_and_cost_estimation_work(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '{"result":"ok"}']]]]],
                'usageMetadata' => [
                    'promptTokenCount' => 120,
                    'candidatesTokenCount' => 80,
                ],
            ], 200),
        ]);

        AIProviderSetting::query()->create([
            'provider' => 'gemini',
            'name' => 'Gemini Primary',
            'api_key' => 'secret-key',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'model' => 'gemini-2.5-flash',
            'is_active' => true,
            'is_default' => true,
            'settings' => [
                'pricing' => [
                    'prompt_per_1k' => 0.002,
                    'completion_per_1k' => 0.004,
                ],
            ],
        ]);

        $result = app(AIRequestService::class)->request([
            'module' => 'test_module',
            'prompt' => 'Return JSON',
            'system_prompt' => 'You are a test',
            'user' => $this->createAdminUser(),
        ]);

        $this->assertSame('gemini', $result['provider']);
        $this->assertSame(200, $result['total_tokens']);
        $this->assertGreaterThan(0, $result['estimated_cost']);
        $this->assertSame(1, AIUsageLog::count());
        $this->assertSame(1, AIRequest::count());
        $this->assertSame(1, AICostReport::count());
    }

    public function test_cache_hit_skips_second_provider_call(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'cached-response']]]]],
                'usageMetadata' => [
                    'promptTokenCount' => 10,
                    'candidatesTokenCount' => 5,
                ],
            ], 200),
        ]);

        AIProviderSetting::query()->create([
            'provider' => 'gemini',
            'name' => 'Gemini Primary',
            'api_key' => 'secret-key',
            'model' => 'gemini-2.5-flash',
            'is_active' => true,
            'is_default' => true,
        ]);

        $service = app(AIRequestService::class);
        $first = $service->request([
            'module' => 'cache_module',
            'prompt' => 'Same prompt',
            'system_prompt' => 'You are a test',
        ]);
        $second = $service->request([
            'module' => 'cache_module',
            'prompt' => 'Same prompt',
            'system_prompt' => 'You are a test',
        ]);

        $this->assertFalse($first['cached']);
        $this->assertTrue($second['cached']);
        Http::assertSentCount(1);
    }

    public function test_queue_job_dispatches(): void
    {
        Queue::fake();

        app(AIRequestService::class)->dispatch([
            'module' => 'queued_module',
            'prompt' => 'Queue this',
            'system_prompt' => 'You are queued',
        ]);

        Queue::assertPushed(RunAIRequestJob::class, fn (RunAIRequestJob $job) => $job->payload()['module'] === 'queued_module');
    }

    public function test_provider_fallback_uses_next_active_provider(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => 'fail'], 500),
            'https://api.openai.com/*' => Http::response([
                'output_text' => 'fallback-ok',
                'usage' => [
                    'input_tokens' => 12,
                    'output_tokens' => 8,
                ],
            ], 200),
        ]);

        AIProviderSetting::query()->create([
            'provider' => 'gemini',
            'name' => 'Gemini Primary',
            'api_key' => 'secret-key',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'model' => 'gemini-2.5-flash',
            'is_active' => true,
            'is_default' => true,
        ]);

        AIProviderSetting::query()->create([
            'provider' => 'openai',
            'name' => 'OpenAI Backup',
            'api_key' => 'openai-key',
            'base_url' => 'https://api.openai.com/v1',
            'model' => 'gpt-4.1-mini',
            'is_active' => true,
            'is_default' => false,
        ]);

        $result = app(AIRequestService::class)->request([
            'module' => 'fallback_module',
            'prompt' => 'Use fallback',
            'system_prompt' => 'You are a test',
        ]);

        $this->assertSame('openai', $result['provider']);
        $this->assertSame(2, AIRequest::count());
        $this->assertSame(1, AIRequest::query()->where('status', 'failed')->count());
        $this->assertSame(1, AIRequest::query()->where('status', 'success')->count());
    }

    public function test_gemini_product_generation_still_works_through_new_manager(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => '{"name":"AI Steel Bolt"}',
                        ]],
                    ],
                ]],
                'usageMetadata' => [
                    'promptTokenCount' => 25,
                    'candidatesTokenCount' => 15,
                ],
            ], 200),
        ]);

        AIProviderSetting::query()->create([
            'provider' => 'gemini',
            'name' => 'Legacy Gemini',
            'api_key' => 'secret-key',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'model' => 'gemini-2.5-flash',
            'is_active' => true,
            'is_default' => true,
        ]);

        AIPromptTemplate::query()->create([
            'module' => 'product_generation',
            'name' => 'product_add_edit_prompt',
            'legacy_identifier' => 'product_add_edit_prompt',
            'system_prompt' => 'Return valid JSON only.',
            'user_prompt_template' => 'Generate {prompt_fields} for {product_name} in {language}.',
            'variables' => ['product_name', 'language', 'prompt_fields'],
            'version' => 1,
            'is_active' => true,
        ]);

        $response = app(AiService::class)->productGenerateWithAI([
            'product_name' => 'Steel Bolt',
            'section' => 'basic-information',
            'lang' => 'en',
        ]);

        $payload = $response->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame('AI Steel Bolt', $payload['data']['name']);
        $this->assertSame('gemini', $payload['provider']);
        $this->assertSame(1, AIRequest::query()->where('module', 'product_generation')->count());
    }
}
