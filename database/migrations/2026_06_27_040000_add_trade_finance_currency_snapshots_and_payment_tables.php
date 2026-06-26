<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'b2b_rfqs',
            'b2b_quotations',
            'b2b_purchase_orders',
            'b2b_proforma_invoices',
            'b2b_shipments',
            'b2b_freight_quotes',
            'b2b_shipping_quotes',
            'b2b_sample_orders',
        ] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'exchange_rate_snapshot')) {
                    $table->decimal('exchange_rate_snapshot', 20, 8)->default(1)->after('currency');
                }
                if (!Schema::hasColumn($tableName, 'currency_snapshot')) {
                    $table->json('currency_snapshot')->nullable()->after('exchange_rate_snapshot');
                }
            });
        }

        Schema::create('b2b_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_type', 120);
            $table->unsignedBigInteger('reference_id');
            $table->unsignedBigInteger('buyer_company_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_company_id')->nullable()->index();
            $table->string('payment_gateway', 60)->nullable();
            $table->string('payment_method', 60)->nullable();
            $table->string('currency', 20);
            $table->string('settlement_currency', 20)->nullable();
            $table->decimal('exchange_rate_snapshot', 20, 8)->default(1);
            $table->decimal('settlement_exchange_rate_snapshot', 20, 8)->default(1);
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('gateway_reference')->nullable()->index();
            $table->string('reference_number')->nullable();
            $table->string('swift')->nullable();
            $table->string('iban')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status', 40)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('meta')->nullable();
            $table->json('currency_snapshot')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'b2b_payment_transactions_reference_index');
        });

        Schema::create('b2b_escrows', function (Blueprint $table) {
            $table->id();
            $table->string('reference_type', 120);
            $table->unsignedBigInteger('reference_id');
            $table->unsignedBigInteger('buyer_company_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_company_id')->nullable()->index();
            $table->unsignedBigInteger('payment_transaction_id')->nullable()->index();
            $table->string('currency', 20);
            $table->string('settlement_currency', 20)->nullable();
            $table->decimal('exchange_rate_snapshot', 20, 8)->default(1);
            $table->decimal('settlement_exchange_rate_snapshot', 20, 8)->default(1);
            $table->decimal('amount', 20, 2)->default(0);
            $table->decimal('funded_amount', 20, 2)->default(0);
            $table->decimal('released_amount', 20, 2)->default(0);
            $table->decimal('refunded_amount', 20, 2)->default(0);
            $table->string('status', 40)->default('pending');
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->unsignedInteger('last_action_by')->nullable();
            $table->json('currency_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'b2b_escrows_reference_index');
        });

        Schema::create('b2b_escrow_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('escrow_id')->index();
            $table->string('action', 40);
            $table->unsignedInteger('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_escrow_logs');
        Schema::dropIfExists('b2b_escrows');
        Schema::dropIfExists('b2b_payment_transactions');

        foreach ([
            'b2b_sample_orders',
            'b2b_shipping_quotes',
            'b2b_freight_quotes',
            'b2b_shipments',
            'b2b_proforma_invoices',
            'b2b_purchase_orders',
            'b2b_quotations',
            'b2b_rfqs',
        ] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach (['currency_snapshot', 'exchange_rate_snapshot'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
