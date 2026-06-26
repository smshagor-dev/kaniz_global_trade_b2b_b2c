<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_shipping_quotes')) {
            Schema::table('b2b_shipping_quotes', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_shipping_quotes', 'subtotal_cost')) {
                    $table->decimal('subtotal_cost', 12, 2)->default(0)->after('customs_estimate');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'site_charge_percent_snapshot')) {
                    $table->decimal('site_charge_percent_snapshot', 8, 3)->default(0)->after('subtotal_cost');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'site_charge_fixed_snapshot')) {
                    $table->decimal('site_charge_fixed_snapshot', 12, 2)->default(0)->after('site_charge_percent_snapshot');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'site_charge_amount')) {
                    $table->decimal('site_charge_amount', 12, 2)->default(0)->after('site_charge_fixed_snapshot');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'total_cost')) {
                    $table->decimal('total_cost', 12, 2)->default(0)->after('site_charge_amount');
                }
            });
        }

        foreach ([
            'b2b_shipping_site_charge_enabled' => 0,
            'b2b_shipping_site_charge_percent' => 0,
            'b2b_shipping_site_charge_fixed' => 0,
            'b2b_freight_site_charge_enabled' => 0,
            'b2b_freight_site_charge_percent' => 0,
            'b2b_freight_site_charge_fixed' => 0,
        ] as $type => $value) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => $type],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        if (Schema::hasTable('b2b_shipping_quotes')) {
            DB::statement("
                UPDATE b2b_shipping_quotes
                SET
                    subtotal_cost = COALESCE(shipping_cost, 0) + COALESCE(insurance_amount, 0) + COALESCE(customs_estimate, 0),
                    site_charge_percent_snapshot = COALESCE(site_charge_percent_snapshot, 0),
                    site_charge_fixed_snapshot = COALESCE(site_charge_fixed_snapshot, 0),
                    site_charge_amount = COALESCE(site_charge_amount, 0),
                    total_cost = COALESCE(total_cost, (COALESCE(shipping_cost, 0) + COALESCE(insurance_amount, 0) + COALESCE(customs_estimate, 0)))
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_shipping_quotes')) {
            Schema::table('b2b_shipping_quotes', function (Blueprint $table) {
                foreach (['total_cost', 'site_charge_amount', 'site_charge_fixed_snapshot', 'site_charge_percent_snapshot', 'subtotal_cost'] as $column) {
                    if (Schema::hasColumn('b2b_shipping_quotes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
