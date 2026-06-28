@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
        <div>
            <span class="b2b-pill">{{ translate('Supplier Trade Desk') }}</span>
            <h1 class="h3 mt-3 mb-1">{{ translate('Supplier Dashboard') }}</h1>
            <p class="text-muted mb-0">{{ $company->company_name }} · {{ ucfirst($company->company_type) }}</p>
        </div>
        <div class="d-flex flex-wrap">
            <a href="{{ route('seller.b2b.rfqs.index') }}" class="btn btn-primary mr-2 mb-2">{{ translate('Browse RFQs') }}</a>
            <a href="{{ route('seller.b2b.company.public-profile') }}" class="btn btn-soft-primary mb-2">{{ translate('Update Public Profile') }}</a>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        @foreach ([
            [translate('RFQ Opportunities'), $stats['pending_rfqs'] ?? 0],
            [translate('Submitted Quotations'), $stats['quoted_rfqs'] ?? 0],
            [translate('Purchase Orders'), $stats['purchase_orders'] ?? 0],
            [translate('Active Shipments'), $stats['active_shipments'] ?? 0],
            [translate('Freight Requests'), $stats['freight_quotes'] ?? 0],
            [translate('Profile Completeness'), ($stats['profile_completeness'] ?? 0) . '%'],
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
                    <span class="text-muted fs-12">{{ translate('Latest supplier-side actions') }}</span>
                </div>
                @forelse ($recent['activity'] as $item)
                    <div class="b2b-timeline-item">
                        <div class="fw-600">{{ ucwords(str_replace('_', ' ', $item->action)) }}</div>
                        <div class="text-muted fs-13">{{ $item->description ?: translate('Activity recorded in the supplier workspace.') }}</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ translate('No recent activity yet. Start by replying to an RFQ.') }}</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="b2b-section-card">
                <h5 class="mb-3">{{ translate('AI Insights') }}</h5>
                <div class="mb-3">
                    <div class="fw-600">{{ translate('Supplier Risk') }}</div>
                    <div class="text-muted fs-13">{{ $ai['supplierRisk']->summary ?? translate('No supplier risk summary available yet.') }}</div>
                </div>
                <div class="mb-3">
                    <div class="fw-600">{{ translate('Trade Opportunity') }}</div>
                    <div class="text-muted fs-13">{{ $ai['opportunity']->summary ?? translate('Opportunity scouting will appear here after AI runs.') }}</div>
                </div>
                <div>
                    <div class="fw-600">{{ translate('Dashboard Insight') }}</div>
                    <div class="text-muted fs-13">{{ $ai['dashboardInsight']->summary ?? translate('No dashboard insight generated yet.') }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="b2b-section-card">
                <h5 class="mb-3">{{ translate('RFQ Opportunities') }}</h5>
                @forelse ($recent['rfqs'] as $rfq)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <div class="fw-600">{{ $rfq->title }}</div>
                            <div class="text-muted fs-13">{{ $rfq->destination_country ?? '-' }} · {{ ucfirst($rfq->status) }}</div>
                        </div>
                        <a href="{{ route('seller.b2b.rfqs.quote', $rfq->id) }}" class="btn btn-soft-secondary btn-sm">{{ translate('Quote') }}</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ translate('No open RFQ opportunities right now.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="b2b-section-card">
                <h5 class="mb-3">{{ translate('Operations Snapshot') }}</h5>
                <div class="row">
                    <div class="col-6 mb-3"><strong>{{ $recent['purchaseOrders']->count() }}</strong><div class="text-muted fs-13">{{ translate('Recent POs') }}</div></div>
                    <div class="col-6 mb-3"><strong>{{ $recent['invoices']->count() }}</strong><div class="text-muted fs-13">{{ translate('Recent Invoices') }}</div></div>
                    <div class="col-6"><strong>{{ $recent['shipments']->count() }}</strong><div class="text-muted fs-13">{{ translate('Recent Shipments') }}</div></div>
                    <div class="col-6"><strong>{{ $recent['settlements']->count() }}</strong><div class="text-muted fs-13">{{ translate('Settlement Updates') }}</div></div>
                </div>
            </div>
        </div>
    </div>
@endsection
