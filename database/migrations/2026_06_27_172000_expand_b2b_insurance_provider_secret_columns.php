<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_insurance_providers')) {
            return;
        }

        DB::statement('ALTER TABLE b2b_insurance_providers MODIFY api_key TEXT NULL');
        DB::statement('ALTER TABLE b2b_insurance_providers MODIFY api_secret TEXT NULL');
        DB::statement('ALTER TABLE b2b_insurance_providers MODIFY username TEXT NULL');
        DB::statement('ALTER TABLE b2b_insurance_providers MODIFY password TEXT NULL');
        DB::statement('ALTER TABLE b2b_insurance_providers MODIFY webhook_secret TEXT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('b2b_insurance_providers')) {
            return;
        }

        DB::statement('ALTER TABLE b2b_insurance_providers MODIFY api_key VARCHAR(191) NULL');
        DB::statement('ALTER TABLE b2b_insurance_providers MODIFY api_secret VARCHAR(191) NULL');
        DB::statement('ALTER TABLE b2b_insurance_providers MODIFY username VARCHAR(191) NULL');
        DB::statement('ALTER TABLE b2b_insurance_providers MODIFY password VARCHAR(191) NULL');
        DB::statement('ALTER TABLE b2b_insurance_providers MODIFY webhook_secret VARCHAR(191) NULL');
    }
};
