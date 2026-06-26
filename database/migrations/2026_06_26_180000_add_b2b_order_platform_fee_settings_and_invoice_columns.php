<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_proforma_invoices')) {
            Schema::table('b2b_proforma_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_proforma_invoices', 'platform_fee_percent_snapshot')) {
                    $table->decimal('platform_fee_percent_snapshot', 8, 3)->default(0)->after('grand_total');
                }
                if (!Schema::hasColumn('b2b_proforma_invoices', 'platform_fee_fixed_snapshot')) {
                    $table->decimal('platform_fee_fixed_snapshot', 20, 2)->default(0)->after('platform_fee_percent_snapshot');
                }
                if (!Schema::hasColumn('b2b_proforma_invoices', 'platform_fee_amount')) {
                    $table->decimal('platform_fee_amount', 20, 2)->default(0)->after('platform_fee_fixed_snapshot');
                }
                if (!Schema::hasColumn('b2b_proforma_invoices', 'supplier_payout_amount')) {
                    $table->decimal('supplier_payout_amount', 20, 2)->default(0)->after('platform_fee_amount');
                }
                if (!Schema::hasColumn('b2b_proforma_invoices', 'buyer_payable_total')) {
                    $table->decimal('buyer_payable_total', 20, 2)->default(0)->after('supplier_payout_amount');
                }
            });
        }

        foreach ([
            'b2b_order_platform_fee_enabled' => 0,
            'b2b_order_platform_fee_type' => 'percentage',
            'b2b_order_platform_fee_percent' => 0,
            'b2b_order_platform_fee_fixed' => 0,
        ] as $type => $value) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => $type],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        if (Schema::hasTable('b2b_proforma_invoices')) {
            DB::statement("
                UPDATE b2b_proforma_invoices
                SET
                    platform_fee_percent_snapshot = COALESCE(platform_fee_percent_snapshot, 0),
                    platform_fee_fixed_snapshot = COALESCE(platform_fee_fixed_snapshot, 0),
                    platform_fee_amount = COALESCE(platform_fee_amount, 0),
                    supplier_payout_amount = COALESCE(supplier_payout_amount, grand_total),
                    buyer_payable_total = COALESCE(buyer_payable_total, grand_total)
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_proforma_invoices')) {
            Schema::table('b2b_proforma_invoices', function (Blueprint $table) {
                foreach ([
                    'buyer_payable_total',
                    'supplier_payout_amount',
                    'platform_fee_amount',
                    'platform_fee_fixed_snapshot',
                    'platform_fee_percent_snapshot',
                ] as $column) {
                    if (Schema::hasColumn('b2b_proforma_invoices', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
