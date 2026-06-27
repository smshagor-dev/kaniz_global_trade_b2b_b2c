<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_insurance_providers')) {
            Schema::create('b2b_insurance_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('company')->nullable();
                $table->string('slug')->unique();
                $table->string('logo')->nullable();
                $table->string('country', 120)->nullable()->index();
                $table->json('coverage')->nullable();
                $table->string('integration_mode', 30)->default('manual')->index();
                $table->string('api_base_url')->nullable();
                $table->text('api_key')->nullable();
                $table->text('api_secret')->nullable();
                $table->text('username')->nullable();
                $table->text('password')->nullable();
                $table->text('credentials')->nullable();
                $table->string('webhook_url')->nullable();
                $table->text('webhook_secret')->nullable();
                $table->json('policy_types')->nullable();
                $table->json('supported_countries')->nullable();
                $table->json('premium_rules')->nullable();
                $table->json('claim_rules')->nullable();
                $table->json('custom_config')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_default')->default(false)->index();
                $table->boolean('is_test_mode')->default(true);
                $table->unsignedInteger('successful_requests')->default(0);
                $table->unsignedInteger('failed_requests')->default(0);
                $table->string('last_api_status')->nullable();
                $table->unsignedInteger('last_api_http_status')->nullable();
                $table->unsignedInteger('last_api_response_time_ms')->nullable();
                $table->timestamp('last_api_called_at')->nullable();
                $table->timestamp('last_api_success_at')->nullable();
                $table->timestamp('last_api_failure_at')->nullable();
                $table->timestamp('last_webhook_received_at')->nullable();
                $table->timestamp('webhook_verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('b2b_insurance_quotes')) {
            Schema::create('b2b_insurance_quotes', function (Blueprint $table) {
                $table->id();
                $table->string('quote_number')->unique();
                $table->foreignId('provider_id')->nullable()->constrained('b2b_insurance_providers')->nullOnDelete();
                $table->foreignId('buyer_company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
                $table->foreignId('supplier_company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
                $table->unsignedInteger('created_by')->nullable();
                $table->foreignId('shipment_id')->nullable()->constrained('b2b_shipments')->nullOnDelete();
                $table->foreignId('container_shipment_id')->nullable()->constrained('b2b_container_shipments')->nullOnDelete();
                $table->foreignId('freight_quote_id')->nullable()->constrained('b2b_freight_quotes')->nullOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained('b2b_purchase_orders')->nullOnDelete();
                $table->foreignId('proforma_invoice_id')->nullable()->constrained('b2b_proforma_invoices')->nullOnDelete();
                $table->string('insurance_type', 80)->index();
                $table->string('transport_mode', 50)->nullable()->index();
                $table->string('incoterm', 20)->nullable();
                $table->string('container_type', 40)->nullable();
                $table->string('origin_country', 120)->nullable()->index();
                $table->string('destination_country', 120)->nullable()->index();
                $table->string('origin_port')->nullable();
                $table->string('destination_port')->nullable();
                $table->string('commodity')->nullable();
                $table->string('hs_code', 40)->nullable()->index();
                $table->decimal('weight', 14, 3)->default(0);
                $table->decimal('volume', 14, 3)->default(0);
                $table->decimal('shipment_value', 18, 2)->default(0);
                $table->decimal('coverage_amount', 18, 2)->default(0);
                $table->string('currency', 20)->default('USD');
                $table->decimal('exchange_rate_snapshot', 18, 8)->default(1);
                $table->json('currency_snapshot')->nullable();
                $table->decimal('risk_score', 8, 2)->default(0)->index();
                $table->json('risk_breakdown')->nullable();
                $table->decimal('premium', 18, 2)->default(0);
                $table->decimal('tax_amount', 18, 2)->default(0);
                $table->decimal('additional_charges', 18, 2)->default(0);
                $table->decimal('platform_fee', 18, 2)->default(0);
                $table->decimal('discount_amount', 18, 2)->default(0);
                $table->decimal('final_amount', 18, 2)->default(0);
                $table->json('premium_breakdown')->nullable();
                $table->json('calculation_history')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->json('ai_recommendation')->nullable();
                $table->string('status', 40)->default('draft')->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('b2b_insurance_policies')) {
            Schema::create('b2b_insurance_policies', function (Blueprint $table) {
                $table->id();
                $table->string('policy_number')->unique();
                $table->foreignId('provider_id')->nullable()->constrained('b2b_insurance_providers')->nullOnDelete();
                $table->foreignId('quote_id')->nullable()->constrained('b2b_insurance_quotes')->nullOnDelete();
                $table->foreignId('buyer_company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
                $table->foreignId('supplier_company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
                $table->unsignedInteger('policy_holder_user_id')->nullable();
                $table->unsignedInteger('issued_by')->nullable();
                $table->foreignId('shipment_id')->nullable()->constrained('b2b_shipments')->nullOnDelete();
                $table->foreignId('container_shipment_id')->nullable()->constrained('b2b_container_shipments')->nullOnDelete();
                $table->foreignId('freight_quote_id')->nullable()->constrained('b2b_freight_quotes')->nullOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained('b2b_purchase_orders')->nullOnDelete();
                $table->foreignId('proforma_invoice_id')->nullable()->constrained('b2b_proforma_invoices')->nullOnDelete();
                $table->string('finance_reference_type')->nullable();
                $table->unsignedBigInteger('finance_reference_id')->nullable();
                $table->string('insurance_type', 80)->index();
                $table->string('transport_mode', 50)->nullable()->index();
                $table->string('coverage_plan')->nullable();
                $table->string('status', 40)->default('draft')->index();
                $table->decimal('coverage_amount', 18, 2)->default(0);
                $table->decimal('premium', 18, 2)->default(0);
                $table->decimal('tax_amount', 18, 2)->default(0);
                $table->decimal('deductible_amount', 18, 2)->default(0);
                $table->decimal('insured_value', 18, 2)->default(0);
                $table->string('currency', 20)->default('USD');
                $table->decimal('exchange_rate_snapshot', 18, 8)->default(1);
                $table->json('currency_snapshot')->nullable();
                $table->json('coverage_details')->nullable();
                $table->json('premium_breakdown')->nullable();
                $table->json('attachment_paths')->nullable();
                $table->json('metadata')->nullable();
                $table->date('coverage_start')->nullable()->index();
                $table->date('coverage_end')->nullable()->index();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('expired_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->foreign('policy_holder_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('b2b_insurance_claims')) {
            Schema::create('b2b_insurance_claims', function (Blueprint $table) {
                $table->id();
                $table->string('claim_number')->unique();
                $table->foreignId('policy_id')->constrained('b2b_insurance_policies')->cascadeOnDelete();
                $table->foreignId('provider_id')->nullable()->constrained('b2b_insurance_providers')->nullOnDelete();
                $table->foreignId('buyer_company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
                $table->foreignId('supplier_company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
                $table->unsignedInteger('claimant_user_id')->nullable();
                $table->foreignId('claimant_company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->foreignId('shipment_id')->nullable()->constrained('b2b_shipments')->nullOnDelete();
                $table->foreignId('container_shipment_id')->nullable()->constrained('b2b_container_shipments')->nullOnDelete();
                $table->foreignId('freight_quote_id')->nullable()->constrained('b2b_freight_quotes')->nullOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained('b2b_purchase_orders')->nullOnDelete();
                $table->foreignId('proforma_invoice_id')->nullable()->constrained('b2b_proforma_invoices')->nullOnDelete();
                $table->string('status', 40)->default('submitted')->index();
                $table->string('claim_type', 80)->nullable()->index();
                $table->string('incident_country', 120)->nullable();
                $table->string('incident_location')->nullable();
                $table->string('incident_reference')->nullable();
                $table->text('summary');
                $table->longText('description')->nullable();
                $table->decimal('claim_amount', 18, 2)->default(0);
                $table->decimal('approved_amount', 18, 2)->default(0);
                $table->decimal('settled_amount', 18, 2)->default(0);
                $table->string('currency', 20)->default('USD');
                $table->json('evidence')->nullable();
                $table->json('timeline')->nullable();
                $table->json('comments')->nullable();
                $table->json('validation_summary')->nullable();
                $table->json('fraud_signals')->nullable();
                $table->json('resolution_data')->nullable();
                $table->timestamp('incident_at')->nullable()->index();
                $table->timestamp('submitted_at')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('settled_at')->nullable();
                $table->timestamp('appealed_at')->nullable();
                $table->timestamps();
                $table->foreign('claimant_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('b2b_insurance_claim_documents')) {
            Schema::create('b2b_insurance_claim_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('claim_id')->constrained('b2b_insurance_claims')->cascadeOnDelete();
                $table->unsignedInteger('uploaded_by')->nullable();
                $table->string('document_type', 80)->index();
                $table->string('title')->nullable();
                $table->string('file_path');
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('b2b_insurance_payments')) {
            Schema::create('b2b_insurance_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('policy_id')->nullable()->constrained('b2b_insurance_policies')->nullOnDelete();
                $table->foreignId('claim_id')->nullable()->constrained('b2b_insurance_claims')->nullOnDelete();
                $table->foreignId('provider_id')->nullable()->constrained('b2b_insurance_providers')->nullOnDelete();
                $table->foreignId('buyer_company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
                $table->foreignId('supplier_company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
                $table->unsignedInteger('recorded_by')->nullable();
                $table->string('payment_type', 40)->index();
                $table->string('payment_method', 60)->nullable();
                $table->string('reference')->nullable()->index();
                $table->decimal('amount', 18, 2)->default(0);
                $table->decimal('tax_amount', 18, 2)->default(0);
                $table->decimal('fees', 18, 2)->default(0);
                $table->string('currency', 20)->default('USD');
                $table->string('status', 40)->default('pending')->index();
                $table->json('meta')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('b2b_insurance_events')) {
            Schema::create('b2b_insurance_events', function (Blueprint $table) {
                $table->id();
                $table->string('eventable_type');
                $table->unsignedBigInteger('eventable_id');
                $table->foreignId('provider_id')->nullable()->constrained('b2b_insurance_providers')->nullOnDelete();
                $table->foreignId('company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
                $table->unsignedInteger('user_id')->nullable();
                $table->string('event_type', 80)->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('occurred_at')->nullable()->index();
                $table->timestamps();
                $table->index(['eventable_type', 'eventable_id'], 'b2b_insurance_events_eventable_index');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('b2b_insurance_api_logs')) {
            Schema::create('b2b_insurance_api_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('provider_id')->nullable()->constrained('b2b_insurance_providers')->nullOnDelete();
                $table->string('loggable_type')->nullable();
                $table->unsignedBigInteger('loggable_id')->nullable();
                $table->string('direction', 20)->default('outbound')->index();
                $table->string('endpoint')->nullable();
                $table->string('request_method', 20)->nullable();
                $table->unsignedInteger('http_status')->nullable()->index();
                $table->string('status', 40)->default('pending')->index();
                $table->unsignedInteger('latency_ms')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
                $table->index(['loggable_type', 'loggable_id'], 'b2b_insurance_api_logs_loggable_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_insurance_api_logs');
        Schema::dropIfExists('b2b_insurance_events');
        Schema::dropIfExists('b2b_insurance_payments');
        Schema::dropIfExists('b2b_insurance_claim_documents');
        Schema::dropIfExists('b2b_insurance_claims');
        Schema::dropIfExists('b2b_insurance_policies');
        Schema::dropIfExists('b2b_insurance_quotes');
        Schema::dropIfExists('b2b_insurance_providers');
    }
};
