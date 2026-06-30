<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_company_catalogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('b2b_company_id');
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('cover_image')->nullable();
            $table->unsignedBigInteger('pdf_file')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['b2b_company_id', 'slug'], 'b2b_company_catalogs_company_slug_unique');
            $table->index('b2b_company_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('b2b_company_catalog_id')->nullable()->after('pdf');
            $table->index('b2b_company_catalog_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['b2b_company_catalog_id']);
            $table->dropColumn('b2b_company_catalog_id');
        });

        Schema::dropIfExists('b2b_company_catalogs');
    }
};
