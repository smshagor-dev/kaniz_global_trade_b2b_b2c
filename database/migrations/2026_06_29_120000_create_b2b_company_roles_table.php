<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_company_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('b2b_company_id');
            $table->string('name');
            $table->string('slug');
            $table->json('permissions')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['b2b_company_id', 'slug'], 'b2b_company_roles_company_slug_unique');
            $table->index('b2b_company_id');
        });

        Schema::table('b2b_company_members', function (Blueprint $table) {
            $table->unsignedBigInteger('custom_role_id')->nullable()->after('role');
        });

        Schema::table('b2b_company_invitations', function (Blueprint $table) {
            $table->unsignedBigInteger('custom_role_id')->nullable()->after('role');
        });

        DB::statement("ALTER TABLE b2b_company_members MODIFY role VARCHAR(100) NOT NULL");
        DB::statement("ALTER TABLE b2b_company_invitations MODIFY role VARCHAR(100) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE b2b_company_members MODIFY role ENUM('owner', 'admin', 'procurement_manager', 'sales_manager', 'finance_manager', 'logistics_manager', 'viewer') NOT NULL");
        DB::statement("ALTER TABLE b2b_company_invitations MODIFY role ENUM('owner', 'admin', 'procurement_manager', 'sales_manager', 'finance_manager', 'logistics_manager', 'viewer') NOT NULL");

        Schema::table('b2b_company_members', function (Blueprint $table) {
            $table->dropColumn('custom_role_id');
        });

        Schema::table('b2b_company_invitations', function (Blueprint $table) {
            $table->dropColumn('custom_role_id');
        });

        Schema::dropIfExists('b2b_company_roles');
    }
};
