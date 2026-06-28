<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fraud_checks')) {
            Schema::create('fraud_checks', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('user_type', 30)->index();
                $table->unsignedInteger('risk_score')->default(0);
                $table->enum('risk_level', ['safe', 'low', 'medium', 'high', 'critical', 'blocked'])->default('safe')->index();
                $table->enum('source', ['rule_based', 'ai', 'manual', 'combined'])->default('rule_based')->index();
                $table->enum('status', ['pending', 'approved', 'rejected', 'blocked', 'needs_review', 'restricted'])->default('pending')->index();
                $table->text('summary')->nullable();
                $table->json('reasons')->nullable();
                $table->unsignedInteger('rule_score')->nullable();
                $table->unsignedInteger('ai_score')->nullable();
                $table->unsignedInteger('manual_score')->nullable();
                $table->unsignedInteger('final_score')->nullable();
                $table->string('ai_provider')->nullable();
                $table->string('ai_model')->nullable();
                $table->json('ai_response')->nullable();
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['user_id', 'risk_level']);
                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('fraud_check_logs')) {
            Schema::create('fraud_check_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fraud_check_id')->constrained('fraud_checks')->cascadeOnDelete();
                $table->unsignedInteger('user_id');
                $table->string('event_type', 80)->index();
                $table->unsignedInteger('old_score')->nullable();
                $table->unsignedInteger('new_score')->nullable();
                $table->text('reason')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('fraud_rules')) {
            Schema::create('fraud_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->string('user_type', 30)->nullable()->index();
                $table->string('event_type', 80)->nullable()->index();
                $table->integer('score');
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('conditions')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('verification_documents')) {
            Schema::create('verification_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('user_type', 30)->index();
                $table->enum('document_type', ['business_license', 'tax_certificate', 'national_id', 'passport', 'bank_statement', 'company_registration', 'address_proof', 'other'])->index();
                $table->string('file_path');
                $table->string('original_name');
                $table->string('mime_type', 120);
                $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending')->index();
                $table->text('rejection_reason')->nullable();
                $table->longText('extracted_text')->nullable();
                $table->json('ai_analysis')->nullable();
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('user_device_logs')) {
            Schema::create('user_device_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('ip_address', 64)->nullable()->index();
                $table->text('user_agent')->nullable();
                $table->string('device_hash', 128)->nullable()->index();
                $table->string('country', 100)->nullable()->index();
                $table->string('city', 100)->nullable();
                $table->timestamp('login_at')->useCurrent()->index();
                $table->json('metadata')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('user_risk_events')) {
            Schema::create('user_risk_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('user_type', 30)->index();
                $table->string('event_type', 80)->index();
                $table->integer('score')->default(0);
                $table->text('reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['user_id', 'event_type']);
            });
        }

        if (!Schema::hasTable('user_reports')) {
            Schema::create('user_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('reporter_id');
                $table->unsignedInteger('reported_user_id');
                $table->string('reported_user_type', 30)->index();
                $table->enum('report_type', ['scam', 'fake_supplier', 'fake_buyer', 'payment_fraud', 'fake_document', 'spam', 'abuse', 'other'])->index();
                $table->text('message')->nullable();
                $table->json('evidence')->nullable();
                $table->enum('status', ['pending', 'investigating', 'resolved', 'rejected'])->default('pending')->index();
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->foreign('reporter_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('reported_user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['reported_user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_reports');
        Schema::dropIfExists('user_risk_events');
        Schema::dropIfExists('user_device_logs');
        Schema::dropIfExists('verification_documents');
        Schema::dropIfExists('fraud_rules');
        Schema::dropIfExists('fraud_check_logs');
        Schema::dropIfExists('fraud_checks');
    }
};
