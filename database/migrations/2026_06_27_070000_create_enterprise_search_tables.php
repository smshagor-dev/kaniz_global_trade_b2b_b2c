<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('search_documents')) {
            Schema::create('search_documents', function (Blueprint $table) {
                $table->id();
                $table->string('engine_document_id')->unique();
                $table->string('index_name')->index();
                $table->string('type')->index();
                $table->string('entity_subtype')->nullable()->index();
                $table->string('model_type')->index();
                $table->unsignedBigInteger('model_id')->index();
                $table->string('title')->index();
                $table->string('subtitle')->nullable();
                $table->text('summary')->nullable();
                $table->string('url')->nullable();
                $table->longText('search_text');
                $table->text('keywords')->nullable();
                $table->json('filters')->nullable();
                $table->json('metadata')->nullable();
                $table->string('visibility')->default('public')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->decimal('rank_exact', 12, 2)->default(1);
                $table->decimal('rank_popularity', 12, 2)->default(0);
                $table->decimal('rank_sales', 12, 2)->default(0);
                $table->decimal('rank_verified', 12, 2)->default(0);
                $table->decimal('rank_featured', 12, 2)->default(0);
                $table->decimal('rank_supplier_score', 12, 2)->default(0);
                $table->decimal('rank_rating', 12, 2)->default(0);
                $table->decimal('rank_trade_volume', 12, 2)->default(0);
                $table->decimal('rank_response_rate', 12, 2)->default(0);
                $table->decimal('rank_recency', 12, 2)->default(0);
                $table->decimal('rank_ai_score', 12, 2)->default(0);
                $table->timestamp('last_indexed_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('search_analytics_events')) {
            Schema::create('search_analytics_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_type')->index();
                $table->string('query')->nullable()->index();
                $table->string('provider')->nullable()->index();
                $table->unsignedBigInteger('document_id')->nullable()->index();
                $table->string('session_id')->nullable()->index();
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->unsignedInteger('result_count')->nullable();
                $table->unsignedInteger('response_time_ms')->nullable();
                $table->json('filters')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('search_indexing_failures')) {
            Schema::create('search_indexing_failures', function (Blueprint $table) {
                $table->id();
                $table->string('index_name')->index();
                $table->string('model_type')->index();
                $table->unsignedBigInteger('model_id')->index();
                $table->string('operation')->index();
                $table->string('provider')->index();
                $table->text('message');
                $table->json('payload')->nullable();
                $table->timestamp('failed_at')->nullable()->index();
                $table->timestamp('resolved_at')->nullable()->index();
                $table->unsignedInteger('attempts')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('search_indexing_failures');
        Schema::dropIfExists('search_analytics_events');
        Schema::dropIfExists('search_documents');
    }
};
