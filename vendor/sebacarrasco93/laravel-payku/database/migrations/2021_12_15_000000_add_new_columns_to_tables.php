<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
                    $table->datetime('deposit_date')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payku_payments');
    }
}
