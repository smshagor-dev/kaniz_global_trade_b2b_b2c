<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('currency_api_settings')) {
            return;
        }

        DB::table('currency_api_settings')
            ->where('sync_frequency', 'hourly')
            ->update([
                'sync_frequency' => 'six_hours',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('currency_api_settings')) {
            return;
        }

        DB::table('currency_api_settings')
            ->where('sync_frequency', 'six_hours')
            ->update([
                'sync_frequency' => 'hourly',
                'updated_at' => now(),
            ]);
    }
};
