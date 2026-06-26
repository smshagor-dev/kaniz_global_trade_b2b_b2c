<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_sample_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('b2b_sample_orders', 'sample_processing_fee_fixed_snapshot')) {
                $table->decimal('sample_processing_fee_fixed_snapshot', 12, 2)->default(0)->after('shipping_amount');
            }

            if (!Schema::hasColumn('b2b_sample_orders', 'sample_processing_fee_amount')) {
                $table->decimal('sample_processing_fee_amount', 12, 2)->default(0)->after('sample_processing_fee_fixed_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('b2b_sample_orders', function (Blueprint $table) {
            if (Schema::hasColumn('b2b_sample_orders', 'sample_processing_fee_amount')) {
                $table->dropColumn('sample_processing_fee_amount');
            }

            if (Schema::hasColumn('b2b_sample_orders', 'sample_processing_fee_fixed_snapshot')) {
                $table->dropColumn('sample_processing_fee_fixed_snapshot');
            }
        });
    }
};
