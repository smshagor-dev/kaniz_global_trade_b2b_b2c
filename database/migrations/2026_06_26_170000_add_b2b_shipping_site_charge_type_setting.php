<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('business_settings')->updateOrInsert(
            ['type' => 'b2b_shipping_site_charge_type'],
            ['value' => 'fixed', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('business_settings')
            ->where('type', 'b2b_shipping_site_charge_type')
            ->delete();
    }
};
