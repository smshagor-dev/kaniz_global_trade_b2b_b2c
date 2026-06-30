@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3 mb-0">{{ translate('Enterprise Trade Finance') }}</h1>
                <p class="text-muted mb-0">{{ translate('Enterprise oversight across escrow, settlement, milestone, dispute and refund operations.') }}</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.b2b.trade-finance.payouts') }}" class="btn btn-primary">
                    {{ translate('Manage Supplier Payouts') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-3 col-md-6"><div class="dashboard-box bg-white"><h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['finance_milestones'] ?? 0 }}</h1><h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Milestones') }}</h3></div></div>
        <div class="col-lg-3 col-md-6"><div class="dashboard-box bg-white"><h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['finance_open_disputes'] ?? 0 }}</h1><h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Open Disputes') }}</h3></div></div>
        <div class="col-lg-3 col-md-6"><div class="dashboard-box bg-white"><h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['finance_pending_settlements'] ?? 0 }}</h1><h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Pending Settlements') }}</h3></div></div>
        <div class="col-lg-3 col-md-6"><div class="dashboard-box bg-white"><h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['finance_pending_refunds'] ?? 0 }}</h1><h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Pending Refunds') }}</h3></div></div>
    </div>

    <div class="row gutters-16">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">{{ translate('Milestone Queue') }}</div>
                <div class="card-body">
                    @foreach($milestones as $milestone)
                        <div class="border p-3 mb-2">
                            <div class="d-flex justify-content-between"><strong>{{ $milestone->title }}</strong><span class="badge badge-inline badge-secondary">{{ ucfirst($milestone->status) }}</span></div>
                            <div class="text-muted fs-12">{{ optional($milestone->purchaseOrder)->po_number }} | {{ $milestone->amount }} {{ $milestone->currency }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">{{ translate('Dispute Queue') }}</div>
                <div class="card-body">
                    @foreach($disputes as $dispute)
                        <div class="border p-3 mb-2">
                            <div class="d-flex justify-content-between"><strong>{{ $dispute->title }}</strong><span class="badge badge-inline badge-warning">{{ ucfirst($dispute->status) }}</span></div>
                            <div class="text-muted fs-12">{{ ucfirst(str_replace('_', ' ', $dispute->category)) }}</div>
                            @if($dispute->status !== 'resolved')
                                <form action="{{ route('admin.b2b.trade-finance.disputes.resolve', $dispute->id) }}" method="POST" class="mt-2">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <select name="resolution" class="form-control">
                                            <option value="release">{{ translate('Release') }}</option>
                                            <option value="refund">{{ translate('Refund') }}</option>
                                            <option value="hold">{{ translate('Hold') }}</option>
                                        </select>
                                        <div class="input-group-append"><button class="btn btn-primary">{{ translate('Resolve') }}</button></div>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">{{ translate('Settlement And Refund Queue') }}</div>
                <div class="card-body">
                    @foreach($settlements as $settlement)
                        <div class="border p-3 mb-2">
                            <div class="d-flex justify-content-between"><strong>{{ ucfirst(str_replace('_', ' ', $settlement->settlement_method)) }}</strong><span class="badge badge-inline badge-info">{{ ucfirst($settlement->status) }}</span></div>
                            <div class="text-muted fs-12">{{ $settlement->net_amount }} {{ $settlement->currency }}</div>
                            @if($settlement->status === 'pending_approval')
                                <form action="{{ route('admin.b2b.trade-finance.settlements.approve', $settlement->id) }}" method="POST" class="mt-2">@csrf<button class="btn btn-sm btn-success">{{ translate('Approve Settlement') }}</button></form>
                            @elseif($settlement->status === 'approved')
                                <form action="{{ route('admin.b2b.trade-finance.settlements.complete', $settlement->id) }}" method="POST" class="mt-2">@csrf<button class="btn btn-sm btn-primary">{{ translate('Complete Settlement') }}</button></form>
                            @endif
                        </div>
                    @endforeach
                    @foreach($refunds as $refund)
                        <div class="border p-3 mb-2">
                            <div class="d-flex justify-content-between"><strong>{{ ucfirst($refund->refund_type) }} {{ translate('Refund') }}</strong><span class="badge badge-inline badge-danger">{{ ucfirst($refund->status) }}</span></div>
                            <div class="text-muted fs-12">{{ $refund->amount }} {{ $refund->currency }}</div>
                            @if($refund->status === 'pending_approval')
                                <form action="{{ route('admin.b2b.trade-finance.refunds.approve', $refund->id) }}" method="POST" class="mt-2">@csrf<button class="btn btn-sm btn-success">{{ translate('Approve Refund') }}</button></form>
                            @elseif($refund->status === 'approved')
                                <form action="{{ route('admin.b2b.trade-finance.refunds.complete', $refund->id) }}" method="POST" class="mt-2">@csrf<button class="btn btn-sm btn-primary">{{ translate('Complete Refund') }}</button></form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
