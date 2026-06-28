<?php

namespace Tests\Feature\B2B;

use App\Models\BusinessSetting;
use App\Models\FraudCheck;
use App\Models\UserDeviceLog;
use App\Models\VerificationDocument;
use App\Services\AI\AIRequestService;
use App\Services\Fraud\FraudScoringService;
use App\Services\Fraud\FraudSettingsService;
use Illuminate\Support\Facades\File;

class FraudModuleTest extends B2BFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(FraudSettingsService::class)->update(
            app(FraudSettingsService::class)->defaults()
        );
    }

    public function test_supplier_risk_scoring_applies_duplicate_device_and_rejected_document_signals(): void
    {
        $supplier = $this->createSellerUser([
            'email' => 'fraud-supplier@example.test',
            'phone' => '01911111111',
        ]);

        $this->createCompany($supplier, [
            'company_type' => 'supplier',
            'company_name' => 'Demo Export House',
            'legal_name' => 'Another Legal Name LLC',
            'bank_account_name' => 'Random Personal Name',
        ]);

        $sharedDeviceHash = 'device-shared-123';

        foreach (range(1, 3) as $index) {
            $user = $index === 1
                ? $supplier
                : $this->createUser([
                    'email' => "device-user-{$index}@example.test",
                    'phone' => '0180000000' . $index,
                ]);

            UserDeviceLog::query()->create([
                'user_id' => $user->id,
                'ip_address' => '203.0.113.10',
                'device_hash' => $sharedDeviceHash,
                'user_agent' => 'Fraud test browser',
                'login_at' => now()->subMinutes($index),
            ]);
        }

        VerificationDocument::query()->create([
            'user_id' => $supplier->id,
            'user_type' => 'supplier',
            'document_type' => 'business_license',
            'file_path' => 'uploads/b2b_companies/fake-license.pdf',
            'original_name' => 'fake-license.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'rejected',
            'rejection_reason' => 'Fake trade license submitted for verification.',
        ]);

        $check = app(FraudScoringService::class)->runForUser($supplier, [
            'run_ai' => false,
        ]);

        $this->assertSame('supplier', $check->user_type);
        $this->assertSame('restricted', $check->status);
        $this->assertSame(100, $check->final_score);
        $this->assertTrue(collect($check->reasons)->contains(fn ($reason) => ($reason['code'] ?? null) === 'duplicate_device_many_accounts'));
        $this->assertTrue(collect($check->reasons)->contains(fn ($reason) => ($reason['code'] ?? null) === 'supplier_document_rejected'));
    }

    public function test_invalid_ai_response_falls_back_to_rule_based_score(): void
    {
        app(FraudSettingsService::class)->update(array_merge(
            app(FraudSettingsService::class)->defaults(),
            [
                'ai_enabled' => true,
            ]
        ));

        $buyer = $this->createUser([
            'email_verified_at' => null,
            'phone' => null,
            'address' => null,
            'city' => null,
            'country' => null,
        ]);

        $this->mock(AIRequestService::class, function ($mock): void {
            $mock->shouldReceive('request')
                ->once()
                ->andReturn([
                    'content' => 'not valid json',
                    'provider' => 'test-provider',
                    'model' => 'test-model',
                ]);
        });

        $check = app(FraudScoringService::class)->runForUser($buyer);

        $this->assertSame('rule_based', $check->source);
        $this->assertNull($check->ai_score);
        $this->assertSame($check->rule_score, $check->final_score);
    }

    public function test_manual_review_can_block_and_then_restore_user(): void
    {
        $admin = $this->createAdminUser(['email' => 'fraud-admin@example.test']);
        $buyer = $this->createUser([
            'email' => 'manual-review@example.test',
        ]);

        app(FraudScoringService::class)->runForUser($buyer, ['run_ai' => false]);

        $blocked = app(FraudScoringService::class)->manualReview($buyer, [
            'manual_score' => 100,
            'status' => 'blocked',
            'summary' => 'Blocked for confirmed fraud pattern.',
            'reason' => 'Manual fraud review blocked the account.',
            'reviewed_by' => $admin->id,
        ]);

        $this->assertSame('manual', $blocked->source);
        $this->assertSame('blocked', $blocked->status);
        $this->assertSame(1, $buyer->fresh()->banned);

        $approved = app(FraudScoringService::class)->manualReview($buyer->fresh(), [
            'manual_score' => 20,
            'status' => 'approved',
            'summary' => 'Restored after successful review.',
            'reason' => 'Manual fraud review restored the account.',
            'reviewed_by' => $admin->id,
        ]);

        $this->assertSame('approved', $approved->status);
        $this->assertSame(0, $buyer->fresh()->banned);
    }

    public function test_high_risk_buyer_cannot_create_rfq(): void
    {
        $buyer = $this->createUser();
        $company = $this->activatePackageForCompany($this->createCompany($buyer, [
            'company_type' => 'buyer',
            'verification_status' => 'approved',
        ]));
        $supplier = $this->createSellerUser(['email' => 'rfq-supplier@example.test']);
        $supplierCompany = $this->createCompany($supplier, [
            'company_type' => 'supplier',
            'verification_status' => 'approved',
        ]);

        FraudCheck::query()->create([
            'user_id' => $buyer->id,
            'user_type' => 'buyer',
            'risk_score' => 95,
            'risk_level' => 'critical',
            'source' => 'manual',
            'status' => 'restricted',
            'summary' => 'High risk buyer.',
            'reasons' => [['code' => 'reported_by_users_high']],
            'final_score' => 95,
            'manual_score' => 95,
        ]);

        $this->setActiveCompany($company);

        $this->actingAs($buyer)->post(route('b2b.rfqs.store'), [
            'supplier_company_id' => $supplierCompany->id,
            'title' => 'Blocked RFQ',
            'description' => 'This RFQ should not be created.',
            'quantity' => 100,
            'unit' => 'pcs',
            'target_price' => 10,
            'currency' => 'USD',
            'incoterm' => 'FOB',
            'destination_country' => 'Bangladesh',
            'destination_city' => 'Dhaka',
            'expires_at' => now()->addDays(5)->toDateTimeString(),
        ])->assertRedirect(route('b2b.company.show'));

        $this->assertDatabaseMissing('b2b_rfqs', [
            'user_id' => $buyer->id,
            'title' => 'Blocked RFQ',
        ]);
    }

    public function test_trust_status_endpoint_hides_internal_scores(): void
    {
        $buyer = $this->createUser([
            'email' => 'trust-status@example.test',
        ]);

        FraudCheck::query()->create([
            'user_id' => $buyer->id,
            'user_type' => 'buyer',
            'risk_score' => 55,
            'risk_level' => 'medium',
            'source' => 'rule_based',
            'status' => 'needs_review',
            'summary' => 'Review pending.',
            'reasons' => [['code' => 'rfq_volume_high']],
            'rule_score' => 55,
            'final_score' => 55,
        ]);

        $response = $this->actingAs($buyer)->getJson(route('dashboard.trust-status'));

        $response->assertOk()
            ->assertJsonStructure([
                'status' => ['label', 'tone'],
                'documents_pending',
            ])
            ->assertJsonMissingPath('risk_score')
            ->assertJsonMissingPath('status.risk_score');
    }

    public function test_admin_can_download_public_company_document_from_fraud_center(): void
    {
        $admin = $this->createAdminUser();
        $buyer = $this->createUser(['email' => 'doc-buyer@example.test']);
        $relativePath = 'uploads/b2b_companies/test-fraud-document.pdf';
        $absolutePath = public_path($relativePath);

        File::ensureDirectoryExists(dirname($absolutePath));
        file_put_contents($absolutePath, 'fraud document');

        $document = VerificationDocument::query()->create([
            'user_id' => $buyer->id,
            'user_type' => 'buyer',
            'document_type' => 'business_license',
            'file_path' => $relativePath,
            'original_name' => 'test-fraud-document.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.fraud.documents.download', $document->id))
            ->assertOk();
    }

    public function test_seller_registration_creates_fraud_documents_and_runs_fraud_check(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'seller_registration_verify'], ['value' => '0']);

        $this->post(route('shops.store'), [
            'name' => 'Fraud Seller',
            'email' => 'fraud-seller@example.test',
            'phone' => '01512345678',
            'password' => 'password',
            'password_confirmation' => 'password',
            'shop_name' => 'Fraud Seller Shop',
            'address' => 'Dhaka',
            'certificate_number' => 'SELLER-123',
            'certificate' => \Illuminate\Http\UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
            'id_card' => \Illuminate\Http\UploadedFile::fake()->create('id-card.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('home'));

        $seller = \App\Models\User::query()->where('email', 'fraud-seller@example.test')->firstOrFail();

        $this->assertDatabaseHas('verification_documents', [
            'user_id' => $seller->id,
            'user_type' => 'supplier',
            'document_type' => 'business_license',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('verification_documents', [
            'user_id' => $seller->id,
            'user_type' => 'supplier',
            'document_type' => 'national_id',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('fraud_checks', [
            'user_id' => $seller->id,
            'user_type' => 'supplier',
        ]);
    }

    public function test_consumer_registration_runs_fraud_check_and_can_upload_verification_document(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'email_verification'], ['value' => '0']);

        $this->post(route('register'), [
            'name' => 'Consumer Fraud User',
            'email' => 'consumer-fraud@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('home'));

        $consumer = \App\Models\User::query()->where('email', 'consumer-fraud@example.test')->firstOrFail();

        $this->assertDatabaseHas('fraud_checks', [
            'user_id' => $consumer->id,
            'user_type' => 'buyer',
        ]);

        $this->actingAs($consumer)->post(route('dashboard.verification.documents.store'), [
            'document_type' => 'national_id',
            'document' => \Illuminate\Http\UploadedFile::fake()->create('consumer-id.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertDatabaseHas('verification_documents', [
            'user_id' => $consumer->id,
            'user_type' => 'buyer',
            'document_type' => 'national_id',
            'status' => 'pending',
        ]);
    }
}
