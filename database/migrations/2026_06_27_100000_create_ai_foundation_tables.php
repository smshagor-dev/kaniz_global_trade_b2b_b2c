<?php

use App\Models\AIPromptTemplate;
use App\Models\AIProviderSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_provider_settings')) {
            Schema::create('ai_provider_settings', function (Blueprint $table) {
                $table->id();
                $table->string('provider')->index();
                $table->string('name');
                $table->text('api_key')->nullable();
                $table->string('base_url')->nullable();
                $table->string('model')->nullable();
                $table->decimal('temperature', 4, 2)->default(0.70);
                $table->unsignedInteger('max_tokens')->default(1024);
                $table->unsignedInteger('timeout')->default(30);
                $table->unsignedInteger('retry_count')->default(1);
                $table->unsignedInteger('daily_limit')->nullable();
                $table->unsignedInteger('monthly_limit')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_default')->default(false)->index();
                $table->json('settings')->nullable();
                $table->timestamp('last_tested_at')->nullable();
                $table->string('last_status')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_requests')) {
            Schema::create('ai_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('provider_setting_id')->nullable()->constrained('ai_provider_settings')->nullOnDelete();
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('module')->index();
                $table->string('provider')->index();
                $table->string('model')->nullable()->index();
                $table->string('prompt_hash', 64)->index();
                $table->string('prompt_preview', 500)->nullable();
                $table->string('status')->default('pending')->index();
                $table->unsignedInteger('latency_ms')->nullable();
                $table->unsignedInteger('prompt_tokens')->default(0);
                $table->unsignedInteger('completion_tokens')->default(0);
                $table->unsignedInteger('total_tokens')->default(0);
                $table->decimal('estimated_cost', 12, 6)->default(0);
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('ai_usage_logs')) {
            Schema::table('ai_usage_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_usage_logs', 'ai_request_id')) {
                    $table->unsignedBigInteger('ai_request_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('ai_usage_logs', 'module')) {
                    $table->string('module')->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('ai_usage_logs', 'provider')) {
                    $table->string('provider')->nullable()->after('module')->index();
                }
                if (!Schema::hasColumn('ai_usage_logs', 'status')) {
                    $table->string('status')->nullable()->after('model')->index();
                }
                if (!Schema::hasColumn('ai_usage_logs', 'latency_ms')) {
                    $table->unsignedInteger('latency_ms')->nullable()->after('status');
                }
                if (!Schema::hasColumn('ai_usage_logs', 'estimated_cost')) {
                    $table->decimal('estimated_cost', 12, 6)->default(0)->after('total_tokens');
                }
                if (!Schema::hasColumn('ai_usage_logs', 'metadata')) {
                    $table->json('metadata')->nullable()->after('estimated_cost');
                }
            });
        } else {
            Schema::create('ai_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ai_request_id')->nullable()->index();
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->string('module')->nullable()->index();
                $table->string('provider')->nullable()->index();
                $table->string('model')->nullable()->index();
                $table->string('status')->nullable()->index();
                $table->unsignedInteger('latency_ms')->nullable();
                $table->unsignedInteger('prompt_tokens')->default(0);
                $table->unsignedInteger('completion_tokens')->default(0);
                $table->unsignedInteger('total_tokens')->default(0);
                $table->decimal('estimated_cost', 12, 6)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_prompt_templates')) {
            Schema::create('ai_prompt_templates', function (Blueprint $table) {
                $table->id();
                $table->string('module')->index();
                $table->string('name');
                $table->string('legacy_identifier')->nullable()->index();
                $table->longText('system_prompt')->nullable();
                $table->longText('user_prompt_template');
                $table->json('variables')->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_cost_reports')) {
            Schema::create('ai_cost_reports', function (Blueprint $table) {
                $table->id();
                $table->date('report_date')->index();
                $table->string('provider')->index();
                $table->string('model')->nullable()->index();
                $table->unsignedInteger('total_requests')->default(0);
                $table->unsignedInteger('successful_requests')->default(0);
                $table->unsignedInteger('failed_requests')->default(0);
                $table->unsignedBigInteger('prompt_tokens')->default(0);
                $table->unsignedBigInteger('completion_tokens')->default(0);
                $table->unsignedBigInteger('total_tokens')->default(0);
                $table->decimal('estimated_cost', 12, 6)->default(0);
                $table->string('currency')->default('USD');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_feedback')) {
            Schema::create('ai_feedback', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ai_request_id')->nullable()->index();
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->string('module')->nullable()->index();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->text('feedback')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        $this->migrateLegacyPrompts();
        $this->migrateLegacyGeminiProvider();
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedback');
        Schema::dropIfExists('ai_cost_reports');
        Schema::dropIfExists('ai_prompt_templates');
        Schema::dropIfExists('ai_requests');
        Schema::dropIfExists('ai_provider_settings');

        if (Schema::hasTable('ai_usage_logs')) {
            Schema::table('ai_usage_logs', function (Blueprint $table) {
                foreach (['ai_request_id', 'module', 'provider', 'status', 'latency_ms', 'estimated_cost', 'metadata'] as $column) {
                    if (Schema::hasColumn('ai_usage_logs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    protected function migrateLegacyPrompts(): void
    {
        if (!Schema::hasTable('ai_prompts')) {
            return;
        }

        $rows = DB::table('ai_prompts')->get();
        foreach ($rows as $row) {
            AIPromptTemplate::query()->updateOrCreate(
                ['legacy_identifier' => $row->identifier],
                [
                    'module' => $row->identifier === 'product_add_edit_prompt' ? 'product_generation' : 'legacy',
                    'name' => $row->identifier,
                    'system_prompt' => null,
                    'user_prompt_template' => $row->prompt,
                    'variables' => [],
                    'version' => 1,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function migrateLegacyGeminiProvider(): void
    {
        $geminiModel = DB::table('business_settings')->where('type', 'gemini_model')->value('value');
        $aiActivation = (int) (DB::table('business_settings')->where('type', 'ai_activation')->value('value') ?? 0);
        $geminiApiKey = env('GEMINI_API_KEY');

        if (!$geminiModel && !$geminiApiKey) {
            return;
        }

        AIProviderSetting::query()->updateOrCreate(
            ['provider' => 'gemini', 'name' => 'Legacy Gemini'],
            [
                'api_key' => $geminiApiKey,
                'base_url' => config('ai.providers.gemini.base_url'),
                'model' => $geminiModel ?: 'gemini-2.5-flash',
                'temperature' => 0.70,
                'max_tokens' => 1024,
                'timeout' => 30,
                'retry_count' => 1,
                'daily_limit' => null,
                'monthly_limit' => null,
                'is_active' => $aiActivation === 1 || (bool) $geminiApiKey,
                'is_default' => $aiActivation === 1,
                'settings' => [],
                'last_status' => 'migrated_from_legacy',
            ]
        );
    }
};
