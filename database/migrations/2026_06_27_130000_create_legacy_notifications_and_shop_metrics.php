<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('shops') && !Schema::hasColumn('shops', 'num_of_sale')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->integer('num_of_sale')->default(0);
            });
        }
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
    }
};
