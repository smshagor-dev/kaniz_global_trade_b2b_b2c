<?php

namespace Tests\Feature\B2B;

use App\Models\AIProviderSetting;
use App\Models\AIRequest;
use App\Models\B2BHsCode;
use App\Services\AI\B2B\DocumentSummaryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class B2BAIModuleTest extends B2BFeatureTestCase
{
    public function test_rfq_assistant_generates_suggestions_through_ai_provider(): void
    {
        Http::fake([
            'https://custom.test/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Refined Stainless Fastener RFQ',
                            'description' => 'Need export-ready stainless fasteners with detailed specifications.',
                            'suggested_incoterm' => 'FOB',
                            'suggested_documents' => ['Specification Sheet', 'Quality Certificate'],
                            'supplier_requirements' => ['ISO 9001', 'Export experience'],
                        ]),
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 150,
                    'completion_tokens' => 60,
                ],
            ], 200),
        ]);

        $this->createDefaultProvider();

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $this->setActiveCompany($buyerCompany);

        $response = $this->actingAs($buyerUser)->post(route('b2b.ai.rfq-assistant.generate'), [
            'title' => 'Need stainless fasteners',
            'description' => 'Bulk purchase for industrial use',
            'quantity' => 500,
            'unit' => 'pcs',
            'currency' => 'USD',
            'destination_country' => 'Bangladesh',
        ]);

        $response->assertOk()->assertSee('Refined Stainless Fastener RFQ');
        $this->assertDatabaseHas('ai_requests', [
            'module' => 'b2b_rfq_assistant',
            'company_id' => $buyerCompany->id,
            'status' => 'success',
        ]);
    }

    public function test_supplier_matches_route_returns_ranked_matches(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $category = $this->createCategory(['name' => 'Industrial Fasteners', 'slug' => 'industrial-fasteners-' . random_int(100, 999)]);
        $rfq = $this->createRfq($buyerCompany, $buyerUser, ['category_id' => $category->id]);
        $this->setActiveCompany($buyerCompany);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
            'public_profile_enabled' => true,
            'verified_supplier_badge' => true,
            'featured_supplier' => true,
            'response_rate' => 92,
            'profile_score' => 88,
            'verification_status' => 'approved',
        ]);

        DB::table('b2b_company_categories')->insert([
            'b2b_company_id' => $supplierCompany->id,
            'category_id' => $category->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createProduct($supplierUser, $category, ['name' => 'Stainless Fasteners']);

        $response = $this->actingAs($buyerUser)->get(route('b2b.ai.rfqs.supplier-matches', $rfq->id));

        $response->assertOk()->assertSee($supplierCompany->company_name);
    }

    public function test_hs_code_assistant_falls_back_to_database_when_no_provider_exists(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $this->setActiveCompany($buyerCompany);

        B2BHsCode::query()->create([
            'hs_code' => '731815',
            'description' => 'Screws and bolts of iron or steel',
            'country' => 'Bangladesh',
            'required_documents' => ['Commercial Invoice'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($buyerUser)->post(route('b2b.ai.hs-code.suggest'), [
            'query' => 'steel bolts',
            'country' => 'Bangladesh',
        ]);

        $response->assertOk()->assertSee('731815')->assertSee('DATABASE');
        $this->assertSame(0, AIRequest::query()->where('module', 'b2b_hs_code_assistant')->count());
    }

    public function test_document_summary_service_blocks_inaccessible_company_records(): void
    {
        $ownerOne = $this->createUser();
        $companyOne = $this->createCompany($ownerOne, ['company_type' => 'buyer']);
        $rfq = $this->createRfq($companyOne, $ownerOne);

        $ownerTwo = $this->createUser();
        $this->createCompany($ownerTwo, ['company_type' => 'buyer']);

        $this->expectException(ModelNotFoundException::class);

        app(DocumentSummaryService::class)->summarize('rfq', $rfq->id, $ownerTwo);
    }

    public function test_trade_assistant_returns_answer(): void
    {
        Http::fake([
            'https://custom.test/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'Review the RFQ expiry and confirm supplier compliance documents.',
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 90,
                    'completion_tokens' => 22,
                ],
            ], 200),
        ]);

        $this->createDefaultProvider();

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $this->setActiveCompany($buyerCompany);

        $response = $this->actingAs($buyerUser)->post(route('b2b.ai.trade-assistant.ask'), [
            'question' => 'What should I review before sending this RFQ?',
            'context_type' => 'rfq',
            'context_id' => 1,
        ]);

        $response->assertOk()->assertSee('confirm supplier compliance documents');
        $this->assertDatabaseHas('ai_requests', [
            'module' => 'b2b_trade_assistant',
            'company_id' => $buyerCompany->id,
            'status' => 'success',
        ]);
    }

    public function test_ai_tools_are_blocked_when_global_b2b_ai_is_disabled(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $this->setActiveCompany($buyerCompany);

        DB::table('business_settings')->updateOrInsert(
            ['type' => 'b2b_ai_tools_enabled'],
            ['value' => 0, 'created_at' => now(), 'updated_at' => now()]
        );

        $this->actingAs($buyerUser)
            ->post(route('b2b.ai.rfq-assistant.generate'), [
                'title' => 'Need stainless fasteners',
            ])
            ->assertForbidden();
    }

    protected function createDefaultProvider(): AIProviderSetting
    {
        return AIProviderSetting::query()->create([
            'provider' => 'custom',
            'name' => 'Custom Test Provider',
            'api_key' => 'test-key',
            'base_url' => 'https://custom.test',
            'model' => 'custom-mini',
            'is_active' => true,
            'is_default' => true,
            'settings' => [
                'path' => '/chat/completions',
            ],
        ]);
    }
}
