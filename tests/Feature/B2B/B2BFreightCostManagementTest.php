<?php

namespace Tests\Feature\B2B;

use App\Services\B2BFreightCostService;
use App\Services\B2BFreightPricingService;
use App\Services\B2BLandedCostService;

class B2BFreightCostManagementTest extends B2BFeatureTestCase
{
    public function test_landed_cost_service_calculates_totals(): void
    {
        $result = app(B2BLandedCostService::class)->calculate([
            'product_cost' => 1000,
            'freight_cost' => 200,
            'insurance' => 20,
            'duty' => 50,
            'vat_gst' => 30,
            'customs_fee' => 10,
            'port_charges' => 15,
            'local_delivery' => 25,
            'quantity' => 10,
            'target_margin_percent' => 20,
            'currency' => 'USD',
        ]);

        $this->assertSame(1350.0, $result['total_landed_cost']);
        $this->assertSame(135.0, $result['cost_per_unit']);
        $this->assertSame(1620.0, $result['suggested_selling_price']);
    }

    public function test_pricing_rule_service_matches_and_calculates_quote(): void
    {
        $rule = $this->createFreightPricingRule();

        $result = app(B2BFreightPricingService::class)->calculate([
            'freight_mode' => 'sea_freight',
            'service_type' => 'port_to_port',
            'origin_country' => 'China',
            'destination_country' => 'Bangladesh',
            'container_type' => '40HC',
            'incoterm' => 'FOB',
            'cargo_weight' => 1000,
            'cargo_volume' => 20,
        ]);

        $this->assertNotNull($result);
        $this->assertSame($rule->id, $result['rule']->id);
        $this->assertSame(711.15, $result['total_cost']);
    }

    public function test_cost_service_recalculates_totals_with_multi_currency_lines(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $quote = $this->createFreightQuote([
            'buyer_company_id' => $buyerCompany->id,
            'created_by' => $buyerUser->id,
            'currency' => 'USD',
            'base_currency' => 'USD',
        ]);

        $this->createFreightQuoteCost($quote, [
            'cost_type' => 'base_freight_cost',
            'amount' => 100,
            'currency' => 'USD',
            'exchange_rate_snapshot' => 1,
        ]);
        $this->createFreightQuoteCost($quote, [
            'cost_type' => 'pickup_cost',
            'amount' => 50,
            'currency' => 'EUR',
            'exchange_rate_snapshot' => 1.2,
        ]);

        app(B2BFreightCostService::class)->recalculate($quote->fresh());

        $this->assertDatabaseHas('b2b_freight_quotes', [
            'id' => $quote->id,
            'total_cost_base_currency' => 160.00,
            'freight_cost' => 160.00,
        ]);
    }

    public function test_hs_code_duty_and_vat_are_attached_to_quote_costs(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $this->createHsCode([
            'hs_code' => '620342',
            'country' => 'Bangladesh',
            'duty_percent' => 10,
            'vat_gst_percent' => 15,
        ]);
        $quote = $this->createFreightQuote([
            'buyer_company_id' => $buyerCompany->id,
            'created_by' => $buyerUser->id,
            'destination_country' => 'Bangladesh',
            'hs_code' => '620342',
            'freight_cost' => 100,
            'insurance_cost' => 20,
        ]);

        app(B2BFreightCostService::class)->applyHsCode($quote->fresh());

        $this->assertDatabaseHas('b2b_freight_quote_costs', [
            'freight_quote_id' => $quote->id,
            'cost_type' => 'customs_duty',
            'amount' => 12.00,
        ]);
        $this->assertDatabaseHas('b2b_freight_quote_costs', [
            'freight_quote_id' => $quote->id,
            'cost_type' => 'vat_gst',
            'amount' => 19.80,
        ]);
    }

    public function test_supplier_can_add_cost_line_from_dashboard(): void
    {
        $buyerUser = $this->createUser();
        $buyerCompany = $this->createCompany($buyerUser, ['company_type' => 'buyer']);
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, ['company_type' => 'supplier']);
        $this->createCompanyMember($supplierCompany, $supplierUser, 'logistics_manager');
        $this->setActiveCompany($supplierCompany);

        $quote = $this->createFreightQuote([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'created_by' => $buyerUser->id,
        ]);

        $this->actingAs($supplierUser)
            ->post(route('seller.b2b.freight-quotes.cost-lines.store', $quote->id), [
                'cost_type' => 'delivery_cost',
                'description' => 'Last mile delivery',
                'amount' => 45,
                'currency' => 'USD',
                'exchange_rate_snapshot' => 1,
                'payer' => 'buyer',
                'is_billable' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('b2b_freight_quote_costs', [
            'freight_quote_id' => $quote->id,
            'cost_type' => 'delivery_cost',
            'amount' => 45.00,
        ]);
    }
}
