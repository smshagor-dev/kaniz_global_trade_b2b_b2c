<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_shipping_providers')) {
            Schema::table('b2b_shipping_providers', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_shipping_providers', 'provider_type')) {
                    $table->string('provider_type', 20)->default('manual')->after('transport_mode');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'api_driver')) {
                    $table->string('api_driver', 30)->nullable()->after('provider_type');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'api_base_url')) {
                    $table->string('api_base_url')->nullable()->after('api_driver');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'api_key')) {
                    $table->text('api_key')->nullable()->after('api_base_url');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'api_secret')) {
                    $table->text('api_secret')->nullable()->after('api_key');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'account_number')) {
                    $table->text('account_number')->nullable()->after('api_secret');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'webhook_secret')) {
                    $table->text('webhook_secret')->nullable()->after('account_number');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'is_test_mode')) {
                    $table->boolean('is_test_mode')->default(true)->after('webhook_secret');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'supported_services')) {
                    $table->text('supported_services')->nullable()->after('supported_countries');
                }
            });
        }

        if (Schema::hasTable('b2b_shipments')) {
            Schema::table('b2b_shipments', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_shipments', 'carrier_reference')) {
                    $table->string('carrier_reference', 120)->nullable()->after('tracking_number');
                }
                if (!Schema::hasColumn('b2b_shipments', 'carrier_service')) {
                    $table->string('carrier_service', 120)->nullable()->after('carrier_reference');
                }
                if (!Schema::hasColumn('b2b_shipments', 'carrier_status')) {
                    $table->string('carrier_status', 120)->nullable()->after('carrier_service');
                }
                if (!Schema::hasColumn('b2b_shipments', 'last_tracked_at')) {
                    $table->timestamp('last_tracked_at')->nullable()->after('carrier_status');
                }
                if (!Schema::hasColumn('b2b_shipments', 'tracking_url')) {
                    $table->string('tracking_url')->nullable()->after('last_tracked_at');
                }
                if (!Schema::hasColumn('b2b_shipments', 'live_tracking_enabled')) {
                    $table->boolean('live_tracking_enabled')->default(false)->after('tracking_url');
                }
                if (!Schema::hasColumn('b2b_shipments', 'sync_error')) {
                    $table->text('sync_error')->nullable()->after('live_tracking_enabled');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_shipments')) {
            Schema::table('b2b_shipments', function (Blueprint $table) {
                foreach (['carrier_reference', 'carrier_service', 'carrier_status', 'last_tracked_at', 'tracking_url', 'live_tracking_enabled', 'sync_error'] as $column) {
                    if (Schema::hasColumn('b2b_shipments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('b2b_shipping_providers')) {
            Schema::table('b2b_shipping_providers', function (Blueprint $table) {
                foreach (['provider_type', 'api_driver', 'api_base_url', 'api_key', 'api_secret', 'account_number', 'webhook_secret', 'is_test_mode', 'supported_services'] as $column) {
                    if (Schema::hasColumn('b2b_shipping_providers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
