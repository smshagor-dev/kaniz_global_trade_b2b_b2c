@extends('b2b.layouts.buyer')

@section('panel_content')
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
        <div>
            <span class="b2b-pill">{{ translate('Buyer Command Center') }}</span>
            <h1 class="h3 mt-3 mb-1">{{ translate('Buyer Dashboard') }}</h1>
            <p class="text-muted mb-0">{{ $company->company_name }} · {{ ucfirst($company->company_type) }}</p>
        </div>
        <div class="d-flex flex-wrap">
            <a href="{{ route('b2b.rfqs.create') }}" class="btn btn-primary mr-2 mb-2">{{ translate('Create RFQ') }}</a>
            <a href="{{ route('b2b.ai.dashboard') }}" class="btn btn-soft-primary mb-2">{{ translate('Open AI Trade Desk') }}</a>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        @foreach ([
            [translate('RFQs'), $stats['requested_rfqs'] ?? 0],
            [translate('Received Quotations'), $stats['received_quotes'] ?? 0],
            [translate('Purchase Orders'), $stats['purchase_orders'] ?? 0],
            [translate('Shipments In Motion'), $stats['current_shipments'] ?? 0],
            [translate('Freight Quotes'), $stats['freight_quotes'] ?? 0],
            [translate('Insurance Policies'), $stats['insurance_policies'] ?? 0],
        ] as [$label, $value])
            <div class="col-md-6 col-xl-4 mb-3">
                <div class="b2b-kpi-card">
                    <div class="text-muted fs-12 text-uppercase">{{ $label }}</div>
                    <div class="value mt-2">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row gutters-16">
        <div class="col-lg-8 mb-3">
            <div class="b2b-section-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ translate('Recent Activity') }}</h5>
                    <span class="text-muted fs-12">{{ translate('Latest buyer-side actions') }}</span>
                </div>
                @forelse ($recent['activity'] as $item)
                    <div class="b2b-timeline-item">
                        <div class="fw-600">{{ ucwords(str_replace('_', ' ', $item->action)) }}</div>
                        <div class="text-muted fs-13">{{ $item->description ?: translate('Activity recorded in the trade workspace.') }}</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ translate('No recent activity yet. Start with an RFQ or supplier search.') }}</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="b2b-section-card">
                <h5 class="mb-3">{{ translate('AI Recommendations') }}</h5>
                <div class="mb-3">
                    <div class="fw-600">{{ translate('Price Recommendation') }}</div>
                    <div class="text-muted fs-13">{{ $ai['priceRecommendation']->summary ?? translate('No recommendation generated yet.') }}</div>
                </div>
                <div class="mb-3">
                    <div class="fw-600">{{ translate('Supplier Risk') }}</div>
                    <div class="text-muted fs-13">{{ $ai['supplierRisk']->summary ?? translate('Run a supplier risk check from the AI Trade Desk.') }}</div>
                </div>
                <div>
                    <div class="fw-600">{{ translate('Dashboard Insight') }}</div>
                    <div class="text-muted fs-13">{{ $ai['dashboardInsight']->summary ?? translate('Insights will appear here after the first AI analysis run.') }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="b2b-section-card">
                <h5 class="mb-3">{{ translate('RFQs and Quotations') }}</h5>
                @forelse ($recent['rfqs'] as $rfq)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <div class="fw-600">{{ $rfq->title }}</div>
                            <div class="text-muted fs-13">{{ $rfq->quantity }} {{ $rfq->unit }} · {{ ucfirst($rfq->status) }}</div>
                        </div>
                        <a href="{{ route('b2b.rfqs.show', $rfq->id) }}" class="btn btn-soft-secondary btn-sm">{{ translate('Open') }}</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ translate('No RFQs yet.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="b2b-section-card">
                <h5 class="mb-3">{{ translate('Trade Operations Snapshot') }}</h5>
                <div class="row">
                    <div class="col-6 mb-3"><strong>{{ $recent['purchaseOrders']->count() }}</strong><div class="text-muted fs-13">{{ translate('Recent POs') }}</div></div>
                    <div class="col-6 mb-3"><strong>{{ $recent['invoices']->count() }}</strong><div class="text-muted fs-13">{{ translate('Recent Invoices') }}</div></div>
                    <div class="col-6"><strong>{{ $recent['shipments']->count() }}</strong><div class="text-muted fs-13">{{ translate('Recent Shipments') }}</div></div>
                    <div class="col-6"><strong>{{ $recent['containers']->count() }}</strong><div class="text-muted fs-13">{{ translate('Container Updates') }}</div></div>
                </div>
            </div>
        </div>
    </div>
@endsection
