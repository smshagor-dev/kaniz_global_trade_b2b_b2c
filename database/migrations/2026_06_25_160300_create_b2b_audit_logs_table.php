<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('actor_company_id')->nullable();
            $table->string('event_type', 100);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('actor_user_id');
            $table->index('actor_company_id');
            $table->index('event_type');
            $table->index(['auditable_type', 'auditable_id'], 'b2b_audit_logs_auditable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_audit_logs');
    }
};
