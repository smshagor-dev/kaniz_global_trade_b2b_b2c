<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_index');
                $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_primary');
            });
        }

        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_roles_model_index');
                $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_primary');
            });
        }

        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id'], 'role_has_permissions_primary');
            });
        }

        if (!Schema::hasTable('product_categories')) {
            Schema::create('product_categories', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('product_id')->index();
                $table->integer('category_id')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('flash_deals')) {
            Schema::create('flash_deals', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('title')->nullable();
                $table->string('slug')->nullable();
                $table->unsignedTinyInteger('status')->default(0);
                $table->unsignedTinyInteger('featured')->default(0);
                $table->integer('start_date')->nullable();
                $table->integer('end_date')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('flash_deal_products')) {
            Schema::create('flash_deal_products', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('flash_deal_id')->index();
                $table->integer('product_id')->index();
                $table->double('discount', 20, 2)->default(0);
                $table->string('discount_type', 20)->default('amount');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
    }
};
