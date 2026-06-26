<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_company_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('b2b_company_id');
            $table->unsignedInteger('user_id');
            $table->enum('role', ['owner', 'admin', 'procurement_manager', 'sales_manager', 'finance_manager', 'logistics_manager', 'viewer']);
            $table->enum('status', ['invited', 'active', 'suspended', 'removed'])->default('active');
            $table->unsignedInteger('invited_by')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['b2b_company_id', 'user_id'], 'b2b_company_members_company_user_unique');
            $table->index('user_id');
            $table->index('role');
            $table->index('status');
        });

        Schema::create('b2b_company_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('b2b_company_id');
            $table->string('email');
            $table->enum('role', ['owner', 'admin', 'procurement_manager', 'sales_manager', 'finance_manager', 'logistics_manager', 'viewer']);
            $table->string('token')->unique();
            $table->enum('status', ['pending', 'accepted', 'expired', 'cancelled'])->default('pending');
            $table->unsignedInteger('invited_by')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index('b2b_company_id');
            $table->index('email');
            $table->index('status');
        });

        $companies = DB::table('b2b_companies')->select('id', 'user_id', 'created_at', 'updated_at')->get();
        foreach ($companies as $company) {
            DB::table('b2b_company_members')->updateOrInsert(
                [
                    'b2b_company_id' => $company->id,
                    'user_id' => $company->user_id,
                ],
                [
                    'role' => 'owner',
                    'status' => 'active',
                    'invited_by' => null,
                    'joined_at' => $company->created_at ?? now(),
                    'created_at' => $company->created_at ?? now(),
                    'updated_at' => $company->updated_at ?? now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_company_invitations');
        Schema::dropIfExists('b2b_company_members');
    }
};
