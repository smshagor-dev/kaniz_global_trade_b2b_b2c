<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->unsignedInteger('buyer_user_id');
            $table->unsignedInteger('supplier_user_id');
            $table->unsignedBigInteger('buyer_company_id');
            $table->unsignedBigInteger('supplier_company_id');
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('quotation_id');
            $table->string('currency', 20);
            $table->string('payment_terms')->nullable();
            $table->string('shipping_terms')->nullable();
            $table->string('incoterms', 50)->nullable();
            $table->longText('delivery_address')->nullable();
            $table->date('delivery_deadline')->nullable();
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'cancelled', 'completed'])->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('supplier_reviewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('po_number');
            $table->index('buyer_user_id');
            $table->index('supplier_user_id');
            $table->index('buyer_company_id');
            $table->index('supplier_company_id');
            $table->index('rfq_id');
            $table->index('quotation_id');
            $table->index('status');
        });

        Schema::create('b2b_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 20, 2);
            $table->string('unit', 100)->nullable();
            $table->decimal('unit_price', 20, 2)->default(0);
            $table->decimal('line_total', 20, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_purchase_order_items');
        Schema::dropIfExists('b2b_purchase_orders');
    }
};
