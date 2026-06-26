<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_payment_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
            $table->unsignedBigInteger('proforma_invoice_id')->nullable()->index();
            $table->unsignedBigInteger('buyer_company_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_company_id')->nullable()->index();
            $table->string('title');
            $table->string('trigger_event', 40)->default('custom');
            $table->unsignedInteger('sort_order')->default(1);
            $table->decimal('percentage', 8, 2)->default(0);
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('currency', 20);
            $table->decimal('exchange_rate_snapshot', 20, 8)->default(1);
            $table->json('currency_snapshot')->nullable();
            $table->string('status', 40)->default('pending');
            $table->timestamp('scheduled_release_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedBigInteger('payment_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('escrow_id')->nullable()->index();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_letter_of_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
            $table->unsignedBigInteger('proforma_invoice_id')->nullable()->index();
            $table->unsignedBigInteger('buyer_company_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_company_id')->nullable()->index();
            $table->string('lc_number')->unique();
            $table->string('issuing_bank');
            $table->string('advising_bank')->nullable();
            $table->date('expiry_date');
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('currency', 20);
            $table->json('required_documents')->nullable();
            $table->string('status', 40)->default('requested');
            $table->text('review_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_company_id')->index();
            $table->unsignedBigInteger('payment_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('escrow_id')->nullable()->index();
            $table->string('settlement_method', 40);
            $table->string('currency', 20);
            $table->decimal('amount', 20, 2)->default(0);
            $table->decimal('fees', 20, 2)->default(0);
            $table->decimal('net_amount', 20, 2)->default(0);
            $table->string('reference')->nullable();
            $table->json('destination_details')->nullable();
            $table->string('status', 40)->default('pending_approval');
            $table->unsignedInteger('requested_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_settlement_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('settlement_id')->index();
            $table->string('action', 40);
            $table->unsignedInteger('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_finance_disputes', function (Blueprint $table) {
            $table->id();
            $table->string('reference_type', 120);
            $table->unsignedBigInteger('reference_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
            $table->unsignedBigInteger('proforma_invoice_id')->nullable()->index();
            $table->unsignedBigInteger('buyer_company_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_company_id')->nullable()->index();
            $table->unsignedInteger('created_by')->nullable();
            $table->string('category', 40);
            $table->string('title');
            $table->text('description');
            $table->json('evidence')->nullable();
            $table->string('status', 40)->default('open');
            $table->boolean('escrow_hold')->default(true);
            $table->string('resolution', 40)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_finance_dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dispute_id')->index();
            $table->unsignedInteger('sender_user_id')->nullable();
            $table->unsignedBigInteger('sender_company_id')->nullable()->index();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_finance_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('reference_type', 120);
            $table->unsignedBigInteger('reference_id');
            $table->unsignedBigInteger('payment_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('escrow_id')->nullable()->index();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('currency', 20);
            $table->string('refund_type', 40)->default('manual');
            $table->string('status', 40)->default('pending_approval');
            $table->text('reason');
            $table->unsignedInteger('requested_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_finance_refunds');
        Schema::dropIfExists('b2b_finance_dispute_messages');
        Schema::dropIfExists('b2b_finance_disputes');
        Schema::dropIfExists('b2b_settlement_logs');
        Schema::dropIfExists('b2b_settlements');
        Schema::dropIfExists('b2b_letter_of_credits');
        Schema::dropIfExists('b2b_payment_milestones');
    }
};
