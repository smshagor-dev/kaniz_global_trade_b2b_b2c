<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_negotiations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('buyer_company_id');
            $table->unsignedBigInteger('supplier_company_id');
            $table->unsignedInteger('buyer_user_id');
            $table->unsignedInteger('supplier_user_id');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index('rfq_id');
            $table->index('quotation_id');
            $table->index('purchase_order_id');
            $table->index('buyer_company_id');
            $table->index('supplier_company_id');
        });

        Schema::create('b2b_negotiation_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('negotiation_id');
            $table->unsignedInteger('sender_user_id');
            $table->unsignedBigInteger('sender_company_id')->nullable();
            $table->enum('sender_role', ['buyer', 'supplier', 'admin', 'system'])->default('buyer');
            $table->enum('message_type', ['message', 'price_change', 'attachment', 'status_change', 'system'])->default('message');
            $table->longText('message')->nullable();
            $table->string('attachment')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('buyer_read_at')->nullable();
            $table->timestamp('supplier_read_at')->nullable();
            $table->timestamps();

            $table->index('negotiation_id');
            $table->index('sender_user_id');
            $table->index('sender_company_id');
            $table->index('message_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_negotiation_messages');
        Schema::dropIfExists('b2b_negotiations');
    }
};
