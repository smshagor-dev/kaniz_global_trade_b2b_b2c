<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_rfqs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('b2b_company_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->longText('description');
            $table->decimal('quantity', 20, 2);
            $table->string('unit')->nullable();
            $table->decimal('target_price', 20, 2)->nullable();
            $table->string('currency', 20)->nullable();
            $table->string('destination_country', 100)->nullable();
            $table->string('destination_city', 100)->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['open', 'quoted', 'closed', 'cancelled'])->default('open');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('b2b_company_id');
            $table->index('product_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_rfqs');
    }
};
