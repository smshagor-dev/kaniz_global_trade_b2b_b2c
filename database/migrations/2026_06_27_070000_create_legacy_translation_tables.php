<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('translations')) {
            Schema::create('translations', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('lang', 10)->nullable()->index();
                $table->text('lang_key')->nullable();
                $table->text('lang_value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('app_translations')) {
            Schema::create('app_translations', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('lang', 10)->nullable()->index();
                $table->string('lang_key')->nullable();
                $table->string('lang_value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
    }
};
