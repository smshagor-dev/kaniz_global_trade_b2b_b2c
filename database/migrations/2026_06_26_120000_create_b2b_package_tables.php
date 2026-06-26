<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_packages')) {
            Schema::create('b2b_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('package_for', 30);
                $table->double('amount', 11, 2)->default(0);
                $table->unsignedInteger('duration')->default(30);
                $table->unsignedInteger('rfq_limit')->default(0);
                $table->unsignedInteger('quotation_limit')->default(0);
                $table->unsignedInteger('product_limit')->default(0);
                $table->unsignedInteger('member_limit')->default(1);
                $table->boolean('priority_listing')->default(false);
                $table->boolean('featured_profile')->default(false);
                $table->boolean('verified_badge')->default(false);
                $table->boolean('analytics_access')->default(false);
                $table->boolean('dedicated_support')->default(false);
                $table->string('logo')->nullable();
                $table->string('highlight_text')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('b2b_package_requests')) {
            Schema::create('b2b_package_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('b2b_company_id')->constrained('b2b_companies')->cascadeOnDelete();
                $table->foreignId('b2b_package_id')->constrained('b2b_packages')->cascadeOnDelete();
                $table->unsignedInteger('requested_by');
                $table->unsignedInteger('approved_by')->nullable();
                $table->double('amount', 11, 2)->default(0);
                $table->string('status', 30)->default('pending');
                $table->text('note')->nullable();
                $table->text('rejection_note')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('b2b_companies')) {
            Schema::table('b2b_companies', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_companies', 'b2b_package_id')) {
                    $table->foreignId('b2b_package_id')->nullable()->after('bank_check_file')->constrained('b2b_packages')->nullOnDelete();
                }
                if (!Schema::hasColumn('b2b_companies', 'package_started_at')) {
                    $table->timestamp('package_started_at')->nullable()->after('b2b_package_id');
                }
                if (!Schema::hasColumn('b2b_companies', 'package_expires_at')) {
                    $table->timestamp('package_expires_at')->nullable()->after('package_started_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_companies')) {
            Schema::table('b2b_companies', function (Blueprint $table) {
                foreach (['package_expires_at', 'package_started_at'] as $column) {
                    if (Schema::hasColumn('b2b_companies', $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn('b2b_companies', 'b2b_package_id')) {
                    $table->dropConstrainedForeignId('b2b_package_id');
                }
            });
        }

        Schema::dropIfExists('b2b_package_requests');
        Schema::dropIfExists('b2b_packages');
    }
};
