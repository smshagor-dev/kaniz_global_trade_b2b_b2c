<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_product_promotion_packages')) {
            Schema::create('b2b_product_promotion_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->double('amount', 11, 2)->default(0);
                $table->unsignedInteger('duration')->default(30);
                $table->unsignedInteger('product_limit')->default(1);
                $table->string('logo')->nullable();
                $table->string('highlight_text')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('b2b_product_promotion_requests')) {
            Schema::create('b2b_product_promotion_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_company_id')->constrained('b2b_companies')->cascadeOnDelete();
                $table->unsignedBigInteger('b2b_product_promotion_package_id');
                $table->foreign('b2b_product_promotion_package_id', 'b2b_promo_req_pkg_fk')
                    ->references('id')
                    ->on('b2b_product_promotion_packages')
                    ->cascadeOnDelete();
                $table->unsignedInteger('requested_by');
                $table->unsignedInteger('approved_by')->nullable();
                $table->double('amount', 11, 2)->default(0);
                $table->string('billing_cycle', 40)->nullable();
                $table->string('status', 30)->default('pending');
                $table->text('note')->nullable();
                $table->string('payment_reference')->nullable();
                $table->text('payment_notes')->nullable();
                $table->timestamp('payment_submitted_at')->nullable();
                $table->text('rejection_note')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('b2b_product_promotions')) {
            Schema::create('b2b_product_promotions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_company_id')->constrained('b2b_companies')->cascadeOnDelete();
                $table->unsignedInteger('product_id');
                $table->unsignedBigInteger('b2b_product_promotion_package_id')->nullable();
                $table->foreign('b2b_product_promotion_package_id', 'b2b_promo_pkg_fk')
                    ->references('id')
                    ->on('b2b_product_promotion_packages')
                    ->nullOnDelete();
                $table->string('status', 30)->default('active');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('b2b_companies')) {
            Schema::table('b2b_companies', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_companies', 'product_promotion_package_id')) {
                    $table->foreignId('product_promotion_package_id')->nullable()->after('featured_b2b_package_id')->constrained('b2b_product_promotion_packages')->nullOnDelete();
                }
                if (!Schema::hasColumn('b2b_companies', 'product_promotion_started_at')) {
                    $table->timestamp('product_promotion_started_at')->nullable()->after('featured_package_expires_at');
                }
                if (!Schema::hasColumn('b2b_companies', 'product_promotion_expires_at')) {
                    $table->timestamp('product_promotion_expires_at')->nullable()->after('product_promotion_started_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_companies')) {
            Schema::table('b2b_companies', function (Blueprint $table) {
                foreach (['product_promotion_expires_at', 'product_promotion_started_at'] as $column) {
                    if (Schema::hasColumn('b2b_companies', $column)) {
                        $table->dropColumn($column);
                    }
                }

                if (Schema::hasColumn('b2b_companies', 'product_promotion_package_id')) {
                    $table->dropConstrainedForeignId('product_promotion_package_id');
                }
            });
        }

        Schema::dropIfExists('b2b_product_promotions');
        Schema::dropIfExists('b2b_product_promotion_requests');
        Schema::dropIfExists('b2b_product_promotion_packages');
    }
};
