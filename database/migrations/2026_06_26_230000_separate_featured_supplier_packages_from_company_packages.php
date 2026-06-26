<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_packages')) {
            Schema::table('b2b_packages', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_packages', 'package_type')) {
                    $table->string('package_type', 40)->default('membership')->after('package_for');
                }
            });

            DB::table('b2b_packages')
                ->where('featured_profile', true)
                ->where('package_for', 'supplier')
                ->update(['package_type' => 'supplier_featured']);

            DB::table('b2b_packages')
                ->whereNull('package_type')
                ->update(['package_type' => 'membership']);
        }

        if (Schema::hasTable('b2b_companies')) {
            Schema::table('b2b_companies', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_companies', 'featured_b2b_package_id')) {
                    $table->foreignId('featured_b2b_package_id')->nullable()->after('b2b_package_id')->constrained('b2b_packages')->nullOnDelete();
                }
                if (!Schema::hasColumn('b2b_companies', 'featured_package_started_at')) {
                    $table->timestamp('featured_package_started_at')->nullable()->after('package_expires_at');
                }
                if (!Schema::hasColumn('b2b_companies', 'featured_package_expires_at')) {
                    $table->timestamp('featured_package_expires_at')->nullable()->after('featured_package_started_at');
                }
            });

            DB::statement("
                UPDATE b2b_companies c
                INNER JOIN b2b_packages p ON p.id = c.b2b_package_id
                SET
                    c.featured_b2b_package_id = c.b2b_package_id,
                    c.featured_package_started_at = c.package_started_at,
                    c.featured_package_expires_at = c.package_expires_at,
                    c.b2b_package_id = NULL,
                    c.package_started_at = NULL,
                    c.package_expires_at = NULL
                WHERE p.package_type = 'supplier_featured'
            ");
        }

        if (Schema::hasTable('b2b_package_requests')) {
            Schema::table('b2b_package_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_package_requests', 'request_type')) {
                    $table->string('request_type', 40)->default('membership')->after('b2b_package_id');
                }
            });

            DB::statement("
                UPDATE b2b_package_requests r
                INNER JOIN b2b_packages p ON p.id = r.b2b_package_id
                SET r.request_type = CASE
                    WHEN p.package_type = 'supplier_featured' THEN 'supplier_featured'
                    ELSE 'membership'
                END
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_package_requests') && Schema::hasColumn('b2b_package_requests', 'request_type')) {
            Schema::table('b2b_package_requests', function (Blueprint $table) {
                $table->dropColumn('request_type');
            });
        }

        if (Schema::hasTable('b2b_companies')) {
            Schema::table('b2b_companies', function (Blueprint $table) {
                foreach (['featured_package_expires_at', 'featured_package_started_at'] as $column) {
                    if (Schema::hasColumn('b2b_companies', $column)) {
                        $table->dropColumn($column);
                    }
                }

                if (Schema::hasColumn('b2b_companies', 'featured_b2b_package_id')) {
                    $table->dropConstrainedForeignId('featured_b2b_package_id');
                }
            });
        }

        if (Schema::hasTable('b2b_packages') && Schema::hasColumn('b2b_packages', 'package_type')) {
            Schema::table('b2b_packages', function (Blueprint $table) {
                $table->dropColumn('package_type');
            });
        }
    }
};
