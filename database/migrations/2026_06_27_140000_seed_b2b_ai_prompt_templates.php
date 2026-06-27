<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $templates = [
            [
                'module' => 'b2b_rfq_assistant',
                'name' => 'default',
                'system_prompt' => 'You are an enterprise B2B sourcing assistant. Return valid JSON only.',
                'user_prompt_template' => 'Improve this RFQ and return JSON with keys: title, description, suggested_incoterm, suggested_documents, supplier_requirements. RFQ data: {"title":"{title}","description":"{description}","category":"{category}","product":"{product}","quantity":"{quantity}","unit":"{unit}","target_price":"{target_price}","currency":"{currency}","destination_country":"{destination_country}","destination_city":"{destination_city}","incoterm":"{incoterm}"}',
                'variables' => json_encode(['title', 'description', 'category', 'product', 'quantity', 'unit', 'target_price', 'currency', 'destination_country', 'destination_city', 'incoterm']),
            ],
            [
                'module' => 'b2b_supplier_match_explanation',
                'name' => 'default',
                'system_prompt' => 'You explain B2B supplier matching. Return valid JSON only.',
                'user_prompt_template' => 'Given this RFQ context {rfq_json} and these ranked suppliers {supplier_candidates_json}, return JSON with keys: summary, watchouts, top_focus.',
                'variables' => json_encode(['rfq_json', 'supplier_candidates_json']),
            ],
            [
                'module' => 'b2b_hs_code_assistant',
                'name' => 'default',
                'system_prompt' => 'You classify trade products into HS codes. Return valid JSON only.',
                'user_prompt_template' => 'Using the product query "{query}" for country "{country}" and candidate HS codes {candidate_hs_codes_json}, return JSON with keys: suggested_hs_code, description, confidence, required_documents, restrictions.',
                'variables' => json_encode(['query', 'country', 'candidate_hs_codes_json']),
            ],
            [
                'module' => 'b2b_document_summary',
                'name' => 'default',
                'system_prompt' => 'You summarize trade workflow records for internal users. Return valid JSON only.',
                'user_prompt_template' => 'Summarize this {entity_type} record {entity_json}. Return JSON with keys: title, summary, action_items.',
                'variables' => json_encode(['entity_type', 'entity_json']),
            ],
            [
                'module' => 'b2b_trade_assistant',
                'name' => 'default',
                'system_prompt' => 'You are a careful B2B trade workflow assistant. Do not invent private data and keep answers concise.',
                'user_prompt_template' => 'Company context: {company_json}. Optional workflow context: {context_json}. User question: {question}',
                'variables' => json_encode(['company_json', 'context_json', 'question']),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('ai_prompt_templates')->updateOrInsert(
                [
                    'module' => $template['module'],
                    'name' => $template['name'],
                    'version' => 1,
                ],
                [
                    'legacy_identifier' => null,
                    'system_prompt' => $template['system_prompt'],
                    'user_prompt_template' => $template['user_prompt_template'],
                    'variables' => $template['variables'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('ai_prompt_templates')
            ->whereIn('module', [
                'b2b_rfq_assistant',
                'b2b_supplier_match_explanation',
                'b2b_hs_code_assistant',
                'b2b_document_summary',
                'b2b_trade_assistant',
            ])
            ->delete();
    }
};
