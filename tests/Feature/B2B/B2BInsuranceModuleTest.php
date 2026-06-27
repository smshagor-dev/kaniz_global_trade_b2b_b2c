<?php

namespace Tests\Feature\B2B;

use App\Models\B2BInsuranceClaim;
use App\Models\B2BInsurancePolicy;
use App\Models\B2BInsuranceProvider;
use App\Models\B2BInsuranceQuote;
use App\Services\B2BInsuranceService;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Spatie\Permission\Middlewares\RoleMiddleware;
use Spatie\Permission\Middlewares\RoleOrPermissionMiddleware;
use Tests\Feature\B2B\Concerns\BuildsB2BData;
use Tests\TestCase;

class B2BInsuranceModuleTest extends TestCase
{
    use BuildsB2BData;
    use DatabaseTransactions;

    protected static bool $insuranceSchemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(PermissionMiddleware::class);
        $this->withoutMiddleware(RoleMiddleware::class);
        $this->withoutMiddleware(RoleOrPermissionMiddleware::class);

        if (!static::$insuranceSchemaReady && !Schema::hasTable('b2b_insurance_policies')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        static::$insuranceSchemaReady = Schema::hasTable('b2b_insurance_policies');
    }

    public function test_admin_can_create_insurance_provider_with_encrypted_credentials(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('admin.b2b.insurance.providers.store'), [
            'name' => 'Allianz Trade',
            'company' => 'Allianz Trade',
            'country' => 'Germany',
            'integration_mode' => 'api',
            'api_key' => 'super-secret-key',
            'api_secret' => 'super-secret-secret',
            'credentials' => ['client_id' => 'abc123'],
            'policy_types' => ['cargo_insurance'],
            'supported_countries' => ['Bangladesh', 'China'],
            'coverage' => ['cargo_insurance'],
            'is_default' => true,
            'is_active' => true,
        ]);

        $response->assertCreated();

        $provider = B2BInsuranceProvider::firstOrFail();

        $this->assertSame('super-secret-key', $provider->api_key);
        $this->assertSame('super-secret-secret', $provider->api_secret);
        $this->assertSame('abc123', $provider->credentials['client_id']);
    }

    public function test_buyer_can_generate_insurance_quote(): void
    {
        $buyer = $this->createUser();
        $company = $this->createCompany($buyer, ['company_type' => 'buyer']);
        $this->createCompanyMember($company, $buyer, 'finance_manager');
        $this->setActiveCompany($company);
        $this->createInsuranceProvider();

        $response = $this->actingAs($buyer)->post(route('b2b.insurance.quotes.store'), [
            'insurance_type' => 'cargo_insurance',
            'transport_mode' => 'sea_freight',
            'incoterm' => 'FOB',
            'origin_country' => 'China',
            'destination_country' => 'Bangladesh',
            'commodity' => 'Electronics',
            'shipment_value' => 25000,
            'coverage_amount' => 25000,
            'weight' => 1200,
            'volume' => 24,
            'currency' => 'USD',
        ]);

        $response->assertCreated();
        $quote = B2BInsuranceQuote::firstOrFail();

        $this->assertSame($company->id, $quote->buyer_company_id);
        $this->assertGreaterThan(0, (float) $quote->risk_score);
        $this->assertGreaterThan(0, (float) $quote->final_amount);
    }

    public function test_admin_can_issue_policy_from_quote_and_export_pdf(): void
    {
        $admin = $this->createAdminUser();
        $provider = $this->createInsuranceProvider();
        $quote = $this->createInsuranceQuote([
            'provider_id' => $provider->id,
            'buyer_company_id' => null,
            'supplier_company_id' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.b2b.insurance.policies.issue', $quote->id), [
            'coverage_plan' => 'all_risk',
            'status' => 'approved',
            'deductible_amount' => 200,
            'coverage_start' => now()->toDateString(),
            'coverage_end' => now()->addDays(60)->toDateString(),
        ]);

        $response->assertCreated();
        $policy = B2BInsurancePolicy::firstOrFail();

        $this->assertSame($quote->id, $policy->quote_id);

        $pdf = $this->actingAs($admin)->get(route('admin.b2b.insurance.policies.export', $policy->id));
        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');
    }

    public function test_claim_submission_and_status_transition_are_company_scoped(): void
    {
        $buyer = $this->createUser();
        $buyerCompany = $this->createCompany($buyer, ['company_type' => 'buyer']);
        $this->createCompanyMember($buyerCompany, $buyer, 'finance_manager');
        $this->setActiveCompany($buyerCompany);

        $supplier = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplier, ['company_type' => 'supplier']);

        $policy = $this->createInsurancePolicy([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
        ]);

        $claimResponse = $this->actingAs($buyer)->post(route('b2b.insurance.claims.store', $policy->id), [
            'claim_type' => 'damage',
            'summary' => 'Damaged shipment',
            'description' => 'Outer cartons were torn and wet.',
            'claim_amount' => 1500,
            'currency' => 'USD',
            'incident_at' => now()->subDay()->toDateTimeString(),
            'documents' => [
                ['document_type' => 'invoice', 'file_path' => 'uploads/insurance/invoice.pdf'],
                ['document_type' => 'damage_report', 'file_path' => 'uploads/insurance/damage-report.pdf'],
            ],
        ]);

        $claimResponse->assertCreated();
        $claim = B2BInsuranceClaim::firstOrFail();

        $this->assertSame($buyerCompany->id, $claim->claimant_company_id);
        $this->assertCount(2, $claim->documents);

        $admin = $this->createAdminUser();
        $statusResponse = $this->actingAs($admin)->post(route('admin.b2b.insurance.claims.status', $claim->id), [
            'status' => 'approved',
            'approved_amount' => 1000,
            'comment' => 'Reviewed and approved.',
        ]);

        $statusResponse->assertOk();
        $claim->refresh();

        $this->assertSame('approved', $claim->status);
        $this->assertSame(1000.0, (float) $claim->approved_amount);
    }

    public function test_shipment_sync_updates_linked_policy_status(): void
    {
        $buyer = $this->createUser();
        $buyerCompany = $this->createCompany($buyer, ['company_type' => 'buyer']);
        $supplier = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplier, ['company_type' => 'supplier']);

        $shipment = $this->createShipment([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'created_by' => $supplier->id,
            'status' => 'preparing',
        ]);
        $policy = $this->createInsurancePolicy([
            'buyer_company_id' => $buyerCompany->id,
            'supplier_company_id' => $supplierCompany->id,
            'shipment_id' => $shipment->id,
            'status' => 'approved',
        ]);

        $shipment->update(['status' => 'in_transit']);
        app(B2BInsuranceService::class)->syncPoliciesForShipment($shipment->fresh());

        $policy->refresh();

        $this->assertSame('in_transit', $policy->status);
    }
}
