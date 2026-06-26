<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_freight_forwarders')) {
            Schema::table('b2b_freight_forwarders', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_freight_forwarders', 'logo')) {
                    $table->string('logo')->nullable()->after('driver');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'banner')) {
                    $table->string('banner')->nullable()->after('logo');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'support_email')) {
                    $table->string('support_email')->nullable()->after('contact_email');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'support_phone')) {
                    $table->string('support_phone', 50)->nullable()->after('contact_phone');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'provider_type')) {
                    $table->string('provider_type', 40)->default('ocean_carrier')->after('support_phone');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'supported_countries')) {
                    $table->json('supported_countries')->nullable()->after('supported_services');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'container_types')) {
                    $table->json('container_types')->nullable()->after('supported_countries');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'credentials')) {
                    $table->json('credentials')->nullable()->after('container_types');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'last_api_test_status')) {
                    $table->string('last_api_test_status', 40)->nullable()->after('credentials');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'last_api_test_message')) {
                    $table->text('last_api_test_message')->nullable()->after('last_api_test_status');
                }
                if (!Schema::hasColumn('b2b_freight_forwarders', 'last_api_tested_at')) {
                    $table->timestamp('last_api_tested_at')->nullable()->after('last_api_test_message');
                }
            });
        }

        if (Schema::hasTable('b2b_ports')) {
            Schema::table('b2b_ports', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_ports', 'latitude')) {
                    $table->decimal('latitude', 10, 6)->nullable()->after('port_type');
                }
                if (!Schema::hasColumn('b2b_ports', 'longitude')) {
                    $table->decimal('longitude', 10, 6)->nullable()->after('latitude');
                }
            });
        }

        if (Schema::hasTable('b2b_freight_quotes')) {
            Schema::table('b2b_freight_quotes', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_freight_quotes', 'total_cost_base_currency')) {
                    $table->decimal('total_cost_base_currency', 12, 2)->default(0)->after('total_cost');
                }
                if (!Schema::hasColumn('b2b_freight_quotes', 'landed_cost_total')) {
                    $table->decimal('landed_cost_total', 12, 2)->default(0)->after('total_cost_base_currency');
                }
                if (!Schema::hasColumn('b2b_freight_quotes', 'base_currency')) {
                    $table->string('base_currency', 20)->default('USD')->after('currency');
                }
                if (!Schema::hasColumn('b2b_freight_quotes', 'pricing_rule_id')) {
                    $table->unsignedBigInteger('pricing_rule_id')->nullable()->after('forwarder_id');
                }
                if (!Schema::hasColumn('b2b_freight_quotes', 'hs_code_record_id')) {
                    $table->unsignedBigInteger('hs_code_record_id')->nullable()->after('hs_code');
                }
            });
        }

        if (Schema::hasTable('b2b_container_shipments')) {
            Schema::table('b2b_container_shipments', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_container_shipments', 'current_location')) {
                    $table->string('current_location', 255)->nullable()->after('status');
                }
                if (!Schema::hasColumn('b2b_container_shipments', 'current_location_port_id')) {
                    $table->unsignedBigInteger('current_location_port_id')->nullable()->after('current_location');
                }
                if (!Schema::hasColumn('b2b_container_shipments', 'total_freight_cost')) {
                    $table->decimal('total_freight_cost', 12, 2)->default(0)->after('tracking_reference');
                }
                if (!Schema::hasColumn('b2b_container_shipments', 'landed_cost_total')) {
                    $table->decimal('landed_cost_total', 12, 2)->default(0)->after('total_freight_cost');
                }
            });
        }

        if (Schema::hasTable('b2b_customs_documents')) {
            Schema::table('b2b_customs_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_customs_documents', 'status')) {
                    $table->string('status', 40)->default('active')->after('title');
                }
                if (!Schema::hasColumn('b2b_customs_documents', 'revision_number')) {
                    $table->unsignedInteger('revision_number')->default(1)->after('status');
                }
                if (!Schema::hasColumn('b2b_customs_documents', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('expires_at');
                }
                if (!Schema::hasColumn('b2b_customs_documents', 'verified_by')) {
                    $table->unsignedInteger('verified_by')->nullable()->after('verified_at');
                }
            });
        }

        if (!Schema::hasTable('b2b_freight_pricing_rules')) {
            Schema::create('b2b_freight_pricing_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('forwarder_id')->nullable()->constrained('b2b_freight_forwarders')->nullOnDelete();
                $table->string('freight_mode', 30)->nullable();
                $table->string('service_type', 40)->nullable();
                $table->string('origin_country', 100)->nullable();
                $table->string('destination_country', 100)->nullable();
                $table->string('container_type', 40)->nullable();
                $table->string('incoterm', 10)->nullable();
                $table->decimal('min_weight', 12, 3)->nullable();
                $table->decimal('max_weight', 12, 3)->nullable();
                $table->decimal('min_volume', 12, 3)->nullable();
                $table->decimal('max_volume', 12, 3)->nullable();
                $table->decimal('base_price', 12, 2)->default(0);
                $table->decimal('price_per_kg', 12, 4)->default(0);
                $table->decimal('price_per_cbm', 12, 4)->default(0);
                $table->decimal('fuel_surcharge_percent', 8, 3)->default(0);
                $table->decimal('platform_fee_percent', 8, 3)->default(0);
                $table->decimal('platform_fee_fixed', 12, 2)->default(0);
                $table->string('currency', 20)->default('USD');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('b2b_hs_codes')) {
            Schema::create('b2b_hs_codes', function (Blueprint $table) {
                $table->id();
                $table->string('hs_code', 60)->index();
                $table->string('description')->nullable();
                $table->string('country', 100)->nullable();
                $table->decimal('duty_percent', 8, 3)->default(0);
                $table->decimal('vat_gst_percent', 8, 3)->default(0);
                $table->text('restrictions')->nullable();
                $table->boolean('is_dangerous_goods')->default(false);
                $table->json('required_documents')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('b2b_freight_quote_costs')) {
            Schema::create('b2b_freight_quote_costs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('freight_quote_id')->constrained('b2b_freight_quotes')->cascadeOnDelete();
                $table->string('cost_type', 80);
                $table->string('description')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 20)->default('USD');
                $table->decimal('exchange_rate_snapshot', 12, 6)->default(1);
                $table->string('payer', 20)->default('buyer');
                $table->boolean('is_billable')->default(true);
                $table->boolean('is_optional')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_freight_quote_costs');
        Schema::dropIfExists('b2b_hs_codes');
        Schema::dropIfExists('b2b_freight_pricing_rules');
    }
};
