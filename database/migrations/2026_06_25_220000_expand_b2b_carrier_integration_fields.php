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
                if (!Schema::hasColumn('b2b_shipping_providers', 'username')) {
                    $table->text('username')->nullable()->after('account_number');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'password')) {
                    $table->text('password')->nullable()->after('username');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'oauth_token')) {
                    $table->text('oauth_token')->nullable()->after('password');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'refresh_token')) {
                    $table->text('refresh_token')->nullable()->after('oauth_token');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'environment')) {
                    $table->string('environment', 40)->nullable()->after('refresh_token');
                }
                if (!Schema::hasColumn('b2b_shipping_providers', 'custom_config')) {
                    $table->json('custom_config')->nullable()->after('environment');
                }
            });
        }

        if (Schema::hasTable('b2b_shipping_quotes')) {
            Schema::table('b2b_shipping_quotes', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_shipping_quotes', 'service_type')) {
                    $table->string('service_type', 120)->nullable()->after('incoterm');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'delivery_priority')) {
                    $table->string('delivery_priority', 40)->nullable()->after('service_type');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'total_weight')) {
                    $table->decimal('total_weight', 12, 3)->nullable()->after('delivery_priority');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'package_length')) {
                    $table->decimal('package_length', 12, 2)->nullable()->after('total_weight');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'package_width')) {
                    $table->decimal('package_width', 12, 2)->nullable()->after('package_length');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'package_height')) {
                    $table->decimal('package_height', 12, 2)->nullable()->after('package_width');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'rate_request_payload')) {
                    $table->json('rate_request_payload')->nullable()->after('notes');
                }
                if (!Schema::hasColumn('b2b_shipping_quotes', 'rate_response_payload')) {
                    $table->longText('rate_response_payload')->nullable()->after('rate_request_payload');
                }
            });
        }

        if (Schema::hasTable('b2b_shipments')) {
            Schema::table('b2b_shipments', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_shipments', 'service_type')) {
                    $table->string('service_type', 120)->nullable()->after('carrier_service');
                }
                if (!Schema::hasColumn('b2b_shipments', 'delivery_priority')) {
                    $table->string('delivery_priority', 40)->nullable()->after('service_type');
                }
                if (!Schema::hasColumn('b2b_shipments', 'currency')) {
                    $table->string('currency', 20)->nullable()->after('incoterm');
                }
                if (!Schema::hasColumn('b2b_shipments', 'declared_value')) {
                    $table->decimal('declared_value', 12, 2)->nullable()->after('currency');
                }
                if (!Schema::hasColumn('b2b_shipments', 'insurance_amount')) {
                    $table->decimal('insurance_amount', 12, 2)->nullable()->after('declared_value');
                }
                if (!Schema::hasColumn('b2b_shipments', 'total_weight')) {
                    $table->decimal('total_weight', 12, 3)->nullable()->after('insurance_amount');
                }
                if (!Schema::hasColumn('b2b_shipments', 'package_length')) {
                    $table->decimal('package_length', 12, 2)->nullable()->after('total_weight');
                }
                if (!Schema::hasColumn('b2b_shipments', 'package_width')) {
                    $table->decimal('package_width', 12, 2)->nullable()->after('package_length');
                }
                if (!Schema::hasColumn('b2b_shipments', 'package_height')) {
                    $table->decimal('package_height', 12, 2)->nullable()->after('package_width');
                }
                if (!Schema::hasColumn('b2b_shipments', 'carrier_payload')) {
                    $table->json('carrier_payload')->nullable()->after('notes');
                }
                if (!Schema::hasColumn('b2b_shipments', 'last_carrier_response')) {
                    $table->longText('last_carrier_response')->nullable()->after('carrier_payload');
                }
                if (!Schema::hasColumn('b2b_shipments', 'current_location')) {
                    $table->string('current_location', 120)->nullable()->after('last_carrier_response');
                }
                if (!Schema::hasColumn('b2b_shipments', 'current_country')) {
                    $table->string('current_country', 10)->nullable()->after('current_location');
                }
                if (!Schema::hasColumn('b2b_shipments', 'estimated_delivery_at')) {
                    $table->timestamp('estimated_delivery_at')->nullable()->after('current_country');
                }
                if (!Schema::hasColumn('b2b_shipments', 'signed_receiver')) {
                    $table->string('signed_receiver', 120)->nullable()->after('estimated_delivery_at');
                }
                if (!Schema::hasColumn('b2b_shipments', 'proof_of_delivery_url')) {
                    $table->string('proof_of_delivery_url')->nullable()->after('signed_receiver');
                }
                if (!Schema::hasColumn('b2b_shipments', 'pickup_scheduled_at')) {
                    $table->timestamp('pickup_scheduled_at')->nullable()->after('proof_of_delivery_url');
                }
                if (!Schema::hasColumn('b2b_shipments', 'pickup_confirmation')) {
                    $table->string('pickup_confirmation', 120)->nullable()->after('pickup_scheduled_at');
                }
                if (!Schema::hasColumn('b2b_shipments', 'pickup_status')) {
                    $table->string('pickup_status', 60)->nullable()->after('pickup_confirmation');
                }
                if (!Schema::hasColumn('b2b_shipments', 'latest_label_path')) {
                    $table->string('latest_label_path')->nullable()->after('pickup_status');
                }
                if (!Schema::hasColumn('b2b_shipments', 'latest_label_format')) {
                    $table->string('latest_label_format', 20)->nullable()->after('latest_label_path');
                }
                if (!Schema::hasColumn('b2b_shipments', 'rate_request_payload')) {
                    $table->json('rate_request_payload')->nullable()->after('latest_label_format');
                }
                if (!Schema::hasColumn('b2b_shipments', 'rate_response_payload')) {
                    $table->longText('rate_response_payload')->nullable()->after('rate_request_payload');
                }
            });
        }

        if (Schema::hasTable('b2b_shipment_events')) {
            Schema::table('b2b_shipment_events', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_shipment_events', 'carrier_event')) {
                    $table->string('carrier_event', 120)->nullable()->after('status');
                }
                if (!Schema::hasColumn('b2b_shipment_events', 'city')) {
                    $table->string('city', 100)->nullable()->after('location');
                }
                if (!Schema::hasColumn('b2b_shipment_events', 'country')) {
                    $table->string('country', 10)->nullable()->after('city');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('b2b_shipment_events')) {
            Schema::table('b2b_shipment_events', function (Blueprint $table) {
                foreach (['carrier_event', 'city', 'country'] as $column) {
                    if (Schema::hasColumn('b2b_shipment_events', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('b2b_shipments')) {
            Schema::table('b2b_shipments', function (Blueprint $table) {
                foreach ([
                    'service_type',
                    'delivery_priority',
                    'currency',
                    'declared_value',
                    'insurance_amount',
                    'total_weight',
                    'package_length',
                    'package_width',
                    'package_height',
                    'carrier_payload',
                    'last_carrier_response',
                    'current_location',
                    'current_country',
                    'estimated_delivery_at',
                    'signed_receiver',
                    'proof_of_delivery_url',
                    'pickup_scheduled_at',
                    'pickup_confirmation',
                    'pickup_status',
                    'latest_label_path',
                    'latest_label_format',
                    'rate_request_payload',
                    'rate_response_payload',
                ] as $column) {
                    if (Schema::hasColumn('b2b_shipments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('b2b_shipping_quotes')) {
            Schema::table('b2b_shipping_quotes', function (Blueprint $table) {
                foreach ([
                    'service_type',
                    'delivery_priority',
                    'total_weight',
                    'package_length',
                    'package_width',
                    'package_height',
                    'rate_request_payload',
                    'rate_response_payload',
                ] as $column) {
                    if (Schema::hasColumn('b2b_shipping_quotes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('b2b_shipping_providers')) {
            Schema::table('b2b_shipping_providers', function (Blueprint $table) {
                foreach (['username', 'password', 'oauth_token', 'refresh_token', 'environment', 'custom_config'] as $column) {
                    if (Schema::hasColumn('b2b_shipping_providers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
