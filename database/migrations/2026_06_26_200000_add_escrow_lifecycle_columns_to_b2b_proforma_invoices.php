<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_proforma_invoices')) {
            return;
        }

        Schema::table('b2b_proforma_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_status')) {
                $table->string('escrow_status', 40)->default('not_applicable')->after('escrow_fee_amount');
            }
            if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_payment_reference')) {
                $table->string('escrow_payment_reference')->nullable()->after('escrow_status');
            }
            if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_funded_at')) {
                $table->timestamp('escrow_funded_at')->nullable()->after('escrow_payment_reference');
            }
            if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_released_at')) {
                $table->timestamp('escrow_released_at')->nullable()->after('escrow_funded_at');
            }
            if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_disputed_at')) {
                $table->timestamp('escrow_disputed_at')->nullable()->after('escrow_released_at');
            }
            if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_refunded_at')) {
                $table->timestamp('escrow_refunded_at')->nullable()->after('escrow_disputed_at');
            }
            if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_cancelled_at')) {
                $table->timestamp('escrow_cancelled_at')->nullable()->after('escrow_refunded_at');
            }
            if (!Schema::hasColumn('b2b_proforma_invoices', 'supplier_paid_out_at')) {
                $table->timestamp('supplier_paid_out_at')->nullable()->after('escrow_cancelled_at');
            }
            if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_dispute_reason')) {
                $table->text('escrow_dispute_reason')->nullable()->after('supplier_paid_out_at');
            }
            if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_resolution')) {
                $table->string('escrow_resolution', 40)->nullable()->after('escrow_dispute_reason');
            }
            if (!Schema::hasColumn('b2b_proforma_invoices', 'escrow_resolution_notes')) {
                $table->text('escrow_resolution_notes')->nullable()->after('escrow_resolution');
            }
        });

        DB::table('b2b_proforma_invoices')
            ->select(['id', 'status', 'escrow_fee_amount'])
            ->orderBy('id')
            ->chunkById(100, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $escrowStatus = ((float) $invoice->escrow_fee_amount) > 0
                        ? ($invoice->status === 'accepted' ? 'awaiting_payment' : 'pending')
                        : 'not_applicable';

                    DB::table('b2b_proforma_invoices')
                        ->where('id', $invoice->id)
                        ->update(['escrow_status' => $escrowStatus]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('b2b_proforma_invoices')) {
            return;
        }

        Schema::table('b2b_proforma_invoices', function (Blueprint $table) {
            foreach ([
                'escrow_resolution_notes',
                'escrow_resolution',
                'escrow_dispute_reason',
                'supplier_paid_out_at',
                'escrow_cancelled_at',
                'escrow_refunded_at',
                'escrow_disputed_at',
                'escrow_released_at',
                'escrow_funded_at',
                'escrow_payment_reference',
                'escrow_status',
            ] as $column) {
                if (Schema::hasColumn('b2b_proforma_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
