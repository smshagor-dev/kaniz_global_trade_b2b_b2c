<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('top_banners')) {
            Schema::create('top_banners', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->longText('text');
                $table->string('link', 100)->nullable();
                $table->integer('status')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('top_banner_translations')) {
            Schema::create('top_banner_translations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('top_banner_id');
                $table->longText('text');
                $table->string('lang', 100);
                $table->timestamps();

                $table->index('top_banner_id');
                $table->index(['top_banner_id', 'lang'], 'top_banner_translation_lang_index');
            });
        }
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
    }
};
