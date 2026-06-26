<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_companies', function (Blueprint $table) {
            $table->string('bank_account_name')->nullable()->after('tax_document_file');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
            $table->string('bank_name')->nullable()->after('bank_account_number');
            $table->string('bank_branch_name')->nullable()->after('bank_name');
            $table->string('bank_branch_address')->nullable()->after('bank_branch_name');
            $table->string('bank_country', 100)->nullable()->after('bank_branch_address');
            $table->string('swift_code')->nullable()->after('bank_country');
            $table->string('iban')->nullable()->after('swift_code');
            $table->string('bank_check_file')->nullable()->after('iban');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_companies', function (Blueprint $table) {
            $table->dropColumn([
                'bank_account_name',
                'bank_account_number',
                'bank_name',
                'bank_branch_name',
                'bank_branch_address',
                'bank_country',
                'swift_code',
                'iban',
                'bank_check_file',
            ]);
        });
    }
};
