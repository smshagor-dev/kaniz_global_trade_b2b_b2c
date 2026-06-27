<?php

use App\Services\AI\AILegacySettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_provider_settings')) {
            return;
        }

        app(AILegacySettingsService::class)->ensureGeminiProvider();
        app(AILegacySettingsService::class)->sync();
    }

    public function down(): void
    {
        //
    }
};
