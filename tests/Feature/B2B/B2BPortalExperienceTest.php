<?php

namespace Tests\Feature\B2B;

use App\Models\BusinessSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class B2BPortalExperienceTest extends B2BFeatureTestCase
{
    public function test_header_exposes_b2b_portal_links_without_breaking_seller_entry(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee(route('b2b.portal.become-supplier'), false)
            ->assertSee(route('buyer.portal'), false)
            ->assertSee(route('b2b.suppliers.index'), false)
            ->assertSee(route(get_setting('seller_registration_verify') === '1' ? 'shop-reg.verification' : 'shops.create'), false);
    }

    public function test_become_supplier_and_portal_routes_exist(): void
    {
        $this->assertTrue(Route::has('b2b.portal.become-supplier'));
        $this->assertTrue(Route::has('buyer.portal'));
        $this->assertTrue(Route::has('supplier.portal'));
        $this->assertTrue(Route::has('buyer.login'));
        $this->assertTrue(Route::has('supplier.login'));
        $this->assertTrue(Route::has('buyer.onboarding'));
        $this->assertTrue(Route::has('supplier.onboarding'));
        $this->assertTrue(Route::has('buyer.dashboard'));
        $this->assertTrue(Route::has('supplier.dashboard'));
    }

    public function test_guest_can_open_supplier_registration_entry(): void
    {
        $this->get(route('b2b.portal.become-supplier'))
            ->assertOk()
            ->assertSee('Register as a Supplier')
            ->assertSee('Personal Info')
            ->assertSee('Company Info')
            ->assertSee('Verification Docs')
            ->assertSee(route('supplier.login'), false);
    }

    public function test_guest_can_open_buyer_registration_entry(): void
    {
        $this->get(route('buyer.portal'))
            ->assertOk()
            ->assertSee('Register as a Buyer')
            ->assertSee('Personal Info')
            ->assertSee('Company Info')
            ->assertSee('Verification Docs')
            ->assertSee(route('buyer.login'), false);
    }

    public function test_buyer_portal_redirects_to_buyer_dashboard_for_approved_buyer(): void
    {
        $buyer = $this->createUser();
        $company = $this->activatePackageForCompany($this->createCompany($buyer, ['company_type' => 'buyer']));
        $this->setActiveCompany($company);

        $this->actingAs($buyer)
            ->get(route('buyer.portal'))
            ->assertRedirect(route('buyer.dashboard'));
    }

    public function test_supplier_portal_redirects_to_supplier_dashboard_for_approved_supplier(): void
    {
        $supplier = $this->createSellerUser();
        $company = $this->activatePackageForCompany($this->createCompany($supplier, ['company_type' => 'supplier']));
        $this->setActiveCompany($company);

        $this->actingAs($supplier)
            ->get(route('supplier.portal'))
            ->assertRedirect(route('supplier.dashboard'));
    }

    public function test_buyer_onboarding_creates_buyer_company(): void
    {
        DB::table('b2b_verification_requirements')->delete();

        $user = $this->createUser();

        $this->actingAs($user)->post(route('b2b.company.store'), [
            'company_name' => 'Buyer Onboarding Company',
            'company_type' => 'buyer',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'phone' => '01700000000',
            'business_email' => 'buyer-portal@example.test',
            'after_submit_route' => 'buyer.dashboard',
        ])->assertRedirect(route('buyer.dashboard'));

        $this->assertDatabaseHas('b2b_companies', [
            'user_id' => $user->id,
            'company_name' => 'Buyer Onboarding Company',
            'company_type' => 'buyer',
        ]);
    }

    public function test_supplier_onboarding_creates_supplier_company(): void
    {
        DB::table('b2b_verification_requirements')->delete();

        $user = $this->createSellerUser(['email' => 'portal-supplier@example.test']);

        $this->actingAs($user)->post(route('b2b.company.store'), [
            'company_name' => 'Supplier Onboarding Company',
            'company_type' => 'supplier',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'phone' => '01800000000',
            'business_email' => 'supplier-portal@example.test',
            'after_submit_route' => 'supplier.dashboard',
        ])->assertRedirect(route('supplier.dashboard'));

        $this->assertDatabaseHas('b2b_companies', [
            'user_id' => $user->id,
            'company_name' => 'Supplier Onboarding Company',
            'company_type' => 'supplier',
        ]);
    }

    public function test_guest_can_register_supplier_from_public_entry(): void
    {
        $this->post(route('b2b.portal.become-supplier.register'), [
            'name' => 'Supplier Guest',
            'email' => 'supplier-guest@example.test',
            'phone' => '01900000000',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Supplier Guest Company',
            'company_type' => 'supplier',
            'business_email' => 'trade@supplier-guest.example.test',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'address' => 'Dhaka',
            'trade_license_file' => UploadedFile::fake()->create('trade-license.pdf', 100, 'application/pdf'),
            'tax_document_file' => UploadedFile::fake()->create('tax-document.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('supplier.portal'));

        $this->assertDatabaseHas('users', [
            'email' => 'supplier-guest@example.test',
            'user_type' => 'seller',
        ]);

        $this->assertDatabaseHas('b2b_companies', [
            'company_name' => 'Supplier Guest Company',
            'company_type' => 'supplier',
            'business_email' => 'trade@supplier-guest.example.test',
        ]);
    }

    public function test_guest_can_register_buyer_from_public_entry(): void
    {
        $this->post(route('buyer.portal.register'), [
            'name' => 'Buyer Guest',
            'email' => 'buyer-guest@example.test',
            'phone' => '01600000000',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Buyer Guest Company',
            'company_type' => 'buyer',
            'business_email' => 'trade@buyer-guest.example.test',
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'address' => 'Dhaka',
            'trade_license_file' => UploadedFile::fake()->create('trade-license.pdf', 100, 'application/pdf'),
            'tax_document_file' => UploadedFile::fake()->create('tax-document.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('buyer.portal'));

        $this->assertDatabaseHas('users', [
            'email' => 'buyer-guest@example.test',
            'user_type' => 'customer',
        ]);

        $this->assertDatabaseHas('b2b_companies', [
            'company_name' => 'Buyer Guest Company',
            'company_type' => 'buyer',
            'business_email' => 'trade@buyer-guest.example.test',
        ]);
    }

    public function test_guest_can_open_seller_registration_in_three_steps(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'seller_registration_verify'], ['value' => '0']);
        Cache::forget('business_settings');

        $this->get(route('shops.create'))
            ->assertOk()
            ->assertSee('Register Your Shop')
            ->assertSee('Personal Info')
            ->assertSee('Shop Info')
            ->assertSee('Verification Docs');
    }

    public function test_guest_can_register_seller_with_initial_verification_documents(): void
    {
        BusinessSetting::updateOrCreate(['type' => 'seller_registration_verify'], ['value' => '0']);
        Cache::forget('business_settings');

        $this->post(route('shops.store'), [
            'name' => 'Seller Guest',
            'email' => 'seller-guest@example.test',
            'phone' => '01500000000',
            'password' => 'password',
            'password_confirmation' => 'password',
            'shop_name' => 'Seller Guest Shop',
            'address' => 'Dhaka',
            'certificate_number' => 'BIN-123456',
            'certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
            'id_card' => UploadedFile::fake()->create('id-card.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'email' => 'seller-guest@example.test',
            'user_type' => 'seller',
        ]);

        $this->assertDatabaseHas('shops', [
            'name' => 'Seller Guest Shop',
            'registration_approval' => 0,
        ]);
    }

    public function test_old_buyer_route_uses_buyer_portal_layout(): void
    {
        $buyer = $this->createUser();
        $company = $this->activatePackageForCompany($this->createCompany($buyer, ['company_type' => 'buyer']));
        $this->setActiveCompany($company);

        $this->actingAs($buyer)
            ->get(route('b2b.rfqs.index'))
            ->assertOk()
            ->assertSee('Buyer Portal');
    }

    public function test_old_supplier_route_uses_supplier_portal_layout(): void
    {
        $supplier = $this->createSellerUser();
        $company = $this->activatePackageForCompany($this->createCompany($supplier, ['company_type' => 'supplier']));
        $this->setActiveCompany($company);

        $this->actingAs($supplier)
            ->get(route('seller.b2b.rfqs.index'))
            ->assertOk()
            ->assertSee('Supplier Portal');
    }

    public function test_admin_user_is_redirected_to_admin_dashboard_from_generic_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_user_can_open_seller_dashboard_route(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('seller.dashboard'))
            ->assertOk();
    }

    public function test_admin_user_can_open_buyer_dashboard_preview(): void
    {
        $admin = $this->createAdminUser();
        $this->createCompany($this->createUser(), ['company_type' => 'buyer']);

        $this->actingAs($admin)
            ->get(route('buyer.dashboard'))
            ->assertOk()
            ->assertSee('Buyer Portal');
    }

    public function test_admin_user_can_open_supplier_dashboard_preview(): void
    {
        $admin = $this->createAdminUser();
        $supplier = $this->createSellerUser(['email' => 'preview-supplier@example.test']);
        $this->createCompany($supplier, ['company_type' => 'supplier']);

        $this->actingAs($admin)
            ->get(route('supplier.dashboard'))
            ->assertOk()
            ->assertSee('Supplier Portal');
    }

    public function test_supplier_company_cannot_use_buyer_portal(): void
    {
        $supplier = $this->createSellerUser();
        $company = $this->activatePackageForCompany($this->createCompany($supplier, ['company_type' => 'supplier']));
        $this->setActiveCompany($company);

        $this->actingAs($supplier)
            ->get(route('buyer.portal'))
            ->assertRedirect(route('buyer.onboarding'));
    }

    public function test_buyer_company_cannot_use_supplier_portal(): void
    {
        $buyer = $this->createUser();
        $company = $this->activatePackageForCompany($this->createCompany($buyer, ['company_type' => 'buyer']));
        $this->setActiveCompany($company);

        $this->actingAs($buyer)
            ->get(route('supplier.portal'))
            ->assertRedirect(route('supplier.onboarding'));
    }
}
