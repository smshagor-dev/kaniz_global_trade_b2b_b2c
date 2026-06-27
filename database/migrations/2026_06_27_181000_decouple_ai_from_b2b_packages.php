<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_packages')) {
            return;
        }

        DB::table('b2b_packages')->whereIn('name', [
            'Buyer AI Pro',
            'Supplier AI Pro',
        ])->delete();

        foreach ([
            'ai_access',
            'ai_rfq_access',
            'ai_product_description_access',
            'ai_negotiation_access',
            'ai_translation_access',
        ] as $column) {
            if (Schema::hasColumn('b2b_packages', $column)) {
                DB::table('b2b_packages')->update([$column => 0]);
            }
        }
    }

    public function down(): void
    {
        // AI access is now managed through Global B2B Config.
    }
};
