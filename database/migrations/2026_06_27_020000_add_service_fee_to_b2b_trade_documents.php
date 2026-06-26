<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_trade_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('b2b_trade_documents', 'service_fee_fixed_snapshot')) {
                $table->decimal('service_fee_fixed_snapshot', 12, 2)->default(0)->after('notes');
            }

            if (!Schema::hasColumn('b2b_trade_documents', 'service_fee_amount')) {
                $table->decimal('service_fee_amount', 12, 2)->default(0)->after('service_fee_fixed_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('b2b_trade_documents', function (Blueprint $table) {
            if (Schema::hasColumn('b2b_trade_documents', 'service_fee_amount')) {
                $table->dropColumn('service_fee_amount');
            }

            if (Schema::hasColumn('b2b_trade_documents', 'service_fee_fixed_snapshot')) {
                $table->dropColumn('service_fee_fixed_snapshot');
            }
        });
    }
};
