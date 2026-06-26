<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $migrationMap = [
            '2019_10_13_000000_create_social_credentials_table' => function (): bool {
                return Schema::hasTable('social_credentials');
            },
            '2019_12_14_000001_create_personal_access_tokens_table' => function (): bool {
                return Schema::hasTable('personal_access_tokens');
            },
            '2021_06_07_000000_create_payku_transactions_table' => function (): bool {
                return Schema::hasTable('payku_transactions');
            },
            '2021_06_07_000001_create_payku_payments_table' => function (): bool {
                return Schema::hasTable('payku_payments');
            },
            '2021_12_15_000000_add_new_columns_to_tables' => function (): bool {
                return Schema::hasColumn('payku_transactions', 'full_name')
                    && Schema::hasColumn('payku_payments', 'payment_key')
                    && Schema::hasColumn('payku_payments', 'transaction_key')
                    && Schema::hasColumn('payku_payments', 'deposit_date');
            },
        ];

        $currentBatch = DB::table('migrations')->max('batch') ?? 1;

        foreach ($migrationMap as $migration => $shouldMarkAsRun) {
            if (DB::table('migrations')->where('migration', $migration)->exists()) {
                continue;
            }

            if ($shouldMarkAsRun()) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $currentBatch,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('migrations')->whereIn('migration', [
            '2019_10_13_000000_create_social_credentials_table',
            '2019_12_14_000001_create_personal_access_tokens_table',
            '2021_06_07_000000_create_payku_transactions_table',
            '2021_06_07_000001_create_payku_payments_table',
            '2021_12_15_000000_add_new_columns_to_tables',
        ])->delete();
    }
};
