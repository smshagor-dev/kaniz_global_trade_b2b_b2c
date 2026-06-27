<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $templates = [
            ['module' => 'b2b_price_recommendation', 'prompt' => 'Explain this pricing recommendation in 2-3 concise sentences: {pricing_json}'],
            ['module' => 'b2b_supplier_risk', 'prompt' => 'Explain this supplier risk assessment clearly and concisely: {supplier_risk_json}'],
            ['module' => 'b2b_buyer_risk', 'prompt' => 'Explain this buyer trust assessment clearly and concisely: {buyer_risk_json}'],
            ['module' => 'b2b_freight_recommendation', 'prompt' => 'Explain this freight recommendation clearly and concisely: {freight_recommendation_json}'],
            ['module' => 'b2b_currency_analysis', 'prompt' => 'Explain this currency analysis clearly and concisely: {currency_analysis_json}'],
            ['module' => 'b2b_dashboard_insight', 'prompt' => 'Write a short executive summary for this dashboard payload: {dashboard_json}'],
        ];

        foreach ($templates as $template) {
            DB::table('ai_prompt_templates')->updateOrInsert(
                ['module' => $template['module'], 'name' => 'default', 'version' => 1],
                [
                    'legacy_identifier' => null,
                    'system_prompt' => 'You are a commercial intelligence assistant. Do not invent unavailable data.',
                    'user_prompt_template' => $template['prompt'],
                    'variables' => json_encode(['payload']),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('ai_prompt_templates')->whereIn('module', [
            'b2b_price_recommendation',
            'b2b_supplier_risk',
            'b2b_buyer_risk',
            'b2b_freight_recommendation',
            'b2b_currency_analysis',
            'b2b_dashboard_insight',
        ])->delete();
    }
};
