<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_company_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedInteger('reviewer_user_id');
            $table->unsignedBigInteger('reviewer_company_id');
            $table->unsignedInteger('reviewed_user_id');
            $table->unsignedBigInteger('reviewed_company_id');
            $table->enum('reviewer_role', ['buyer', 'supplier']);
            $table->enum('reviewed_role', ['buyer', 'supplier']);
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['purchase_order_id', 'reviewer_company_id'], 'b2b_company_reviews_po_reviewer_unique');
            $table->index('purchase_order_id');
            $table->index('reviewer_company_id');
            $table->index('reviewed_company_id');
            $table->index('reviewed_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_company_reviews');
    }
};
