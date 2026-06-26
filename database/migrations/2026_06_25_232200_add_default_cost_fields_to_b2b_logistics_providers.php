<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_shipping_providers')) {
            Schema::table('b2b_shipping_providers', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_shipping_providers', 'default_shipping_cost')) {
                    $table->decimal('default_shipping_cost', 12, 2)->default(0)->after('supported_countries');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'default_insurance_amount')) {
                    $table->decimal('default_insurance_amount', 12, 2)->default(0)->after('default_shipping_cost');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'default_customs_estimate')) {
                    $table->decimal('default_customs_estimate', 12, 2)->default(0)->after('default_insurance_amount');
                }
            });
        }

        if (Schema::hasTable('b2b_freight_forwarders')) {
            Schema::table('b2b_freight_forwarders', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_freight_forwarders', 'default_freight_cost')) {
                    $table->decimal('default_freight_cost', 12, 2)->default(0)->after('container_types');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'default_insurance_cost')) {
                    $table->decimal('default_insurance_cost', 12, 2)->default(0)->after('default_freight_cost');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'default_customs_estimate')) {
                    $table->decimal('default_customs_estimate', 12, 2)->default(0)->after('default_insurance_cost');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_shipping_providers')) {
            Schema::table('b2b_shipping_providers', function (Blueprint $table) {
                foreach (['default_shipping_cost', 'default_insurance_amount', 'default_customs_estimate'] as $column) {
                    if (Schema::hasColumn('b2b_shipping_providers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('b2b_freight_forwarders')) {
            Schema::table('b2b_freight_forwarders', function (Blueprint $table) {
                foreach (['default_freight_cost', 'default_insurance_cost', 'default_customs_estimate'] as $column) {
                    if (Schema::hasColumn('b2b_freight_forwarders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
