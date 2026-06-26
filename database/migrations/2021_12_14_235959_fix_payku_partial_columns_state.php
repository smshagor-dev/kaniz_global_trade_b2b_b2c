<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payku_transactions') && !Schema::hasColumn('payku_transactions', 'full_name')) {
            Schema::table('payku_transactions', function (Blueprint $table) {
                $table->string('full_name')->nullable();
            });
        }

        if (Schema::hasTable('payku_payments')) {
            Schema::table('payku_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payku_payments', 'payment_key')) {
                    $table->string('payment_key')->nullable();
                }

                if (!Schema::hasColumn('payku_payments', 'transaction_key')) {
                    $table->string('transaction_key')->nullable();
                }

                if (!Schema::hasColumn('payku_payments', 'deposit_date')) {
                    $table->dateTime('deposit_date')->nullable();
                }
            });
        }

        if (
            Schema::hasTable('payku_transactions')
            && Schema::hasColumn('payku_transactions', 'full_name')
            && Schema::hasTable('payku_payments')
            && Schema::hasColumn('payku_payments', 'payment_key')
            && Schema::hasColumn('payku_payments', 'transaction_key')
            && Schema::hasColumn('payku_payments', 'deposit_date')
            && !DB::table('migrations')->where('migration', '2021_12_15_000000_add_new_columns_to_tables')->exists()
        ) {
            $currentBatch = DB::table('migrations')->max('batch') ?? 1;

            DB::table('migrations')->insert([
                'migration' => '2021_12_15_000000_add_new_columns_to_tables',
                'batch' => $currentBatch,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('migrations')->where('migration', '2021_12_15_000000_add_new_columns_to_tables')->delete();
    }
};
