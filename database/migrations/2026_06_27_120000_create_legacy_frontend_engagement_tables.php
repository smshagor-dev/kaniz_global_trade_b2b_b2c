<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('custom_alerts')) {
            Schema::create('custom_alerts', function (Blueprint $table) {
                $table->unsignedInteger('id', true);
                $table->integer('status')->default(0);
                $table->string('type', 191)->default('small');
                $table->string('banner', 191)->nullable();
                $table->string('link', 191)->default('#');
                $table->text('description')->nullable();
                $table->string('text_color', 191)->nullable();
                $table->string('background_color', 191)->nullable();
                $table->integer('auto_hide')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('wishlists')) {
            Schema::create('wishlists', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('user_id');
                $table->integer('product_id');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
    }
};
