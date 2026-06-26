<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('seller_packages')) {
            Schema::create('seller_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->double('amount', 11, 2)->default(0);
                $table->integer('product_upload_limit')->default(0);
                $table->integer('preorder_product_upload_limit')->default(0);
                $table->string('logo')->nullable();
                $table->integer('duration')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('seller_package_translations')) {
            Schema::create('seller_package_translations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_package_id');
                $table->string('name', 50);
                $table->string('lang', 100);
                $table->timestamps();

                $table->foreign('seller_package_id')
                    ->references('id')
                    ->on('seller_packages')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('seller_package_payments')) {
            Schema::create('seller_package_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->unsignedBigInteger('seller_package_id');
                $table->double('amount', 20, 2);
                $table->string('payment_method')->nullable();
                $table->longText('payment_details')->nullable();
                $table->boolean('approval')->default(false);
                $table->tinyInteger('offline_payment')->default(0)->comment('1=offline payment, 2=online payment');
                $table->string('reciept', 150)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('shops') && !Schema::hasColumn('shops', 'preorder_product_upload_limit')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->integer('preorder_product_upload_limit')->default(0)->after('product_upload_limit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shops') && Schema::hasColumn('shops', 'preorder_product_upload_limit')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->dropColumn('preorder_product_upload_limit');
            });
        }

        Schema::dropIfExists('seller_package_payments');
        Schema::dropIfExists('seller_package_translations');
        Schema::dropIfExists('seller_packages');
    }
};
