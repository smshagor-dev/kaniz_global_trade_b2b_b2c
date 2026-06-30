<?php

namespace App\Services;

use App\Models\B2BCompany;
use App\Models\B2BCompanyCertification;
use App\Models\B2BContainerShipment;
use App\Models\B2BEscrow;
use App\Models\B2BFinanceDispute;
use App\Models\B2BFinanceRefund;
use App\Models\B2BFreightForwarder;
use App\Models\B2BFreightQuote;
use App\Models\B2BFreightQuoteCost;
use App\Models\B2BInsuranceClaim;
use App\Models\B2BInsurancePayment;
use App\Models\B2BInsurancePolicy;
use App\Models\B2BPaymentMilestone;
use App\Models\B2BProformaInvoice;
use App\Models\B2BPurchaseOrder;
use App\Models\B2BQuotation;
use App\Models\B2BRfq;
use App\Models\B2BSampleOrder;
use App\Models\B2BSettlement;
use App\Models\B2BShipment;
use App\Models\B2BShippingProvider;
use App\Models\B2BShippingQuote;
use App\Models\Product;

class B2BDashboardService
{
    public function __construct(
        protected B2BCompanyService $b2bCompanyService,
        protected B2BPackageService $b2bPackageService,
        protected B2BProductPromotionService $productPromotionService,
        protected B2BPremiumVerificationService $premiumVerificationService,
        protected B2BSampleProcessingFeeService $sampleProcessingFeeService,
        protected B2BInspectionServiceChargeService $inspectionServiceChargeService,
        protected B2BTradeDocumentFeeService $tradeDocumentFeeService,
        protected B2BLogisticsChargeService $logisticsChargeService,
        protected B2BOrderPlatformFeeService $orderPlatformFeeService,
        protected B2BEscrowFeeService $escrowFeeService
    )
    {
    }

    public function sellerStats(int $userId, ?B2BCompany $company = null): array
    {
        $company ??= $this->b2bCompanyService->getCompanyByUser($userId);
        $companyId = $company?->id;

        return [
            'active_company_name' => $company?->company_name,
            'pending_rfqs' => B2BRfq::whereIn('status', ['open', 'quoted'])->count(),
            'quoted_rfqs' => $companyId ? B2BQuotation::where('supplier_company_id', $companyId)->count() : 0,
            'accepted_quotes' => $companyId ? B2BQuotation::where('supplier_company_id', $companyId)->where('status', 'accepted')->count() : 0,
            'purchase_orders' => $companyId ? B2BPurchaseOrder::where('supplier_company_id', $companyId)->count() : 0,
            'pending_purchase_orders' => $companyId ? B2BPurchaseOrder::where('supplier_company_id', $companyId)->where('status', 'sent')->count() : 0,
            'invoices' => $companyId ? B2BProformaInvoice::where('supplier_company_id', $companyId)->count() : 0,
            'revenue' => $companyId ? B2BProformaInvoice::where('supplier_company_id', $companyId)->where('status', 'accepted')->sum('grand_total') : 0,
            'top_products' => B2BQuotation::with('product')
                ->when($companyId, fn ($query) => $query->where('supplier_company_id', $companyId), fn ($query) => $query->whereRaw('1=0'))
                ->selectRaw('product_id, COUNT(*) as quote_count')
                ->groupBy('product_id')
                ->orderByDesc('quote_count')
                ->limit(5)
                ->get(),
            'unread_negotiations' => $companyId
                ? \App\Models\B2BNegotiationMessage::whereHas('negotiation', fn ($query) => $query->where('supplier_company_id', $companyId))
                    ->whereNull('supplier_read_at')
                    ->where('sender_company_id', '!=', $companyId)
                    ->count()
                : 0,
            'profile_completeness' => $company ? $this->calculateSupplierProfileCompleteness($company) : 0,
            'public_profile_enabled' => (bool) ($company?->public_profile_enabled),
            'pending_certifications' => $companyId ? B2BCompanyCertification::where('b2b_company_id', $companyId)->where('verification_status', 'pending')->count() : 0,
            'approved_certifications' => $companyId ? B2BCompanyCertification::where('b2b_company_id', $companyId)->where('verification_status', 'approved')->count() : 0,
            'todays_shipments' => $companyId ? B2BShipment::where('supplier_company_id', $companyId)->whereDate('created_at', today())->count() : 0,
            'pending_shipments' => $companyId ? B2BShipment::where('supplier_company_id', $companyId)->where('status', 'preparing')->count() : 0,
            'awaiting_pickup_shipments' => $companyId ? B2BShipment::where('supplier_company_id', $companyId)->whereIn('status', ['label_created', 'pickup_scheduled'])->count() : 0,
            'active_shipments' => $companyId ? B2BShipment::where('supplier_company_id', $companyId)->whereIn('status', ['picked_up', 'export_customs', 'in_transit', 'import_customs', 'out_for_delivery'])->count() : 0,
            'completed_shipments' => $companyId ? B2BShipment::where('supplier_company_id', $companyId)->where('status', 'delivered')->count() : 0,
            'exception_shipments' => $companyId ? B2BShipment::where('supplier_company_id', $companyId)->whereIn('status', ['exception', 'returned', 'customs_hold'])->count() : 0,
            'delayed_shipments' => $companyId ? B2BShipment::where('supplier_company_id', $companyId)->where('status', 'delayed')->count() : 0,
            'freight_quotes' => $companyId ? B2BFreightQuote::where('supplier_company_id', $companyId)->count() : 0,
            'awaiting_freight_pickup' => $companyId ? B2BContainerShipment::whereHas('freightQuote', fn ($query) => $query->where('supplier_company_id', $companyId))->where('status', 'gate_in')->count() : 0,
            'active_containers' => $companyId ? B2BContainerShipment::whereHas('freightQuote', fn ($query) => $query->where('supplier_company_id', $companyId))->whereNotIn('status', ['delivered', 'cancelled'])->count() : 0,
            'delivered_containers' => $companyId ? B2BContainerShipment::whereHas('freightQuote', fn ($query) => $query->where('supplier_company_id', $companyId))->where('status', 'delivered')->count() : 0,
            'freight_exceptions' => $companyId ? B2BContainerShipment::whereHas('freightQuote', fn ($query) => $query->where('supplier_company_id', $companyId))->whereIn('status', ['customs_hold', 'delayed', 'exception'])->count() : 0,
            'sample_orders' => $companyId ? B2BSampleOrder::where('supplier_company_id', $companyId)->count() : 0,
            'finance_pending_settlements' => $companyId ? B2BSettlement::where('supplier_company_id', $companyId)->whereIn('status', ['pending_approval', 'approved'])->count() : 0,
            'finance_completed_settlements' => $companyId ? B2BSettlement::where('supplier_company_id', $companyId)->where('status', 'completed')->count() : 0,
            'finance_gross_revenue' => $companyId ? B2BProformaInvoice::where('supplier_company_id', $companyId)->sum('grand_total') : 0,
            'finance_platform_fees' => $companyId ? B2BProformaInvoice::where('supplier_company_id', $companyId)->sum('platform_fee_amount') : 0,
            'finance_net_earnings' => $companyId ? B2BProformaInvoice::where('supplier_company_id', $companyId)->sum('supplier_payout_amount') : 0,
            'finance_released_earnings' => $companyId ? B2BProformaInvoice::where('supplier_company_id', $companyId)->whereNotNull('supplier_paid_out_at')->sum('supplier_payout_amount') : 0,
            'finance_available_payout' => $companyId ? B2BEscrow::where('supplier_company_id', $companyId)->where('status', 'released')->whereDoesntHave('settlements')->sum('released_amount') : 0,
            'finance_requested_payout_amount' => $companyId ? B2BSettlement::where('supplier_company_id', $companyId)->whereIn('status', ['pending_approval', 'approved'])->sum('net_amount') : 0,
            'finance_completed_payout_amount' => $companyId ? B2BSettlement::where('supplier_company_id', $companyId)->where('status', 'completed')->sum('net_amount') : 0,
            'finance_total_payout_fees' => $companyId ? B2BSettlement::where('supplier_company_id', $companyId)->sum('fees') : 0,
            'finance_open_disputes' => $companyId ? B2BFinanceDispute::where('supplier_company_id', $companyId)->where('status', 'open')->count() : 0,
            'finance_milestones_due' => $companyId ? B2BPaymentMilestone::where('supplier_company_id', $companyId)->whereIn('status', ['pending', 'funded'])->count() : 0,
            'insurance_policies' => $companyId ? B2BInsurancePolicy::where('supplier_company_id', $companyId)->count() : 0,
            'insurance_claims' => $companyId ? B2BInsuranceClaim::where('supplier_company_id', $companyId)->count() : 0,
            'insurance_open_claims' => $companyId ? B2BInsuranceClaim::where('supplier_company_id', $companyId)->whereIn('status', ['submitted', 'review', 'investigation', 'appealed'])->count() : 0,
            'profile_views' => 0,
        ];
    }

    public function buyerStats(int $userId, ?B2BCompany $company = null): array
    {
        $company ??= $this->b2bCompanyService->getCompanyByUser($userId);
        $companyId = $company?->id;

        return [
            'requested_rfqs' => $companyId ? B2BRfq::where('b2b_company_id', $companyId)->count() : 0,
            'received_quotes' => $companyId ? B2BQuotation::whereHas('rfq', fn ($query) => $query->where('b2b_company_id', $companyId))->count() : 0,
            'accepted_quotes' => $companyId ? B2BQuotation::whereHas('rfq', fn ($query) => $query->where('b2b_company_id', $companyId))->where('status', 'accepted')->count() : 0,
            'purchase_orders' => $companyId ? B2BPurchaseOrder::where('buyer_company_id', $companyId)->count() : 0,
            'invoices' => $companyId ? B2BProformaInvoice::where('buyer_company_id', $companyId)->count() : 0,
            'favorite_suppliers' => $companyId ? B2BQuotation::whereHas('rfq', fn ($query) => $query->where('b2b_company_id', $companyId))
                ->where('status', 'accepted')
                ->distinct('supplier_company_id')
                ->count('supplier_company_id') : 0,
            'unread_negotiations' => $companyId
                ? \App\Models\B2BNegotiationMessage::whereHas('negotiation', fn ($query) => $query->where('buyer_company_id', $companyId))
                    ->whereNull('buyer_read_at')
                    ->where('sender_company_id', '!=', $companyId)
                    ->count()
                : 0,
            'current_shipments' => $companyId ? B2BShipment::where('buyer_company_id', $companyId)->whereNotIn('status', ['delivered', 'cancelled', 'returned'])->count() : 0,
            'expected_deliveries' => $companyId ? B2BShipment::where('buyer_company_id', $companyId)->whereNotNull('estimated_delivery_at')->count() : 0,
            'pending_shipments' => $companyId ? B2BShipment::where('buyer_company_id', $companyId)->where('status', 'preparing')->count() : 0,
            'active_shipments' => $companyId ? B2BShipment::where('buyer_company_id', $companyId)->whereIn('status', ['picked_up', 'export_customs', 'in_transit', 'import_customs', 'out_for_delivery'])->count() : 0,
            'completed_shipments' => $companyId ? B2BShipment::where('buyer_company_id', $companyId)->where('status', 'delivered')->count() : 0,
            'delayed_shipments' => $companyId ? B2BShipment::where('buyer_company_id', $companyId)->where('status', 'delayed')->count() : 0,
            'freight_quotes' => $companyId ? B2BFreightQuote::where('buyer_company_id', $companyId)->count() : 0,
            'current_containers' => $companyId ? B2BContainerShipment::whereHas('freightQuote', fn ($query) => $query->where('buyer_company_id', $companyId))->whereNotIn('status', ['delivered', 'cancelled'])->count() : 0,
            'expected_container_deliveries' => $companyId ? B2BContainerShipment::whereHas('freightQuote', fn ($query) => $query->where('buyer_company_id', $companyId))->whereNotNull('eta')->count() : 0,
            'sample_orders' => $companyId ? B2BSampleOrder::where('buyer_company_id', $companyId)->count() : 0,
            'finance_outstanding' => $companyId ? B2BProformaInvoice::where('buyer_company_id', $companyId)->whereIn('status', ['sent', 'accepted'])->sum('buyer_payable_total') : 0,
            'finance_open_disputes' => $companyId ? B2BFinanceDispute::where('buyer_company_id', $companyId)->where('status', 'open')->count() : 0,
            'finance_milestones_due' => $companyId ? B2BPaymentMilestone::where('buyer_company_id', $companyId)->whereIn('status', ['pending', 'funded'])->count() : 0,
            'finance_refunds' => $companyId ? B2BFinanceRefund::where('reference_type', B2BProformaInvoice::class)
                ->whereIn('reference_id', B2BProformaInvoice::where('buyer_company_id', $companyId)->pluck('id'))
                ->count() : 0,
            'insurance_policies' => $companyId ? B2BInsurancePolicy::where('buyer_company_id', $companyId)->count() : 0,
            'insurance_claims' => $companyId ? B2BInsuranceClaim::where('buyer_company_id', $companyId)->count() : 0,
            'insurance_open_claims' => $companyId ? B2BInsuranceClaim::where('buyer_company_id', $companyId)->whereIn('status', ['submitted', 'review', 'investigation', 'appealed'])->count() : 0,
        ];
    }

    public function adminStats(): array
    {
        $totalCompanies = B2BCompany::count();
        $verifiedCompanies = B2BCompany::where('verification_status', 'approved')->count();
        $approvedSuppliers = B2BCompany::approvedSupplierSide()->count();
        $publicSuppliers = B2BCompany::publicSuppliers()->count();
        $b2bProducts = Product::where('wholesale_product', 1)
            ->where('approved', 1)
            ->where('published', 1)
            ->count();

        $featuredRevenueProjection = $this->b2bPackageService->featuredSupplierRevenueProjection($publicSuppliers);
        $sponsoredProductProjection = $this->productPromotionService->revenueProjection($b2bProducts);
        $premiumVerificationProjection = $this->premiumVerificationService->revenueProjection($verifiedCompanies);
        $sampleProcessingProjection = $this->sampleProcessingFeeService->revenueProjection(B2BSampleOrder::count());
        $inspectionProjection = $this->inspectionServiceChargeService->revenueProjection(
            B2BFreightQuoteCost::where('cost_type', B2BInspectionServiceChargeService::BASE_COST_TYPE)->count()
        );
        $tradeDocumentProjection = $this->tradeDocumentFeeService->revenueProjection(
            $this->tradeDocumentFeeService->chargeableDocumentsCount()
        );

        return [
            'total_companies' => $totalCompanies,
            'total_buyers' => B2BCompany::whereIn('company_type', B2BCompany::BUYER_TYPES)->count(),
            'total_suppliers' => B2BCompany::supplierSide()->count(),
            'b2b_products' => $b2bProducts,
            'pending_companies' => B2BCompany::where('verification_status', 'pending')->count(),
            'rfqs' => B2BRfq::count(),
            'open_rfqs' => B2BRfq::where('status', 'open')->count(),
            'quoted_rfqs' => B2BRfq::where('status', 'quoted')->count(),
            'quotes' => B2BQuotation::count(),
            'purchase_orders' => B2BPurchaseOrder::count(),
            'invoices' => B2BProformaInvoice::count(),
            'verified_companies' => $verifiedCompanies,
            'premium_verified_companies' => B2BCompany::where('premium_verified', true)->count(),
            'approved_suppliers' => $approvedSuppliers,
            'public_suppliers' => $publicSuppliers,
            'pending_certifications' => B2BCompanyCertification::where('verification_status', 'pending')->count(),
            'featured_suppliers' => B2BCompany::approvedSupplierSide()->where('featured_supplier', true)->count(),
            'active_featured_homepage_suppliers' => B2BCompany::homepageFeaturedSuppliers()->count(),
            'featured_supplier_monthly_revenue' => $this->b2bPackageService->featuredSupplierMonthlyRevenue(),
            'featured_supplier_plan_name' => $featuredRevenueProjection['plan_name'],
            'featured_supplier_monthly_price' => $featuredRevenueProjection['monthly_price'],
            'featured_supplier_projection_count' => $featuredRevenueProjection['company_count'],
            'featured_supplier_projection_monthly_revenue' => $featuredRevenueProjection['projected_monthly_revenue'],
            'active_sponsored_products' => $this->productPromotionService->activeSponsoredProductsCount(),
            'sponsored_product_monthly_revenue' => $this->productPromotionService->sponsoredProductMonthlyRevenue(),
            'sponsored_product_plan_name' => $sponsoredProductProjection['plan_name'],
            'sponsored_product_monthly_unit_price' => $sponsoredProductProjection['monthly_unit_price'],
            'sponsored_product_projection_count' => $sponsoredProductProjection['product_count'],
            'sponsored_product_projection_monthly_revenue' => $sponsoredProductProjection['projected_monthly_revenue'],
            'premium_verification_total_revenue' => B2BCompany::where('premium_verified', true)
                ->with('premiumVerificationPackage')
                ->get()
                ->sum(fn (B2BCompany $company) => (float) ($company->premiumVerificationPackage?->amount ?? 0)),
            'premium_verification_plan_name' => $premiumVerificationProjection['plan_name'],
            'premium_verification_price' => $premiumVerificationProjection['price'],
            'premium_verification_projection_count' => $premiumVerificationProjection['company_count'],
            'premium_verification_projection_revenue' => $premiumVerificationProjection['projected_revenue'],
            'sample_processing_fee_revenue' => $this->sampleProcessingFeeService->platformRevenue(),
            'sample_processing_fee_type' => $sampleProcessingProjection['type'],
            'sample_processing_fee_percent' => $sampleProcessingProjection['percent'],
            'sample_processing_fee_fixed' => $sampleProcessingProjection['fee'],
            'sample_processing_unit_fee' => $sampleProcessingProjection['unit_fee'],
            'sample_processing_average_subtotal' => $sampleProcessingProjection['average_subtotal'],
            'sample_processing_projection_count' => $sampleProcessingProjection['sample_count'],
            'sample_processing_projection_revenue' => $sampleProcessingProjection['projected_revenue'],
            'inspection_service_charge_revenue' => $this->inspectionServiceChargeService->platformRevenue(),
            'inspection_service_charge_type' => $inspectionProjection['type'],
            'inspection_service_charge_percent' => $inspectionProjection['percent'],
            'inspection_service_charge_fixed' => $inspectionProjection['fixed'],
            'inspection_service_charge_unit' => $inspectionProjection['unit_charge'],
            'inspection_average_fee' => $inspectionProjection['average_inspection_fee'],
            'inspection_projection_count' => $inspectionProjection['inspection_count'],
            'inspection_projection_revenue' => $inspectionProjection['projected_revenue'],
            'trade_document_fee_revenue' => $this->tradeDocumentFeeService->platformRevenue(),
            'trade_document_fee_type' => $tradeDocumentProjection['type'],
            'trade_document_fee_percent' => $tradeDocumentProjection['percent'],
            'trade_document_fee_fixed' => $tradeDocumentProjection['fee'],
            'trade_document_unit_fee' => $tradeDocumentProjection['unit_fee'],
            'trade_documents' => $this->tradeDocumentFeeService->totalDocumentsCount(),
            'chargeable_trade_documents' => $tradeDocumentProjection['document_count'],
            'trade_document_projection_count' => $tradeDocumentProjection['document_count'],
            'trade_document_projection_revenue' => $tradeDocumentProjection['projected_revenue'],
            'todays_shipments' => B2BShipment::whereDate('created_at', today())->count(),
            'pending_shipments' => B2BShipment::where('status', 'preparing')->count(),
            'awaiting_pickup_shipments' => B2BShipment::whereIn('status', ['label_created', 'pickup_scheduled'])->count(),
            'active_shipments' => B2BShipment::whereIn('status', ['picked_up', 'export_customs', 'in_transit', 'import_customs', 'out_for_delivery'])->count(),
            'completed_shipments' => B2BShipment::where('status', 'delivered')->count(),
            'exception_shipments' => B2BShipment::whereIn('status', ['exception', 'returned', 'customs_hold'])->count(),
            'delayed_shipments' => B2BShipment::where('status', 'delayed')->count(),
            'freight_quotes' => B2BFreightQuote::count(),
            'active_containers' => B2BContainerShipment::whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'delivered_containers' => B2BContainerShipment::where('status', 'delivered')->count(),
            'container_exceptions' => B2BContainerShipment::whereIn('status', ['customs_hold', 'delayed', 'exception'])->count(),
            'sample_orders' => B2BSampleOrder::count(),
            'countries' => B2BCompany::whereNotNull('country')->distinct('country')->count('country'),
            'trade_volume' => B2BProformaInvoice::sum('grand_total'),
            'finance_open_disputes' => B2BFinanceDispute::where('status', 'open')->count(),
            'finance_pending_settlements' => B2BSettlement::whereIn('status', ['pending_approval', 'approved'])->count(),
            'finance_completed_settlements' => B2BSettlement::where('status', 'completed')->count(),
            'finance_pending_refunds' => B2BFinanceRefund::whereIn('status', ['pending_approval', 'approved'])->count(),
            'finance_milestones' => B2BPaymentMilestone::count(),
            'insurance_providers' => B2BInsurancePolicy::query()->distinct('provider_id')->count('provider_id'),
            'insurance_policies' => B2BInsurancePolicy::count(),
            'insurance_claims' => B2BInsuranceClaim::count(),
            'insurance_open_claims' => B2BInsuranceClaim::whereIn('status', ['submitted', 'review', 'investigation', 'appealed'])->count(),
            'insurance_premium_revenue' => B2BInsurancePayment::where('payment_type', 'premium')->where('status', 'paid')->sum('amount'),
            'insurance_claim_settlements' => B2BInsurancePayment::where('payment_type', 'claim_settlement')->where('status', 'paid')->sum('amount'),
            'order_platform_profit' => $this->orderPlatformFeeService->platformRevenue(),
            'escrow_platform_profit' => $this->escrowFeeService->platformRevenue(),
            'sample_processing_platform_profit' => $this->sampleProcessingFeeService->platformRevenue(),
            'inspection_platform_profit' => $this->inspectionServiceChargeService->platformRevenue(),
            'trade_document_platform_profit' => $this->tradeDocumentFeeService->platformRevenue(),
            'shipping_platform_profit' => $this->logisticsChargeService->shippingPlatformRevenue(),
            'freight_platform_profit' => $this->logisticsChargeService->freightPlatformRevenue(),
            'platform_profit' => $this->orderPlatformFeeService->platformRevenue()
                + $this->escrowFeeService->platformRevenue()
                + $this->sampleProcessingFeeService->platformRevenue()
                + $this->inspectionServiceChargeService->platformRevenue()
                + $this->tradeDocumentFeeService->platformRevenue()
                + $this->logisticsChargeService->shippingPlatformRevenue()
                + $this->logisticsChargeService->freightPlatformRevenue(),
            'carrier_performance' => B2BShippingProvider::query()
                ->withCount(['shipments as delivered_shipments_count' => fn ($query) => $query->where('status', 'delivered')])
                ->withCount(['shipments as delayed_shipments_count' => fn ($query) => $query->where('status', 'delayed')])
                ->withCount(['shipments as api_errors_count' => fn ($query) => $query->whereNotNull('sync_error')])
                ->get()
                ->map(fn ($provider) => [
                    'provider' => $provider->name,
                    'delivered' => $provider->delivered_shipments_count,
                    'delayed_percent' => ($provider->shipments()->count() > 0)
                        ? round(($provider->delayed_shipments_count / $provider->shipments()->count()) * 100, 2)
                        : 0,
                    'api_errors' => $provider->api_errors_count,
                ])
                ->values(),
            'freight_forwarder_performance' => B2BFreightForwarder::query()
                ->withCount(['containerShipments as delivered_containers_count' => fn ($query) => $query->where('status', 'delivered')])
                ->withCount(['containerShipments as delayed_containers_count' => fn ($query) => $query->where('status', 'delayed')])
                ->withCount(['containerShipments as api_errors_count' => fn ($query) => $query->whereNotNull('sync_error')])
                ->get()
                ->map(fn ($forwarder) => [
                    'forwarder' => $forwarder->name,
                    'delivered' => $forwarder->delivered_containers_count,
                    'delayed_percent' => ($forwarder->containerShipments()->count() > 0)
                        ? round(($forwarder->delayed_containers_count / $forwarder->containerShipments()->count()) * 100, 2)
                        : 0,
                    'api_errors' => $forwarder->api_errors_count,
                ])
                ->values(),
        ];
    }

    protected function calculateSupplierProfileCompleteness(B2BCompany $company): int
    {
        $fields = [
            $company->logo,
            $company->description,
            $company->business_scope,
            $company->production_capacity,
            $company->factory_location,
            $company->quality_control,
            $company->lead_time_summary,
            $company->response_rate,
            $company->response_time_hours,
            $company->year_established,
        ];

        $completed = collect($fields)->filter(fn ($value) => !is_null($value) && $value !== '')->count();
        $completed += $company->categories()->count() > 0 ? 1 : 0;
        $completed += $company->certifications()->count() > 0 ? 1 : 0;
        $completed += $company->wholesaleProducts()->count() > 0 ? 1 : 0;

        return (int) round(($completed / 13) * 100);
    }
}
