<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('supplier_user_id');
            $table->unsignedBigInteger('supplier_company_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('price', 20, 2);
            $table->string('currency', 20);
            $table->decimal('moq', 20, 2)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->string('shipping_terms')->nullable();
            $table->string('payment_terms')->nullable();
            $table->longText('message')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->timestamps();

            $table->index('rfq_id');
            $table->index('supplier_user_id');
            $table->index('supplier_company_id');
            $table->index('product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_quotations');
    }
};
