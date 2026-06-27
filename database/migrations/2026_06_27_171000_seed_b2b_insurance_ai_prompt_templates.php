<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_prompt_templates')) {
            return;
        }

        $templates = [
            [
                'module' => 'b2b_insurance_risk',
                'name' => 'default',
                'system_prompt' => 'You are a careful trade insurance analyst. Return valid JSON only and do not invent unavailable data.',
                'user_prompt_template' => 'Assess this trade insurance quote risk and recommend coverage notes: {quote_json}',
                'variables' => json_encode(['quote_json']),
                'version' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'module' => 'b2b_insurance_claim_validation',
                'name' => 'default',
                'system_prompt' => 'You validate trade insurance claims. Return valid JSON only and flag missing evidence clearly.',
                'user_prompt_template' => 'Validate this trade insurance claim payload: {claim_json}',
                'variables' => json_encode(['claim_json']),
                'version' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'module' => 'b2b_insurance_fraud_detection',
                'name' => 'default',
                'system_prompt' => 'You review cargo insurance fraud indicators. Return valid JSON only with concise signals.',
                'user_prompt_template' => 'Review these insurance fraud indicators: {claim_json}',
                'variables' => json_encode(['claim_json']),
                'version' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('ai_prompt_templates')->updateOrInsert(
                ['module' => $template['module'], 'name' => $template['name'], 'version' => $template['version']],
                $template
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_prompt_templates')) {
            return;
        }

        DB::table('ai_prompt_templates')
            ->whereIn('module', [
                'b2b_insurance_risk',
                'b2b_insurance_claim_validation',
                'b2b_insurance_fraud_detection',
            ])
            ->delete();
    }
};
