@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
        <div>
            <span class="b2b-pill">{{ translate('Supplier Workspace') }}</span>
            <h1 class="h3 mt-3 mb-1">{{ translate('Supplier Dashboard') }}</h1>
            <p class="text-muted mb-0">{{ $company->company_name }} | {{ ucfirst($company->company_type) }}</p>
        </div>
        <div class="d-flex flex-wrap">
            <a href="{{ route('seller.b2b.rfqs.index') }}" class="btn btn-primary mr-2 mb-2">{{ translate('Browse RFQs') }}</a>
            <a href="{{ route('seller.b2b.company.public-profile') }}" class="btn btn-soft-primary mb-2">{{ translate('Update Public Profile') }}</a>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        @foreach ([
            [translate('RFQ Opportunities'), $stats['pending_rfqs'] ?? 0, route('seller.b2b.rfqs.index')],
            [translate('Submitted Quotations'), $stats['quoted_rfqs'] ?? 0, route('seller.b2b.quotations.index')],
            [translate('Purchase Orders'), $stats['purchase_orders'] ?? 0, route('seller.b2b.purchase-orders.index')],
            [translate('Active Shipments'), $stats['active_shipments'] ?? 0, route('seller.b2b.shipments.index')],
            [translate('Freight Requests'), $stats['freight_quotes'] ?? 0, route('seller.b2b.freight-quotes.index')],
            [translate('Profile Completeness'), ($stats['profile_completeness'] ?? 0) . '%', route('seller.b2b.company.public-profile')],
        ] as [$label, $value, $link])
            <div class="col-md-6 col-xl-4 mb-3">
                <a href="{{ $link }}" class="text-reset d-block">
                    <div class="b2b-kpi-card">
                        <div class="text-muted fs-12 text-uppercase">{{ $label }}</div>
                        <div class="value mt-2">{{ $value }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row gutters-16 mb-4">
        @foreach ([
            [translate('Gross Revenue'), single_price($stats['finance_gross_revenue'] ?? 0), route('seller.b2b.finance.earnings')],
            [translate('Net Earnings'), single_price($stats['finance_net_earnings'] ?? 0), route('seller.b2b.finance.earnings')],
            [translate('Available Payout'), single_price($stats['finance_available_payout'] ?? 0), route('seller.b2b.finance.payouts')],
            [translate('Requested Payouts'), single_price($stats['finance_requested_payout_amount'] ?? 0), route('seller.b2b.finance.payouts')],
        ] as [$label, $value, $link])
            <div class="col-md-6 col-xl-3 mb-3">
                <a href="{{ $link }}" class="text-reset d-block">
                    <div class="b2b-kpi-card">
                        <div class="text-muted fs-12 text-uppercase">{{ $label }}</div>
                        <div class="value mt-2">{{ $value }}</div>
                    </div>
                </a>
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
                        <div class="text-muted fs-13">
                            {{ $item->description ?: translate('Activity recorded in the supplier workspace.') }}
                        </div>
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ translate('Earnings And Payouts') }}</h5>
                    <div class="d-flex flex-wrap">
                        <a href="{{ route('seller.b2b.finance.earnings') }}" class="btn btn-soft-primary btn-sm mr-2 mb-2">{{ translate('View Earnings') }}</a>
                        <a href="{{ route('seller.b2b.finance.payouts') }}" class="btn btn-primary btn-sm mb-2">{{ translate('Manage Payouts') }}</a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <a href="{{ route('seller.b2b.finance.payouts') }}" class="text-reset d-block">
                            <strong>{{ single_price($stats['finance_completed_payout_amount'] ?? 0) }}</strong>
                            <div class="text-muted fs-13">{{ translate('Completed Payouts') }}</div>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="{{ route('seller.b2b.finance.payouts') }}" class="text-reset d-block">
                            <strong>{{ single_price($stats['finance_total_payout_fees'] ?? 0) }}</strong>
                            <div class="text-muted fs-13">{{ translate('Payout Fees') }}</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('seller.b2b.finance.payouts') }}" class="text-reset d-block">
                            <strong>{{ $stats['finance_pending_settlements'] ?? 0 }}</strong>
                            <div class="text-muted fs-13">{{ translate('Pending Requests') }}</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('seller.b2b.finance.payouts') }}" class="text-reset d-block">
                            <strong>{{ $stats['finance_completed_settlements'] ?? 0 }}</strong>
                            <div class="text-muted fs-13">{{ translate('Completed Requests') }}</div>
                        </a>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="fw-600 mb-2">{{ translate('Latest Payout Queue') }}</div>
                    @forelse ($finance['releasedEscrows'] as $escrow)
                        @php($latestSettlement = $escrow->settlements->sortByDesc('id')->first())
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-600">{{ data_get($escrow->reference, 'invoice_number') ?: ('#' . $escrow->id) }}</div>
                                <div class="text-muted fs-13">
                                    {{ number_format((float) ($escrow->released_amount ?: $escrow->amount), 2) }} {{ $escrow->currency }}
                                </div>
                            </div>
                            <span class="badge badge-inline badge-soft-{{ $latestSettlement ? 'info' : 'success' }}">
                                {{ $latestSettlement ? ucfirst(str_replace('_', ' ', $latestSettlement->status)) : translate('Ready for payout') }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ translate('No released escrow is waiting for payout right now.') }}</p>
                    @endforelse
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
                            <div class="text-muted fs-13">{{ $rfq->destination_country ?? '-' }} | {{ ucfirst($rfq->status) }}</div>
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
                    <div class="col-6 mb-3"><a href="{{ route('seller.b2b.purchase-orders.index') }}" class="text-reset d-block"><strong>{{ $recent['purchaseOrders']->count() }}</strong><div class="text-muted fs-13">{{ translate('Recent POs') }}</div></a></div>
                    <div class="col-6 mb-3"><a href="{{ route('seller.b2b.proforma-invoices.index') }}" class="text-reset d-block"><strong>{{ $recent['invoices']->count() }}</strong><div class="text-muted fs-13">{{ translate('Recent Invoices') }}</div></a></div>
                    <div class="col-6"><a href="{{ route('seller.b2b.shipments.index') }}" class="text-reset d-block"><strong>{{ $recent['shipments']->count() }}</strong><div class="text-muted fs-13">{{ translate('Recent Shipments') }}</div></a></div>
                    <div class="col-6"><a href="{{ route('seller.b2b.finance.payouts') }}" class="text-reset d-block"><strong>{{ $recent['settlements']->count() }}</strong><div class="text-muted fs-13">{{ translate('Settlement Updates') }}</div></a></div>
                </div>
            </div>
        </div>
    </div>
@endsection
