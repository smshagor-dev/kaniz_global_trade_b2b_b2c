<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_price_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('country')->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('supplier_cost', 16, 4)->default(0);
            $table->decimal('shipping_cost', 16, 4)->default(0);
            $table->decimal('customs_cost', 16, 4)->default(0);
            $table->decimal('tax_cost', 16, 4)->default(0);
            $table->decimal('vat_cost', 16, 4)->default(0);
            $table->decimal('platform_fee', 16, 4)->default(0);
            $table->decimal('selling_price', 16, 4)->default(0);
            $table->decimal('minimum_profitable_price', 16, 4)->default(0);
            $table->decimal('wholesale_price', 16, 4)->default(0);
            $table->decimal('distributor_price', 16, 4)->default(0);
            $table->decimal('export_price', 16, 4)->default(0);
            $table->decimal('profit_margin', 8, 4)->default(0);
            $table->string('source', 30)->default('deterministic');
            $table->text('explanation')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_supplier_risk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('supplier_company_id')->index();
            $table->unsignedBigInteger('subject_user_id')->nullable()->index();
            $table->unsignedInteger('risk_score')->default(0);
            $table->string('risk_level', 30)->default('low');
            $table->text('explanation')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_buyer_risk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('buyer_company_id')->index();
            $table->unsignedBigInteger('subject_user_id')->nullable()->index();
            $table->unsignedInteger('trust_score')->default(0);
            $table->string('risk_level', 30)->default('low');
            $table->text('explanation')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_trade_opportunities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('opportunity_type', 50);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('market_country')->nullable();
            $table->unsignedInteger('opportunity_score')->default(0);
            $table->decimal('estimated_revenue_increase', 16, 4)->default(0);
            $table->decimal('estimated_savings', 16, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('ai_dashboard_insights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->date('insight_date')->index();
            $table->string('scope', 30)->default('company');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('insights')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_freight_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('freight_quote_id')->nullable()->index();
            $table->unsignedBigInteger('shipment_id')->nullable()->index();
            $table->unsignedBigInteger('forwarder_id')->nullable()->index();
            $table->string('recommended_mode', 30)->nullable();
            $table->string('recommended_strategy', 30)->nullable();
            $table->string('recommended_forwarder_name')->nullable();
            $table->unsignedInteger('estimated_delivery_days')->nullable();
            $table->unsignedInteger('estimated_customs_delay_days')->nullable();
            $table->decimal('estimated_shipping_cost', 16, 4)->default(0);
            $table->decimal('cost_saving_estimate', 16, 4)->default(0);
            $table->decimal('carbon_estimate', 16, 4)->default(0);
            $table->unsignedInteger('risk_score')->default(0);
            $table->text('explanation')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_currency_analysis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->string('currency_code', 10);
            $table->string('base_currency_code', 10)->nullable();
            $table->decimal('amount', 16, 4)->default(0);
            $table->unsignedInteger('volatility_score')->default(0);
            $table->decimal('fx_exposure', 16, 4)->default(0);
            $table->string('recommended_invoice_currency', 10)->nullable();
            $table->decimal('profit_impact', 16, 4)->default(0);
            $table->text('hedging_suggestion')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_trade_finance_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->string('recommended_term', 50);
            $table->unsignedInteger('risk_score')->default(0);
            $table->text('explanation')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_notification_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->string('event_type', 50);
            $table->string('audience_type', 50);
            $table->unsignedBigInteger('audience_id')->nullable()->index();
            $table->string('severity', 20)->default('info');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_notification_events');
        Schema::dropIfExists('ai_trade_finance_recommendations');
        Schema::dropIfExists('ai_currency_analysis');
        Schema::dropIfExists('ai_freight_recommendations');
        Schema::dropIfExists('ai_dashboard_insights');
        Schema::dropIfExists('ai_trade_opportunities');
        Schema::dropIfExists('ai_buyer_risk');
        Schema::dropIfExists('ai_supplier_risk');
        Schema::dropIfExists('ai_price_recommendations');
    }
};
