<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->unique();
            $table->string('company_name');
            $table->enum('company_type', ['buyer', 'supplier', 'manufacturer', 'distributor', 'wholesaler', 'retailer']);
            $table->string('legal_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('country', 100);
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 50);
            $table->string('business_email');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('trade_license_file')->nullable();
            $table->string('tax_document_file')->nullable();
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('verification_note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('verified_by')->nullable();
            $table->timestamps();

            $table->index('company_type');
            $table->index('verification_status');
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_companies');
    }
};
