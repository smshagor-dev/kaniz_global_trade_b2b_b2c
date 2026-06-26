<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedInteger('buyer_user_id');
            $table->unsignedInteger('supplier_user_id');
            $table->unsignedBigInteger('buyer_company_id');
            $table->unsignedBigInteger('supplier_company_id');
            $table->string('currency', 20);
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('shipping_amount', 20, 2)->default(0);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('grand_total', 20, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'sent', 'accepted', 'expired', 'cancelled'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('invoice_number');
            $table->index('purchase_order_id');
            $table->index('buyer_user_id');
            $table->index('supplier_user_id');
            $table->index('buyer_company_id');
            $table->index('supplier_company_id');
            $table->index('status');
        });

        Schema::create('b2b_proforma_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proforma_invoice_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 20, 2);
            $table->string('unit', 100)->nullable();
            $table->decimal('unit_price', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('line_total', 20, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('proforma_invoice_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_proforma_invoice_items');
        Schema::dropIfExists('b2b_proforma_invoices');
    }
};
