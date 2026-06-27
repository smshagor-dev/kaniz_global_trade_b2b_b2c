<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('search_indexing_runs')) {
            Schema::create('search_indexing_runs', function (Blueprint $table) {
                $table->id();
                $table->string('entity')->default('all')->index();
                $table->string('provider')->nullable()->index();
                $table->unsignedInteger('chunk_size')->default(100);
                $table->boolean('is_queue')->default(false);
                $table->boolean('is_dry_run')->default(false);
                $table->string('status')->default('pending')->index();
                $table->unsignedBigInteger('total_models')->default(0);
                $table->unsignedBigInteger('processed_models')->default(0);
                $table->unsignedBigInteger('failed_models')->default(0);
                $table->unsignedBigInteger('queued_chunks')->default(0);
                $table->unsignedBigInteger('processed_chunks')->default(0);
                $table->string('current_model_class')->nullable()->index();
                $table->unsignedBigInteger('last_processed_id')->nullable();
                $table->json('summary')->nullable();
                $table->timestamp('started_at')->nullable()->index();
                $table->timestamp('finished_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('search_indexing_failures') && !Schema::hasColumn('search_indexing_failures', 'run_id')) {
            Schema::table('search_indexing_failures', function (Blueprint $table) {
                $table->unsignedBigInteger('run_id')->nullable()->after('id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('search_indexing_failures') && Schema::hasColumn('search_indexing_failures', 'run_id')) {
            Schema::table('search_indexing_failures', function (Blueprint $table) {
                $table->dropColumn('run_id');
            });
        }

        Schema::dropIfExists('search_indexing_runs');
    }
};
