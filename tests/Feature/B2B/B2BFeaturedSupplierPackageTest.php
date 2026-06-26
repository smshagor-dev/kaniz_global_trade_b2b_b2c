<?php

namespace Tests\Feature\B2B;

use App\Models\B2BPackageRequest;
use Illuminate\Support\Facades\DB;

class B2BFeaturedSupplierPackageTest extends B2BFeatureTestCase
{
    public function test_supplier_can_submit_featured_homepage_package_request_with_payment_reference(): void
    {
        $supplierUser = $this->createSellerUser();
        $supplierCompany = $this->createCompany($supplierUser, [
            'company_type' => 'supplier',
            'public_profile_enabled' => true,
        ]);

        $packageId = DB::table('b2b_packages')->insertGetId([
            'name' => 'Featured Supplier Homepage',
            'package_for' => 'supplier',
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

        $this->actingAs($supplierUser)->post(route('b2b.packages.request', $packageId), [
            'payment_reference' => 'TXN-500-HOMEPAGE',
            'payment_notes' => 'Paid for homepage featured listing.',
            'note' => 'Please approve homepage placement.',
        ])->assertRedirect();

        $request = B2BPackageRequest::where('b2b_company_id', $supplierCompany->id)->firstOrFail();

        $this->assertSame('TXN-500-HOMEPAGE', $request->payment_reference);
        $this->assertSame('Paid for homepage featured listing.', $request->payment_notes);
        $this->assertSame('monthly', $request->billing_cycle);
        $this->assertNotNull($request->payment_submitted_at);
        $this->assertSame(500.0, (float) $request->amount);
    }
}
