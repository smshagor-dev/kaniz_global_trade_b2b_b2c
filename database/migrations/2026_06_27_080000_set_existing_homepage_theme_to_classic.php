<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        DB::table('business_settings')->updateOrInsert(
            ['type' => 'homepage_select', 'lang' => null],
            ['value' => 'classic', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
    }
};
