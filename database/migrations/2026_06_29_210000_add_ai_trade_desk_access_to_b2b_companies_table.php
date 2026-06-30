<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_companies')) {
            return;
        }

        Schema::table('b2b_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('b2b_companies', 'ai_trade_desk_active')) {
                $table->boolean('ai_trade_desk_active')->default(false)->after('premium_verified');
            }

            if (!Schema::hasColumn('b2b_companies', 'ai_trade_desk_paid_at')) {
                $table->timestamp('ai_trade_desk_paid_at')->nullable()->after('premium_verified_at');
            }

            if (!Schema::hasColumn('b2b_companies', 'ai_trade_desk_price')) {
                $table->decimal('ai_trade_desk_price', 11, 2)->default(0)->after('ai_trade_desk_paid_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('b2b_companies')) {
            return;
        }

        Schema::table('b2b_companies', function (Blueprint $table) {
            foreach (['ai_trade_desk_price', 'ai_trade_desk_paid_at', 'ai_trade_desk_active'] as $column) {
                if (Schema::hasColumn('b2b_companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
