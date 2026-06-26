<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_ports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('country', 100);
            $table->string('city', 100)->nullable();
            $table->string('unlocode', 20)->nullable()->index();
            $table->string('timezone', 50)->nullable();
            $table->string('port_type', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('b2b_freight_forwarders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver', 50);
            $table->string('website')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('api_base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->text('account_number')->nullable();
            $table->text('oauth_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->string('environment', 40)->nullable();
            $table->boolean('is_test_mode')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('supported_modes')->nullable();
            $table->json('supported_services')->nullable();
            $table->json('custom_config')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_freight_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('buyer_company_id')->constrained('b2b_companies')->cascadeOnDelete();
            $table->foreignId('supplier_company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
            $table->foreignId('forwarder_id')->nullable()->constrained('b2b_freight_forwarders')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('b2b_purchase_orders')->nullOnDelete();
            $table->foreignId('proforma_invoice_id')->nullable()->constrained('b2b_proforma_invoices')->nullOnDelete();
            $table->foreignId('sample_order_id')->nullable()->constrained('b2b_sample_orders')->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained('b2b_shipments')->nullOnDelete();
            $table->unsignedBigInteger('rfq_id')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->string('origin_country', 100);
            $table->foreignId('origin_port_id')->nullable()->constrained('b2b_ports')->nullOnDelete();
            $table->string('destination_country', 100);
            $table->foreignId('destination_port_id')->nullable()->constrained('b2b_ports')->nullOnDelete();
            $table->string('freight_mode', 30);
            $table->string('service_type', 40)->nullable();
            $table->string('incoterm', 10)->nullable();
            $table->string('container_type', 40)->nullable();
            $table->unsignedInteger('container_count')->default(1);
            $table->decimal('cargo_weight', 12, 3)->nullable();
            $table->decimal('cargo_volume', 12, 3)->nullable();
            $table->string('hs_code', 60)->nullable();
            $table->text('goods_description')->nullable();
            $table->text('pickup_address')->nullable();
            $table->text('delivery_address')->nullable();
            $table->unsignedSmallInteger('estimated_days')->nullable();
            $table->decimal('freight_cost', 12, 2)->default(0);
            $table->decimal('insurance_cost', 12, 2)->default(0);
            $table->decimal('customs_estimate', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->string('currency', 20)->default('USD');
            $table->string('status', 40)->default('requested');
            $table->json('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_container_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freight_quote_id')->constrained('b2b_freight_quotes')->cascadeOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained('b2b_shipments')->nullOnDelete();
            $table->foreignId('forwarder_id')->nullable()->constrained('b2b_freight_forwarders')->nullOnDelete();
            $table->string('booking_number', 120)->nullable()->index();
            $table->string('bill_of_lading_number', 120)->nullable()->index();
            $table->string('container_number', 120)->nullable()->index();
            $table->string('seal_number', 120)->nullable();
            $table->string('vessel_name', 120)->nullable();
            $table->string('voyage_number', 120)->nullable();
            $table->foreignId('origin_port_id')->nullable()->constrained('b2b_ports')->nullOnDelete();
            $table->foreignId('destination_port_id')->nullable()->constrained('b2b_ports')->nullOnDelete();
            $table->foreignId('transshipment_port_id')->nullable()->constrained('b2b_ports')->nullOnDelete();
            $table->timestamp('etd')->nullable();
            $table->timestamp('eta')->nullable();
            $table->timestamp('ata')->nullable();
            $table->string('status', 40)->default('booked');
            $table->string('source_provider', 120)->nullable();
            $table->string('tracking_reference', 120)->nullable();
            $table->text('sync_error')->nullable();
            $table->json('request_payload')->nullable();
            $table->longText('last_response')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_container_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_shipment_id')->constrained('b2b_container_shipments')->cascadeOnDelete();
            $table->string('event_type', 60);
            $table->string('port_location', 120)->nullable();
            $table->foreignId('port_id')->nullable()->constrained('b2b_ports')->nullOnDelete();
            $table->string('vessel_name', 120)->nullable();
            $table->string('voyage_number', 120)->nullable();
            $table->text('description')->nullable();
            $table->string('source_provider', 120)->nullable();
            $table->timestamp('event_at');
            $table->timestamps();
        });

        Schema::create('b2b_customs_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
            $table->string('document_type', 80);
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_customs_documents');
        Schema::dropIfExists('b2b_container_events');
        Schema::dropIfExists('b2b_container_shipments');
        Schema::dropIfExists('b2b_freight_quotes');
        Schema::dropIfExists('b2b_freight_forwarders');
        Schema::dropIfExists('b2b_ports');
    }
};
