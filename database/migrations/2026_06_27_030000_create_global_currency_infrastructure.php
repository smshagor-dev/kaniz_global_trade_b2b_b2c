<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_api_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->default('exchange_rate_api');
            $table->string('driver', 50)->default('exchange_rate_api');
            $table->string('base_currency_code', 20)->nullable();
            $table->string('default_display_currency_code', 20)->nullable();
            $table->string('sync_frequency', 20)->default('hourly');
            $table->boolean('auto_sync_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('credentials')->nullable();
            $table->json('custom_rates')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_sync_status', 30)->nullable();
            $table->text('last_error')->nullable();
            $table->longText('last_response')->nullable();
            $table->timestamps();
        });

        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency_code', 20);
            $table->string('currency_code', 20);
            $table->decimal('rate', 20, 8)->default(1);
            $table->string('provider', 50)->nullable();
            $table->boolean('is_manual_override')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['base_currency_code', 'currency_code'], 'currency_exchange_rates_unique_pair');
        });

        Schema::create('currency_rate_history', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency_code', 20);
            $table->string('currency_code', 20);
            $table->decimal('rate', 20, 8)->default(1);
            $table->string('provider', 50)->nullable();
            $table->string('sync_batch', 50)->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::table('currencies', function (Blueprint $table) {
            if (!Schema::hasColumn('currencies', 'decimal_places')) {
                $table->unsignedTinyInteger('decimal_places')->default(2)->after('exchange_rate');
            }
            if (!Schema::hasColumn('currencies', 'symbol_position')) {
                $table->string('symbol_position', 30)->default('prefix')->after('decimal_places');
            }
            if (!Schema::hasColumn('currencies', 'decimal_separator')) {
                $table->string('decimal_separator', 20)->default('dot')->after('symbol_position');
            }
            if (!Schema::hasColumn('currencies', 'thousands_separator')) {
                $table->string('thousands_separator', 20)->default('comma')->after('decimal_separator');
            }
            if (!Schema::hasColumn('currencies', 'is_base_currency')) {
                $table->boolean('is_base_currency')->default(false)->after('thousands_separator');
            }
            if (!Schema::hasColumn('currencies', 'is_default_display_currency')) {
                $table->boolean('is_default_display_currency')->default(false)->after('is_base_currency');
            }
        });

        $baseCurrencyId = DB::table('business_settings')->where('type', 'system_default_currency')->value('value');
        $baseCurrency = $baseCurrencyId ? DB::table('currencies')->where('id', $baseCurrencyId)->first() : DB::table('currencies')->first();

        if ($baseCurrency) {
            DB::table('currencies')->where('id', $baseCurrency->id)->update([
                'is_base_currency' => true,
                'is_default_display_currency' => true,
            ]);

            DB::table('currency_api_settings')->insert([
                'provider' => 'exchange_rate_api',
                'driver' => 'exchange_rate_api',
                'base_currency_code' => $baseCurrency->code,
                'default_display_currency_code' => $baseCurrency->code,
                'sync_frequency' => 'hourly',
                'auto_sync_enabled' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $currencies = DB::table('currencies')->get(['code', 'exchange_rate']);
            foreach ($currencies as $currency) {
                DB::table('currency_exchange_rates')->insert([
                    'base_currency_code' => $baseCurrency->code,
                    'currency_code' => $currency->code,
                    'rate' => $currency->exchange_rate,
                    'provider' => 'seeded_existing_currency_table',
                    'is_manual_override' => true,
                    'synced_at' => now(),
                    'source_updated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rate_history');
        Schema::dropIfExists('currency_exchange_rates');
        Schema::dropIfExists('currency_api_settings');

        Schema::table('currencies', function (Blueprint $table) {
            foreach ([
                'is_default_display_currency',
                'is_base_currency',
                'thousands_separator',
                'decimal_separator',
                'symbol_position',
                'decimal_places',
            ] as $column) {
                if (Schema::hasColumn('currencies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
