<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_premium_verification_packages')) {
            Schema::create('b2b_premium_verification_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->double('amount', 11, 2)->default(0);
                $table->string('logo')->nullable();
                $table->string('highlight_text')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('b2b_premium_verification_requests')) {
            Schema::create('b2b_premium_verification_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_company_id')->constrained('b2b_companies')->cascadeOnDelete();
                $table->unsignedBigInteger('b2b_premium_verification_package_id');
                $table->foreign('b2b_premium_verification_package_id', 'b2b_prem_verify_req_pkg_fk')
                    ->references('id')
                    ->on('b2b_premium_verification_packages')
                    ->cascadeOnDelete();
                $table->unsignedInteger('requested_by');
                $table->unsignedInteger('approved_by')->nullable();
                $table->double('amount', 11, 2)->default(0);
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

        if (Schema::hasTable('b2b_companies')) {
            Schema::table('b2b_companies', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_companies', 'premium_verification_package_id')) {
                    $table->foreignId('premium_verification_package_id')->nullable()->after('product_promotion_package_id')->constrained('b2b_premium_verification_packages')->nullOnDelete();
                }
                if (!Schema::hasColumn('b2b_companies', 'premium_verified')) {
                    $table->boolean('premium_verified')->default(false)->after('verified_supplier_badge');
                }
                if (!Schema::hasColumn('b2b_companies', 'premium_verified_at')) {
                    $table->timestamp('premium_verified_at')->nullable()->after('product_promotion_expires_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_companies')) {
            Schema::table('b2b_companies', function (Blueprint $table) {
                foreach (['premium_verified_at', 'premium_verified'] as $column) {
                    if (Schema::hasColumn('b2b_companies', $column)) {
                        $table->dropColumn($column);
                    }
                }

                if (Schema::hasColumn('b2b_companies', 'premium_verification_package_id')) {
                    $table->dropConstrainedForeignId('premium_verification_package_id');
                }
            });
        }

        Schema::dropIfExists('b2b_premium_verification_requests');
        Schema::dropIfExists('b2b_premium_verification_packages');
    }
};
