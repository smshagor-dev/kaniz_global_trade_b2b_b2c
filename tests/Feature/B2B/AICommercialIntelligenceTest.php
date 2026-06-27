<?php

namespace Tests\Feature\B2B;

use App\Jobs\GenerateCurrencyAnalysisJob;
use App\Jobs\GenerateDashboardInsightJob;
use App\Models\AIBuyerRisk;
use App\Models\AICurrencyAnalysis;
use App\Models\AIDashboardInsight;
use App\Models\AIFreightRecommendation;
use App\Models\AINotificationEvent;
use App\Models\AIPriceRecommendation;
use App\Models\AISupplierRisk;
use App\Models\AITradeFinanceRecommendation;
use App\Models\B2BCompany;
use App\Services\AI\AIDashboardInsightService;
use App\Services\AI\AINotificationService;
use Illuminate\Support\Facades\Bus;

class AICommercialIntelligenceTest extends B2BFeatureTestCase
{
    public function test_price_recommendation_uses_deterministic_fallback_without_provider(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $this->setActiveCompany($buyerCompany);

        $response = $this->actingAs($buyerUser)->post(route('b2b.ai.price-recommendation'), [
            'currency' => 'USD',
            'country' => 'Bangladesh',
            'supplier_cost' => 100,
            'shipping_cost' => 20,
            'customs_cost' => 10,
            'tax_cost' => 5,
            'vat_cost' => 15,
            'platform_fee' => 5,
            'profit_margin' => 0.25,
        ]);

        $response->assertOk()->assertSee('DETERMINISTIC');
        $this->assertDatabaseHas('ai_price_recommendations', [
            'company_id' => $buyerCompany->id,
            'source' => 'deterministic',
            'provider' => null,
        ]);
    }

    public function test_supplier_risk_assessment_is_stored(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $this->setActiveCompany($buyerCompany);

        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
            'public_profile_enabled' => true,
            'response_rate' => 42,
            'verification_status' => 'approved',
        ]);

        $response = $this->actingAs($buyerUser)->post(route('b2b.ai.supplier-risk'), [
            'supplier_company_id' => $supplierCompany->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('ai_supplier_risk', [
            'company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
        ]);
    }

    public function test_buyer_risk_assessment_is_stored(): void
    {
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $supplierCompany = $this->activatePackageForCompany($supplierCompany);
        $this->setActiveCompany($supplierCompany);

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);

        $response = $this->actingAs($supplierUser)->post(route('b2b.ai.buyer-risk'), [
            'buyer_company_id' => $buyerCompany->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('ai_buyer_risk', [
            'company_id' => $supplierCompany->id,
            'buyer_company_id' => $buyerCompany->id,
        ]);
    }

    public function test_freight_recommendation_is_generated_for_owned_quote(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $quote = $this->createFreightQuote([
            'buyer_company_id' => $buyerCompany->id,
            'freight_cost' => 900,
            'insurance_cost' => 50,
            'customs_estimate' => 100,
            'total_cost' => 1050,
        ]);
        $this->setActiveCompany($buyerCompany);

        $response = $this->actingAs($buyerUser)->post(route('b2b.ai.freight-recommendation'), [
            'freight_quote_id' => $quote->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('ai_freight_recommendations', [
            'company_id' => $buyerCompany->id,
            'freight_quote_id' => $quote->id,
        ]);
    }

    public function test_currency_analysis_can_be_queued(): void
    {
        Bus::fake();

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $this->setActiveCompany($buyerCompany);

        $this->actingAs($buyerUser)->post(route('b2b.ai.currency-analysis'), [
            'currency_code' => 'USD',
            'amount' => 1500,
            'queue' => 1,
        ])->assertOk();

        Bus::assertDispatched(GenerateCurrencyAnalysisJob::class, fn (GenerateCurrencyAnalysisJob $job) => $job->companyId === $buyerCompany->id);
    }

    public function test_dashboard_insight_generation_and_notification_generation_work(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $this->setActiveCompany($buyerCompany);

        app(AIDashboardInsightService::class)->generateForCompany($buyerCompany, $buyerUser);
        AISupplierRisk::query()->create([
            'company_id' => $buyerCompany->id,
            'user_id' => $buyerUser->id,
            'supplier_company_id' => $buyerCompany->id,
            'risk_score' => 88,
            'risk_level' => 'critical',
            'confidence_score' => 82,
        ]);

        app(AINotificationService::class)->generateForCompany($buyerCompany);

        $this->assertGreaterThan(0, AIDashboardInsight::count());
        $this->assertGreaterThan(0, AINotificationEvent::count());
    }

    public function test_trade_finance_recommendation_is_stored(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $rfq = $this->createRfq($buyerCompany, $buyerUser);
        $quotation = $this->createQuotation($rfq, $supplierCompany, $supplierUser);
        $purchaseOrder = $this->createPurchaseOrder($quotation);
        $this->setActiveCompany($buyerCompany);

        $response = $this->actingAs($buyerUser)->post(route('b2b.ai.trade-finance'), [
            'purchase_order_id' => $purchaseOrder->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('ai_trade_finance_recommendations', [
            'company_id' => $buyerCompany->id,
            'reference_id' => $purchaseOrder->id,
        ]);
    }

    public function test_permission_scope_blocks_foreign_freight_quote_access(): void
    {
        $ownerOne = $this->createUser();
        $companyOne = $this->createCompany($ownerOne, ['company_type' => 'buyer']);
        $companyOne = $this->activatePackageForCompany($companyOne);
        $foreignQuote = $this->createFreightQuote(['buyer_company_id' => $companyOne->id]);

        $ownerTwo = $this->createUser();
        $companyTwo = $this->createCompany($ownerTwo, ['company_type' => 'buyer']);
        $companyTwo = $this->activatePackageForCompany($companyTwo);
        $this->setActiveCompany($companyTwo);

        $this->actingAs($ownerTwo)->post(route('b2b.ai.freight-recommendation'), [
            'freight_quote_id' => $foreignQuote->id,
        ])->assertNotFound();
    }

    public function test_dashboard_queue_dispatches_job(): void
    {
        Bus::fake();

        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $buyerCompany = $this->activatePackageForCompany($buyerCompany);
        $this->setActiveCompany($buyerCompany);

        $this->actingAs($buyerUser)->get(route('b2b.ai.dashboard-insights', ['queue' => 1]))->assertOk();

        Bus::assertDispatched(GenerateDashboardInsightJob::class, fn (GenerateDashboardInsightJob $job) => $job->companyId === $buyerCompany->id);
    }
}
