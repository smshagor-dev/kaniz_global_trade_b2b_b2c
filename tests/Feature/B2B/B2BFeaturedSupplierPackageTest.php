<?php

namespace Tests\Feature\B2B;

use App\Http\Controllers\B2BPackageController;
use App\Models\B2BPackageRequest;
use Illuminate\Support\Facades\DB;

class B2BFeaturedSupplierPackageTest extends B2BFeatureTestCase
{
    public function test_supplier_can_start_featured_package_online_checkout(): void
    {
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
            'public_profile_enabled' => true,
        ]);

        $packageId = DB::table('b2b_packages')->insertGetId([
            'name' => 'Featured Supplier Homepage',
            'package_for' => 'supplier',
            'package_type' => 'supplier_featured',
            'amount' => 500,
            'duration' => 30,
            'rfq_limit' => 0,
            'quotation_limit' => 0,
            'product_limit' => 0,
            'member_limit' => 20,
            'priority_listing' => 1,
            'featured_profile' => 1,
            'verified_badge' => 1,
            'analytics_access' => 1,
            'dedicated_support' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->setActiveCompany($supplierCompany);

        $response = $this->actingAs($supplierUser)->post(route('b2b.packages.purchase', $packageId), [
            'payment_option' => 'stripe',
        ]);

        $response->assertOk();
        $response->assertViewIs('frontend.payment.stripe');
        $response->assertSessionHas('payment_type', 'seller_package_payment');
        $response->assertSessionHas('payment_data.b2b_package_id', $packageId);
        $response->assertSessionHas('payment_data.b2b_company_id', $supplierCompany->id);
    }

    public function test_supplier_featured_package_is_activated_after_successful_payment(): void
    {
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
            'public_profile_enabled' => true,
        ]);

        $packageId = DB::table('b2b_packages')->insertGetId([
            'name' => 'Featured Supplier Homepage',
            'package_for' => 'supplier',
            'package_type' => 'supplier_featured',
            'amount' => 500,
            'duration' => 30,
            'rfq_limit' => 0,
            'quotation_limit' => 0,
            'product_limit' => 0,
            'member_limit' => 20,
            'priority_listing' => 1,
            'featured_profile' => 1,
            'verified_badge' => 1,
            'analytics_access' => 1,
            'dedicated_support' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = app(B2BPackageController::class);
        $this->actingAs($supplierUser);

        $response = $controller->purchasePaymentDone([
            'b2b_package_id' => $packageId,
            'b2b_company_id' => $supplierCompany->id,
            'b2b_user_id' => $supplierUser->id,
            'payment_method' => 'stripe',
        ], '{"status":"Success","transaction":"PKG-500"}');

        $this->assertSame(route('b2b.packages.index'), $response->getTargetUrl());

        $supplierCompany->refresh();

        $this->assertSame($packageId, $supplierCompany->featured_b2b_package_id);
        $this->assertNotNull($supplierCompany->featured_package_expires_at);

        $request = B2BPackageRequest::where('b2b_company_id', $supplierCompany->id)->firstOrFail();

        $this->assertSame('supplier_featured', $request->request_type);
        $this->assertSame('approved', $request->status);
        $this->assertNotNull($request->payment_submitted_at);
        $this->assertNotNull($request->approved_at);
        $this->assertSame(500.0, (float) $request->amount);
    }
}
