<?php

namespace Tests\Feature\B2B;

use Illuminate\Support\Facades\DB;

class B2BSupplierDirectoryTest extends B2BFeatureTestCase
{
    public function test_supplier_directory_lists_only_public_approved_suppliers_and_supports_filters(): void
    {
        $category = $this->createCategory(['name' => 'Industrial']);

        $publicSupplierOwner = $this->createSellerUser();
        $publicSupplier = $this->createCompany($publicSupplierOwner, [
            'company_name' => 'Alpha Metals',
            'company_type' => 'supplier',
            'public_profile_enabled' => true,
            'verified_supplier_badge' => true,
            'featured_supplier' => true,
            'country' => 'Bangladesh',
        ]);
        $featuredPackageId = DB::table('b2b_packages')->insertGetId([
            'name' => 'Featured Supplier Homepage',
            'package_for' => 'supplier',
            'amount' => 500,
            'duration' => 30,
            'rfq_limit' => 0,
            'quotation_limit' => 0,
            'product_limit' => 0,
            'member_limit' => 10,
            'priority_listing' => 1,
            'featured_profile' => 1,
            'verified_badge' => 1,
            'analytics_access' => 1,
            'dedicated_support' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $publicSupplier->update([
            'b2b_package_id' => $featuredPackageId,
            'package_started_at' => now(),
            'package_expires_at' => now()->addDays(30),
        ]);
        $publicSupplier->categories()->attach($category->id);

        $hiddenSupplierOwner = $this->createSellerUser();
        $this->createCompany($hiddenSupplierOwner, [
            'company_name' => 'Hidden Plastics',
            'company_type' => 'supplier',
            'public_profile_enabled' => false,
            'country' => 'Bangladesh',
        ]);

        $response = $this->get(route('b2b.suppliers.index', [
            'keyword' => 'Alpha',
            'country' => 'Bangladesh',
            'verified_supplier_badge' => 1,
            'featured_supplier' => 1,
            'category' => $category->id,
        ]));

        $response->assertOk();
        $response->assertSee('Alpha Metals');
        $response->assertDontSee('Hidden Plastics');
    }

    public function test_supplier_profile_page_uses_public_slug(): void
    {
        $owner = $this->createSellerUser();
        $supplier = $this->createCompany($owner, [
            'company_name' => 'Bravo Textiles',
            'company_type' => 'manufacturer',
            'public_profile_enabled' => true,
            'business_scope' => 'Garments',
        ]);

        $response = $this->get(route('b2b.suppliers.show', $supplier->public_slug));

        $response->assertOk();
        $response->assertSee('Bravo Textiles');
        $response->assertSee('Garments');
    }

    public function test_expired_featured_supplier_plan_is_not_treated_as_homepage_featured(): void
    {
        $owner = $this->createSellerUser();
        $supplier = $this->createCompany($owner, [
            'company_name' => 'Expired Featured Supplier',
            'company_type' => 'supplier',
            'public_profile_enabled' => true,
            'featured_supplier' => true,
        ]);

        $featuredPackageId = DB::table('b2b_packages')->insertGetId([
            'name' => 'Expired Featured Package',
            'package_for' => 'supplier',
            'amount' => 500,
            'duration' => 30,
            'rfq_limit' => 0,
            'quotation_limit' => 0,
            'product_limit' => 0,
            'member_limit' => 10,
            'priority_listing' => 1,
            'featured_profile' => 1,
            'verified_badge' => 1,
            'analytics_access' => 1,
            'dedicated_support' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplier->update([
            'b2b_package_id' => $featuredPackageId,
            'package_started_at' => now()->subDays(40),
            'package_expires_at' => now()->subDay(),
        ]);

        $response = $this->get(route('b2b.suppliers.index', [
            'featured_supplier' => 1,
        ]));

        $response->assertOk();
        $response->assertDontSee('Expired Featured Supplier');
    }
}
