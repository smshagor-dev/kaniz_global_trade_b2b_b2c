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
                if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_fee_percent_snapshot')) {
                    $table->decimal('escrow_fee_percent_snapshot', 8, 3)->default(0)->after('buyer_payable_total');
                }
                if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_fee_fixed_snapshot')) {
                    $table->decimal('escrow_fee_fixed_snapshot', 20, 2)->default(0)->after('escrow_fee_percent_snapshot');
                }
                if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_fee_amount')) {
                    $table->decimal('escrow_fee_amount', 20, 2)->default(0)->after('escrow_fee_fixed_snapshot');
                }
            });
        }

        foreach ([
            'b2b_escrow_fee_enabled' => 0,
            'b2b_escrow_fee_type' => 'percentage',
            'b2b_escrow_fee_percent' => 0,
            'b2b_escrow_fee_fixed' => 0,
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
                    escrow_fee_percent_snapshot = COALESCE(escrow_fee_percent_snapshot, 0),
                    escrow_fee_fixed_snapshot = COALESCE(escrow_fee_fixed_snapshot, 0),
                    escrow_fee_amount = COALESCE(escrow_fee_amount, 0)
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_proforma_invoices')) {
            Schema::table('b2b_proforma_invoices', function (Blueprint $table) {
                foreach ([
                    'escrow_fee_amount',
                    'escrow_fee_fixed_snapshot',
                    'escrow_fee_percent_snapshot',
                ] as $column) {
                    if (Schema::hasColumn('b2b_proforma_invoices', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
