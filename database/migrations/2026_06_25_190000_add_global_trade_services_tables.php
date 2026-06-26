<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('b2b_trade_documents');
        Schema::dropIfExists('b2b_shipment_events');
        Schema::dropIfExists('b2b_shipments');
        Schema::dropIfExists('b2b_shipping_quotes');
        Schema::dropIfExists('b2b_sample_orders');
        Schema::dropIfExists('b2b_shipping_providers');

        if (!Schema::hasColumn('b2b_rfqs', 'incoterm')) {
            Schema::table('b2b_rfqs', function (Blueprint $table) {
                $table->string('incoterm', 10)->nullable()->after('currency');
            });
        }

        if (!Schema::hasColumn('b2b_quotations', 'incoterm')) {
            Schema::table('b2b_quotations', function (Blueprint $table) {
                $table->string('incoterm', 10)->nullable()->after('shipping_terms');
            });
        }

        if (!Schema::hasColumn('b2b_proforma_invoices', 'incoterm')) {
            Schema::table('b2b_proforma_invoices', function (Blueprint $table) {
                $table->string('incoterm', 10)->nullable()->after('currency');
            });
        }

        Schema::create('b2b_shipping_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('transport_mode', 30);
            $table->string('website')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->text('supported_countries')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_sample_orders', function (Blueprint $table) {
            $table->id();
            $table->string('sample_number')->unique();
            $table->foreignId('buyer_company_id')->constrained('b2b_companies')->cascadeOnDelete();
            $table->foreignId('supplier_company_id')->constrained('b2b_companies')->cascadeOnDelete();
            $table->unsignedInteger('buyer_user_id');
            $table->unsignedInteger('supplier_user_id');
            $table->integer('product_id')->nullable();
            $table->foreignId('rfq_id')->nullable()->constrained('b2b_rfqs')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('b2b_quotations')->nullOnDelete();
            $table->string('currency', 20)->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 100)->nullable();
            $table->decimal('sample_price', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 40)->default('requested');
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('supplier_responded_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('buyer_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('supplier_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });

        Schema::create('b2b_shipping_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('b2b_purchase_orders')->nullOnDelete();
            $table->foreignId('sample_order_id')->nullable()->constrained('b2b_sample_orders')->nullOnDelete();
            $table->foreignId('supplier_company_id')->constrained('b2b_companies')->cascadeOnDelete();
            $table->foreignId('buyer_company_id')->constrained('b2b_companies')->cascadeOnDelete();
            $table->foreignId('shipping_provider_id')->nullable()->constrained('b2b_shipping_providers')->nullOnDelete();
            $table->unsignedInteger('created_by');
            $table->string('transport_mode', 30);
            $table->string('origin_country', 100);
            $table->string('destination_country', 100);
            $table->string('incoterm', 10)->nullable();
            $table->string('currency', 20);
            $table->unsignedSmallInteger('estimated_days')->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('insurance_amount', 12, 2)->default(0);
            $table->decimal('customs_estimate', 12, 2)->default(0);
            $table->string('status', 30)->default('submitted');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('b2b_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number')->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('b2b_purchase_orders')->nullOnDelete();
            $table->foreignId('proforma_invoice_id')->nullable()->constrained('b2b_proforma_invoices')->nullOnDelete();
            $table->foreignId('sample_order_id')->nullable()->constrained('b2b_sample_orders')->nullOnDelete();
            $table->foreignId('shipping_quote_id')->nullable()->constrained('b2b_shipping_quotes')->nullOnDelete();
            $table->foreignId('supplier_company_id')->constrained('b2b_companies')->cascadeOnDelete();
            $table->foreignId('buyer_company_id')->constrained('b2b_companies')->cascadeOnDelete();
            $table->foreignId('shipping_provider_id')->nullable()->constrained('b2b_shipping_providers')->nullOnDelete();
            $table->unsignedInteger('created_by');
            $table->string('transport_mode', 30);
            $table->string('incoterm', 10)->nullable();
            $table->string('tracking_number', 120)->nullable();
            $table->string('origin_country', 100)->nullable();
            $table->string('destination_country', 100)->nullable();
            $table->date('estimated_departure')->nullable();
            $table->date('estimated_arrival')->nullable();
            $table->timestamp('actual_departure_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('status', 40)->default('preparing');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('b2b_shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('b2b_shipments')->cascadeOnDelete();
            $table->unsignedInteger('created_by')->nullable();
            $table->string('status', 40);
            $table->string('title')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('event_at');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('b2b_trade_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->unsignedInteger('uploaded_by');
            $table->foreignId('company_id')->nullable()->constrained('b2b_companies')->nullOnDelete();
            $table->string('document_type', 60);
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
        });

        DB::table('b2b_proforma_invoices')
            ->join('b2b_purchase_orders', 'b2b_purchase_orders.id', '=', 'b2b_proforma_invoices.purchase_order_id')
            ->update([
                'b2b_proforma_invoices.incoterm' => DB::raw('b2b_purchase_orders.incoterms'),
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_trade_documents');
        Schema::dropIfExists('b2b_shipment_events');
        Schema::dropIfExists('b2b_shipments');
        Schema::dropIfExists('b2b_shipping_quotes');
        Schema::dropIfExists('b2b_sample_orders');
        Schema::dropIfExists('b2b_shipping_providers');

        if (Schema::hasColumn('b2b_proforma_invoices', 'incoterm')) {
            Schema::table('b2b_proforma_invoices', function (Blueprint $table) {
                $table->dropColumn('incoterm');
            });
        }

        if (Schema::hasColumn('b2b_quotations', 'incoterm')) {
            Schema::table('b2b_quotations', function (Blueprint $table) {
                $table->dropColumn('incoterm');
            });
        }

        if (Schema::hasColumn('b2b_rfqs', 'incoterm')) {
            Schema::table('b2b_rfqs', function (Blueprint $table) {
                $table->dropColumn('incoterm');
            });
        }
    }
};
