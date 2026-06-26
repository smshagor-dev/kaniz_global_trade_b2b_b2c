<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendTable('b2b_freight_forwarders');
        $this->extendTable('b2b_shipping_providers');
    }

    public function down(): void
    {
    }

    protected function extendTable(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'integration_events')) {
                $table->json('integration_events')->nullable()->after('webhook_secret');
            }
            if (!Schema::hasColumn($tableName, 'last_api_status')) {
                $table->string('last_api_status', 40)->nullable()->after('integration_events');
            }
            if (!Schema::hasColumn($tableName, 'last_api_http_status')) {
                $table->unsignedSmallInteger('last_api_http_status')->nullable()->after('last_api_status');
            }
            if (!Schema::hasColumn($tableName, 'last_api_response_time_ms')) {
                $table->unsignedInteger('last_api_response_time_ms')->nullable()->after('last_api_http_status');
            }
            if (!Schema::hasColumn($tableName, 'last_api_called_at')) {
                $table->timestamp('last_api_called_at')->nullable()->after('last_api_response_time_ms');
            }
            if (!Schema::hasColumn($tableName, 'last_api_success_at')) {
                $table->timestamp('last_api_success_at')->nullable()->after('last_api_called_at');
            }
            if (!Schema::hasColumn($tableName, 'last_api_failure_at')) {
                $table->timestamp('last_api_failure_at')->nullable()->after('last_api_success_at');
            }
            if (!Schema::hasColumn($tableName, 'last_webhook_received_at')) {
                $table->timestamp('last_webhook_received_at')->nullable()->after('last_api_failure_at');
            }
            if (!Schema::hasColumn($tableName, 'webhook_verified_at')) {
                $table->timestamp('webhook_verified_at')->nullable()->after('last_webhook_received_at');
            }
            if (!Schema::hasColumn($tableName, 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable()->after('webhook_verified_at');
            }
            if (!Schema::hasColumn($tableName, 'successful_requests')) {
                $table->unsignedInteger('successful_requests')->default(0)->after('last_sync_at');
            }
            if (!Schema::hasColumn($tableName, 'failed_requests')) {
                $table->unsignedInteger('failed_requests')->default(0)->after('successful_requests');
            }
            if (!Schema::hasColumn($tableName, 'average_response_time_ms')) {
                $table->unsignedInteger('average_response_time_ms')->default(0)->after('failed_requests');
            }
        });
    }
};
