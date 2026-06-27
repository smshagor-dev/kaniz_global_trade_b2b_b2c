@extends('backend.layouts.app')

@section('content')
    @php
        $companyApprovalPercent = ($b2b_stats['total_companies'] ?? 0) > 0
            ? round((($b2b_stats['verified_companies'] ?? 0) / $b2b_stats['total_companies']) * 100)
            : 0;
        $rfqConversionPercent = ($b2b_stats['rfqs'] ?? 0) > 0
            ? round((($b2b_stats['purchase_orders'] ?? 0) / $b2b_stats['rfqs']) * 100)
            : 0;
        $shipmentTotal = ($b2b_stats['pending_shipments'] ?? 0) + ($b2b_stats['awaiting_pickup_shipments'] ?? 0) + ($b2b_stats['active_shipments'] ?? 0) + ($b2b_stats['completed_shipments'] ?? 0) + ($b2b_stats['delayed_shipments'] ?? 0) + ($b2b_stats['exception_shipments'] ?? 0);
        $shipmentCompletionPercent = $shipmentTotal > 0
            ? round((($b2b_stats['completed_shipments'] ?? 0) / $shipmentTotal) * 100)
            : 0;
    @endphp

    <div class="aiz-titlebar text-left mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3 mb-0">{{ translate('B2B Dashboard') }}</h1>
                <p class="text-muted mb-0">{{ translate('A compact overview of B2B activity and operational progress.') }}</p>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-3">
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $b2b_stats['total_companies'] }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Total Companies') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $b2b_stats['rfqs'] }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Total RFQs') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $b2b_stats['purchase_orders'] }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Purchase Orders') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ single_price($b2b_stats['trade_volume']) }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Trade Volume') }}</h3>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $b2b_stats['total_buyers'] }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Total Buyers') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $b2b_stats['total_suppliers'] }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Total Suppliers') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $b2b_stats['active_featured_homepage_suppliers'] }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Homepage Featured Suppliers') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ single_price($b2b_stats['platform_profit']) }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('B2B Platform Profit') }}</h3>
                <div class="fs-11 text-muted mt-1">
                    {{ translate('Order') }}: {{ single_price($b2b_stats['order_platform_profit'] ?? 0) }}
                    /
                    {{ translate('Escrow') }}: {{ single_price($b2b_stats['escrow_platform_profit'] ?? 0) }}
                    /
                    {{ translate('Sample') }}: {{ single_price($b2b_stats['sample_processing_platform_profit'] ?? 0) }}
                    /
                    {{ translate('Inspection') }}: {{ single_price($b2b_stats['inspection_platform_profit'] ?? 0) }}
                    /
                    {{ translate('Documents') }}: {{ single_price($b2b_stats['trade_document_platform_profit'] ?? 0) }}
                    /
                    {{ translate('Shipping') }}: {{ single_price($b2b_stats['shipping_platform_profit'] ?? 0) }}
                    /
                    {{ translate('Freight') }}: {{ single_price($b2b_stats['freight_platform_profit'] ?? 0) }}
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="fs-12 text-warning fw-700 mb-2">{{ translate('Featured Supplier Revenue') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">{{ single_price($b2b_stats['featured_supplier_monthly_revenue'] ?? 0) }}</h3>
                    <p class="text-muted mb-0">
                        {{ translate('Current monthly-equivalent revenue from active supplier homepage featured plans.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 border-warning">
                <div class="card-body">
                    <div class="fs-12 text-warning fw-700 mb-2">{{ translate('Featured Supplier Projection') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">
                        {{ single_price($b2b_stats['featured_supplier_projection_monthly_revenue'] ?? 0) }}/{{ translate('month') }}
                    </h3>
                    <p class="text-muted mb-1">
                        {{ $b2b_stats['featured_supplier_projection_count'] ?? 0 }} {{ translate('companies') }}
                        x
                        {{ single_price($b2b_stats['featured_supplier_monthly_price'] ?? 0) }}
                    </p>
                    <p class="text-muted mb-0">{{ $b2b_stats['featured_supplier_plan_name'] ?: translate('Create a featured supplier package to enable this projection.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 border-info">
                <div class="card-body">
                    <div class="fs-12 text-info fw-700 mb-2">{{ translate('Sponsored Product Revenue') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">{{ single_price($b2b_stats['sponsored_product_monthly_revenue'] ?? 0) }}/{{ translate('month') }}</h3>
                    <p class="text-muted mb-1">
                        {{ $b2b_stats['active_sponsored_products'] ?? 0 }} {{ translate('products are currently sponsored.') }}
                    </p>
                    <p class="text-muted mb-0">
                        {{ $b2b_stats['sponsored_product_plan_name'] ?: translate('Create a sponsored product package to enable this revenue stream.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-4">
            <div class="card h-100 border-info">
                <div class="card-body">
                    <div class="fs-12 text-info fw-700 mb-2">{{ translate('Sponsored Product Projection') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">
                        {{ single_price($b2b_stats['sponsored_product_projection_monthly_revenue'] ?? 0) }}/{{ translate('month') }}
                    </h3>
                    <p class="text-muted mb-1">
                        {{ $b2b_stats['sponsored_product_projection_count'] ?? 0 }} {{ translate('products') }}
                        x
                        {{ single_price($b2b_stats['sponsored_product_monthly_unit_price'] ?? 0) }}
                    </p>
                    <p class="text-muted mb-0">{{ $b2b_stats['sponsored_product_plan_name'] ?: translate('Create a sponsored product package to enable this projection.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="fs-12 text-success fw-700 mb-2">{{ translate('Premium Verification Revenue') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">{{ single_price($b2b_stats['premium_verification_total_revenue'] ?? 0) }}</h3>
                    <p class="text-muted mb-0">
                        {{ translate('Total collected revenue from approved premium company verification purchases.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <div class="fs-12 text-success fw-700 mb-2">{{ translate('Premium Verification Projection') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">
                        {{ single_price($b2b_stats['premium_verification_projection_revenue'] ?? 0) }}
                    </h3>
                    <p class="text-muted mb-1">
                        {{ $b2b_stats['premium_verification_projection_count'] ?? 0 }} {{ translate('companies') }}
                        x
                        {{ single_price($b2b_stats['premium_verification_price'] ?? 0) }}
                    </p>
                    <p class="text-muted mb-0">{{ $b2b_stats['premium_verification_plan_name'] ?: translate('Create a premium verification package to enable this projection.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 border-secondary">
                <div class="card-body">
                    <div class="fs-12 text-secondary fw-700 mb-2">{{ translate('Sample Processing Revenue') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">{{ single_price($b2b_stats['sample_processing_fee_revenue'] ?? 0) }}</h3>
                    <p class="text-muted mb-0">
                        {{ translate('Total collected revenue from paid sample processing fees.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100 border-secondary">
                <div class="card-body">
                    <div class="fs-12 text-secondary fw-700 mb-2">{{ translate('Sample Processing Projection') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">
                        {{ single_price($b2b_stats['sample_processing_projection_revenue'] ?? 0) }}
                    </h3>
                    <p class="text-muted mb-1">
                        {{ $b2b_stats['sample_processing_projection_count'] ?? 0 }} {{ translate('sample orders') }}
                        x
                        {{ single_price($b2b_stats['sample_processing_unit_fee'] ?? 0) }}
                    </p>
                    <p class="text-muted mb-0">
                        @if (($b2b_stats['sample_processing_fee_type'] ?? 'fixed') === 'percentage')
                            {{ $b2b_stats['sample_processing_fee_percent'] ?? 0 }}% {{ translate('fee based on average sample subtotal') }} {{ single_price($b2b_stats['sample_processing_average_subtotal'] ?? 0) }}.
                        @else
                            {{ translate('Projection based on current sample order volume.') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 border-dark">
                <div class="card-body">
                    <div class="fs-12 text-dark fw-700 mb-2">{{ translate('Inspection Service Revenue') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">{{ single_price($b2b_stats['inspection_service_charge_revenue'] ?? 0) }}</h3>
                    <p class="text-muted mb-0">
                        {{ translate('Total collected revenue from inspection service charges on freight inspections.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100 border-dark">
                <div class="card-body">
                    <div class="fs-12 text-dark fw-700 mb-2">{{ translate('Inspection Service Projection') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">
                        {{ single_price($b2b_stats['inspection_projection_revenue'] ?? 0) }}
                    </h3>
                    <p class="text-muted mb-1">
                        {{ $b2b_stats['inspection_projection_count'] ?? 0 }} {{ translate('inspections') }}
                        x
                        {{ single_price($b2b_stats['inspection_service_charge_unit'] ?? 0) }}
                    </p>
                    <p class="text-muted mb-0">
                        @if (($b2b_stats['inspection_service_charge_type'] ?? 'fixed') === 'percentage')
                            {{ $b2b_stats['inspection_service_charge_percent'] ?? 0 }}% {{ translate('charge based on average inspection fee') }} {{ single_price($b2b_stats['inspection_average_fee'] ?? 0) }}.
                        @else
                            {{ translate('Projection based on current inspection volume.') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <div class="fs-12 text-primary fw-700 mb-2">{{ translate('Trade Document Revenue') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">{{ single_price($b2b_stats['trade_document_fee_revenue'] ?? 0) }}</h3>
                    <p class="text-muted mb-0">
                        {{ translate('Total collected revenue from commercial invoice, packing list, certificate of origin, and bill of lading generation.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <div class="fs-12 text-primary fw-700 mb-2">{{ translate('Trade Document Projection') }}</div>
                    <h3 class="fs-28 fw-700 text-dark mb-2">
                        {{ single_price($b2b_stats['trade_document_projection_revenue'] ?? 0) }}
                    </h3>
                    <p class="text-muted mb-1">
                        {{ $b2b_stats['trade_document_projection_count'] ?? 0 }} {{ translate('documents') }}
                        x
                        {{ single_price($b2b_stats['trade_document_unit_fee'] ?? 0) }}
                    </p>
                    <p class="text-muted mb-0">
                        @if (($b2b_stats['trade_document_fee_type'] ?? 'fixed') === 'percentage')
                            {{ $b2b_stats['trade_document_fee_percent'] ?? 0 }}% {{ translate('service fee on related document value.') }}
                        @else
                            {{ translate('Projection based on chargeable trade documents already generated.') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">{{ translate('Company Approval Progress') }}</h5>
                        <span class="badge badge-soft-success">{{ $companyApprovalPercent }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $companyApprovalPercent }}%;"></div>
                    </div>
                    <div class="d-flex justify-content-between fs-13 mb-2">
                        <span class="text-muted">{{ translate('Verified') }}</span>
                        <span class="fw-600">{{ $b2b_stats['verified_companies'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between fs-13">
                        <span class="text-muted">{{ translate('Pending Review') }}</span>
                        <span class="fw-600">{{ $b2b_stats['pending_companies'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">{{ translate('RFQ To PO Progress') }}</h5>
                        <span class="badge badge-soft-info">{{ $rfqConversionPercent }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $rfqConversionPercent }}%;"></div>
                    </div>
                    <div class="d-flex justify-content-between fs-13 mb-2">
                        <span class="text-muted">{{ translate('Open RFQs') }}</span>
                        <span class="fw-600">{{ $b2b_stats['open_rfqs'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between fs-13 mb-2">
                        <span class="text-muted">{{ translate('Quoted RFQs') }}</span>
                        <span class="fw-600">{{ $b2b_stats['quoted_rfqs'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between fs-13">
                        <span class="text-muted">{{ translate('Purchase Orders') }}</span>
                        <span class="fw-600">{{ $b2b_stats['purchase_orders'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">{{ translate('Shipment Completion') }}</h5>
                        <span class="badge badge-soft-primary">{{ $shipmentCompletionPercent }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $shipmentCompletionPercent }}%;"></div>
                    </div>
                    <div class="d-flex justify-content-between fs-13 mb-2">
                        <span class="text-muted">{{ translate('Active') }}</span>
                        <span class="fw-600">{{ $b2b_stats['active_shipments'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between fs-13 mb-2">
                        <span class="text-muted">{{ translate('Completed') }}</span>
                        <span class="fw-600">{{ $b2b_stats['completed_shipments'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between fs-13">
                        <span class="text-muted">{{ translate('Delayed') }}</span>
                        <span class="fw-600 text-danger">{{ $b2b_stats['delayed_shipments'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0">
                    <h5 class="mb-0">{{ translate('B2B Operations Snapshot') }}</h5>
                </div>
                <div class="card-body pt-0">
                    <div class="row gutters-10">
                        <div class="col-md-4 col-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fs-12 text-muted">{{ translate('Approved Suppliers') }}</div>
                                <div class="fs-22 fw-700">{{ $b2b_stats['approved_suppliers'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fs-12 text-muted">{{ translate('Premium Verified Companies') }}</div>
                                <div class="fs-22 fw-700">{{ $b2b_stats['premium_verified_companies'] ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fs-12 text-muted">{{ translate('Pending Certifications') }}</div>
                                <div class="fs-22 fw-700">{{ $b2b_stats['pending_certifications'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fs-12 text-muted">{{ translate('Freight Quotes') }}</div>
                                <div class="fs-22 fw-700">{{ $b2b_stats['freight_quotes'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fs-12 text-muted">{{ translate('Invoices') }}</div>
                                <div class="fs-22 fw-700">{{ $b2b_stats['invoices'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fs-12 text-muted">{{ translate('Sample Orders') }}</div>
                                <div class="fs-22 fw-700">{{ $b2b_stats['sample_orders'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fs-12 text-muted">{{ translate('Trade Countries') }}</div>
                                <div class="fs-22 fw-700">{{ $b2b_stats['countries'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fs-12 text-muted">{{ translate('Trade Documents') }}</div>
                                <div class="fs-22 fw-700">{{ $b2b_stats['trade_documents'] ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fs-12 text-muted">{{ translate('Chargeable Documents') }}</div>
                                <div class="fs-22 fw-700">{{ $b2b_stats['chargeable_trade_documents'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header border-0">
                    <h5 class="mb-0">{{ translate('Quick Access') }}</h5>
                </div>
                <div class="card-body pt-0">
                    <a href="{{ route('admin.b2b.companies.index') }}" class="btn btn-soft-primary btn-block mb-2">{{ translate('Companies') }}</a>
                    <a href="{{ route('admin.b2b.rfqs.index') }}" class="btn btn-soft-info btn-block mb-2">{{ translate('RFQs') }}</a>
                    <a href="{{ route('admin.b2b.purchase-orders.index') }}" class="btn btn-soft-success btn-block mb-2">{{ translate('Purchase Orders') }}</a>
                    <a href="{{ route('admin.b2b.shipments.index') }}" class="btn btn-soft-warning btn-block mb-2">{{ translate('Shipments') }}</a>
                    <a href="{{ route('admin.b2b.freight-quotes.index') }}" class="btn btn-soft-secondary btn-block mb-2">{{ translate('Freight Quotes') }}</a>
                    <a href="{{ route('admin.b2b.insurance.dashboard') }}" class="btn btn-soft-info btn-block mb-2">{{ translate('Insurance Dashboard') }}</a>
                    <a href="{{ route('admin.b2b.logistics-charge-settings.index') }}" class="btn btn-soft-info btn-block mb-2">{{ translate('Logistics Charges') }}</a>
                    <a href="{{ route('ai-config') }}" class="btn btn-soft-primary btn-block mb-2">{{ translate('B2B AI Providers') }}</a>
                    <a href="{{ route('admin.b2b.audit-logs.index') }}" class="btn btn-soft-danger btn-block">{{ translate('Audit Logs') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
