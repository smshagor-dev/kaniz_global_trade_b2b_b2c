<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('b2b_companies', 'public_slug')) {
                $table->string('public_slug')->nullable()->unique()->after('company_name');
            }
            if (!Schema::hasColumn('b2b_companies', 'public_profile_enabled')) {
                $table->boolean('public_profile_enabled')->default(false)->after('verification_status');
            }
            if (!Schema::hasColumn('b2b_companies', 'year_established')) {
                $table->unsignedSmallInteger('year_established')->nullable()->after('description');
            }
            if (!Schema::hasColumn('b2b_companies', 'employee_count')) {
                $table->string('employee_count')->nullable()->after('year_established');
            }
            if (!Schema::hasColumn('b2b_companies', 'annual_revenue')) {
                $table->string('annual_revenue')->nullable()->after('employee_count');
            }
            if (!Schema::hasColumn('b2b_companies', 'main_markets')) {
                $table->text('main_markets')->nullable()->after('annual_revenue');
            }
            if (!Schema::hasColumn('b2b_companies', 'business_scope')) {
                $table->text('business_scope')->nullable()->after('main_markets');
            }
            if (!Schema::hasColumn('b2b_companies', 'production_capacity')) {
                $table->text('production_capacity')->nullable()->after('business_scope');
            }
            if (!Schema::hasColumn('b2b_companies', 'export_percentage')) {
                $table->decimal('export_percentage', 5, 2)->nullable()->after('production_capacity');
            }
            if (!Schema::hasColumn('b2b_companies', 'factory_size')) {
                $table->string('factory_size')->nullable()->after('export_percentage');
            }
            if (!Schema::hasColumn('b2b_companies', 'factory_location')) {
                $table->string('factory_location')->nullable()->after('factory_size');
            }
            if (!Schema::hasColumn('b2b_companies', 'quality_control')) {
                $table->text('quality_control')->nullable()->after('factory_location');
            }
            if (!Schema::hasColumn('b2b_companies', 'lead_time_summary')) {
                $table->string('lead_time_summary')->nullable()->after('quality_control');
            }
            if (!Schema::hasColumn('b2b_companies', 'response_rate')) {
                $table->decimal('response_rate', 5, 2)->nullable()->after('lead_time_summary');
            }
            if (!Schema::hasColumn('b2b_companies', 'response_time_hours')) {
                $table->unsignedInteger('response_time_hours')->nullable()->after('response_rate');
            }
            if (!Schema::hasColumn('b2b_companies', 'profile_score')) {
                $table->unsignedInteger('profile_score')->default(0)->after('response_time_hours');
            }
            if (!Schema::hasColumn('b2b_companies', 'verified_supplier_badge')) {
                $table->boolean('verified_supplier_badge')->default(false)->after('profile_score');
            }
            if (!Schema::hasColumn('b2b_companies', 'featured_supplier')) {
                $table->boolean('featured_supplier')->default(false)->after('verified_supplier_badge');
            }
        });

        Schema::table('b2b_rfqs', function (Blueprint $table) {
            if (!Schema::hasColumn('b2b_rfqs', 'supplier_company_id')) {
                $table->unsignedBigInteger('supplier_company_id')->nullable()->after('b2b_company_id');
                $table->index('supplier_company_id');
            }
        });

        Schema::create('b2b_company_certifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('b2b_company_id');
            $table->string('name');
            $table->string('issuing_authority')->nullable();
            $table->string('certificate_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file')->nullable();
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('b2b_company_id');
            $table->index('verification_status');
        });

        Schema::create('b2b_company_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('b2b_company_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->unique(['b2b_company_id', 'category_id'], 'b2b_company_categories_company_category_unique');
            $table->index('b2b_company_id');
            $table->index('category_id');
        });

        $existingSlugs = DB::table('b2b_companies')
            ->whereNotNull('public_slug')
            ->pluck('public_slug')
            ->filter()
            ->values()
            ->all();

        $companies = DB::table('b2b_companies')->select('id', 'company_name')->orderBy('id')->get();
        foreach ($companies as $company) {
            $baseSlug = Str::slug($company->company_name ?: 'supplier');
            $baseSlug = $baseSlug !== '' ? $baseSlug : 'supplier';
            $slug = $baseSlug;
            $counter = 2;

            while (in_array($slug, $existingSlugs, true)) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $existingSlugs[] = $slug;

            DB::table('b2b_companies')
                ->where('id', $company->id)
                ->update(['public_slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_company_categories');
        Schema::dropIfExists('b2b_company_certifications');

        Schema::table('b2b_rfqs', function (Blueprint $table) {
            if (Schema::hasColumn('b2b_rfqs', 'supplier_company_id')) {
                $table->dropIndex(['supplier_company_id']);
                $table->dropColumn('supplier_company_id');
            }
        });

        Schema::table('b2b_companies', function (Blueprint $table) {
            if (Schema::hasColumn('b2b_companies', 'public_slug')) {
                $table->dropUnique(['public_slug']);
            }

            $columns = [
                'public_slug',
                'public_profile_enabled',
                'year_established',
                'employee_count',
                'annual_revenue',
                'main_markets',
                'business_scope',
                'production_capacity',
                'export_percentage',
                'factory_size',
                'factory_location',
                'quality_control',
                'lead_time_summary',
                'response_rate',
                'response_time_hours',
                'profile_score',
                'verified_supplier_badge',
                'featured_supplier',
            ];

            $existingColumns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn('b2b_companies', $column)));
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
