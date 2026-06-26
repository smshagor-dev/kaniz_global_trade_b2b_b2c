<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_package_requests')) {
            Schema::table('b2b_package_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_package_requests', 'billing_cycle')) {
                    $table->string('billing_cycle', 40)->nullable()->after('amount');
                }
                if (!Schema::hasColumn('b2b_package_requests', 'payment_reference')) {
                    $table->string('payment_reference')->nullable()->after('note');
                }
                if (!Schema::hasColumn('b2b_package_requests', 'payment_notes')) {
                    $table->text('payment_notes')->nullable()->after('payment_reference');
                }
                if (!Schema::hasColumn('b2b_package_requests', 'payment_submitted_at')) {
                    $table->timestamp('payment_submitted_at')->nullable()->after('payment_notes');
                }
            });
        }

        if (Schema::hasTable('b2b_packages')) {
            DB::table('b2b_packages')->updateOrInsert(
                [
                    'name' => 'Featured Supplier Homepage',
                    'package_for' => 'supplier',
                ],
                [
                    'amount' => 500,
                    'duration' => 30,
                    'rfq_limit' => 0,
                    'quotation_limit' => 0,
                    'product_limit' => 0,
                    'member_limit' => 25,
                    'priority_listing' => 1,
                    'featured_profile' => 1,
                    'verified_badge' => 1,
                    'analytics_access' => 1,
                    'dedicated_support' => 1,
                    'highlight_text' => 'Homepage visibility',
                    'description' => 'Featured supplier placement on homepage and premium B2B discovery.',
                    'sort_order' => 1,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_package_requests')) {
            Schema::table('b2b_package_requests', function (Blueprint $table) {
                foreach ([
                    'payment_submitted_at',
                    'payment_notes',
                    'payment_reference',
                    'billing_cycle',
                ] as $column) {
                    if (Schema::hasColumn('b2b_package_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
