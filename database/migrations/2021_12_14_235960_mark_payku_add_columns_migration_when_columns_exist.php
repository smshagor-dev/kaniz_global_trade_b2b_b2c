<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('migrations')->where('migration', '2021_12_15_000000_add_new_columns_to_tables')->exists()) {
            return;
        }

        $paykuColumnsReady = Schema::hasTable('payku_transactions')
            && Schema::hasColumn('payku_transactions', 'full_name')
            && Schema::hasTable('payku_payments')
            && Schema::hasColumn('payku_payments', 'payment_key')
            && Schema::hasColumn('payku_payments', 'transaction_key')
            && Schema::hasColumn('payku_payments', 'deposit_date');

        if (!$paykuColumnsReady) {
            return;
        }

        $currentBatch = DB::table('migrations')->max('batch') ?? 1;

        DB::table('migrations')->insert([
            'migration' => '2021_12_15_000000_add_new_columns_to_tables',
            'batch' => $currentBatch,
        ]);
    }

    public function down(): void
    {
        DB::table('migrations')->where('migration', '2021_12_15_000000_add_new_columns_to_tables')->delete();
    }
};
