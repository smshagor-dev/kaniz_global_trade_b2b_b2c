<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $packages = [
            [
                'name' => 'Buyer Starter',
                'package_for' => 'buyer',
                'amount' => 0,
                'duration' => 30,
                'rfq_limit' => 10,
                'quotation_limit' => 0,
                'product_limit' => 0,
                'member_limit' => 3,
                'priority_listing' => false,
                'featured_profile' => false,
                'verified_badge' => false,
                'analytics_access' => false,
                'dedicated_support' => false,
                'highlight_text' => 'Free entry',
                'description' => 'For new sourcing teams testing B2B purchasing workflows.',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Buyer Business',
                'package_for' => 'buyer',
                'amount' => 199,
                'duration' => 90,
                'rfq_limit' => 75,
                'quotation_limit' => 0,
                'product_limit' => 0,
                'member_limit' => 12,
                'priority_listing' => true,
                'featured_profile' => false,
                'verified_badge' => false,
                'analytics_access' => true,
                'dedicated_support' => false,
                'highlight_text' => 'Popular',
                'description' => 'For active buying offices managing more RFQs and larger teams.',
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Buyer Enterprise',
                'package_for' => 'buyer',
                'amount' => 499,
                'duration' => 180,
                'rfq_limit' => 0,
                'quotation_limit' => 0,
                'product_limit' => 0,
                'member_limit' => 30,
                'priority_listing' => true,
                'featured_profile' => true,
                'verified_badge' => true,
                'analytics_access' => true,
                'dedicated_support' => true,
                'highlight_text' => 'Enterprise',
                'description' => 'For procurement organizations with unlimited sourcing demand.',
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Supplier Standard',
                'package_for' => 'supplier',
                'amount' => 0,
                'duration' => 30,
                'rfq_limit' => 0,
                'quotation_limit' => 15,
                'product_limit' => 20,
                'member_limit' => 3,
                'priority_listing' => false,
                'featured_profile' => false,
                'verified_badge' => false,
                'analytics_access' => false,
                'dedicated_support' => false,
                'highlight_text' => 'Free entry',
                'description' => 'For small suppliers starting with wholesale catalog and quotation access.',
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'Supplier Verified',
                'package_for' => 'supplier',
                'amount' => 299,
                'duration' => 90,
                'rfq_limit' => 0,
                'quotation_limit' => 100,
                'product_limit' => 100,
                'member_limit' => 12,
                'priority_listing' => true,
                'featured_profile' => true,
                'verified_badge' => true,
                'analytics_access' => true,
                'dedicated_support' => false,
                'highlight_text' => 'Best seller',
                'description' => 'For growth-stage exporters needing visibility, trust, and larger response volume.',
                'sort_order' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Supplier Gold',
                'package_for' => 'supplier',
                'amount' => 799,
                'duration' => 180,
                'rfq_limit' => 0,
                'quotation_limit' => 0,
                'product_limit' => 0,
                'member_limit' => 30,
                'priority_listing' => true,
                'featured_profile' => true,
                'verified_badge' => true,
                'analytics_access' => true,
                'dedicated_support' => true,
                'highlight_text' => 'Gold supplier',
                'description' => 'For serious manufacturers wanting unlimited scale and premium support.',
                'sort_order' => 60,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $package) {
            DB::table('b2b_packages')->updateOrInsert(
                ['name' => $package['name'], 'package_for' => $package['package_for']],
                array_merge($package, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        DB::table('b2b_packages')->whereIn('name', [
            'Buyer Starter',
            'Buyer Business',
            'Buyer Enterprise',
            'Supplier Standard',
            'Supplier Verified',
            'Supplier Gold',
        ])->delete();
    }
};
