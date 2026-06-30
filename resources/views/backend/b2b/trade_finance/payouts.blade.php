@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3 mb-0">{{ translate('Supplier Payout Management') }}</h1>
                <p class="text-muted mb-0">{{ translate('Monitor released escrows, supplier payout requests, approvals, and completed disbursements.') }}</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.b2b.trade-finance.dashboard') }}" class="btn btn-soft-primary">
                    {{ translate('Back To Trade Finance') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-3 col-md-6"><div class="dashboard-box bg-white"><h1 class="fs-30 fw-600 text-dark mb-1">{{ single_price($summary['available_payout'] ?? 0) }}</h1><h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Available Payout') }}</h3></div></div>
        <div class="col-lg-3 col-md-6"><div class="dashboard-box bg-white"><h1 class="fs-30 fw-600 text-dark mb-1">{{ $summary['pending_requests'] ?? 0 }}</h1><h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Pending Requests') }}</h3></div></div>
        <div class="col-lg-3 col-md-6"><div class="dashboard-box bg-white"><h1 class="fs-30 fw-600 text-dark mb-1">{{ $summary['approved_requests'] ?? 0 }}</h1><h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Approved Requests') }}</h3></div></div>
        <div class="col-lg-3 col-md-6"><div class="dashboard-box bg-white"><h1 class="fs-30 fw-600 text-dark mb-1">{{ single_price($summary['completed_payouts'] ?? 0) }}</h1><h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Completed Payouts') }}</h3></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('Released Escrows Ready For Supplier Payout') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Escrow') }}</th>
                            <th>{{ translate('Supplier Company') }}</th>
                            <th>{{ translate('Reference') }}</th>
                            <th>{{ translate('Released Amount') }}</th>
                            <th>{{ translate('Released At') }}</th>
                            <th>{{ translate('Payout Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($releasedEscrows as $escrow)
                            @php($latestSettlement = $escrow->settlements->sortByDesc('id')->first())
                            <tr>
                                <td>#{{ $escrow->id }}</td>
                                <td>{{ optional(optional($escrow->paymentTransaction)->supplierCompany)->company_name ?: ('#' . $escrow->supplier_company_id) }}</td>
                                <td>{{ data_get($escrow->reference, 'invoice_number') ?: class_basename($escrow->reference_type) }}</td>
                                <td>{{ number_format((float) ($escrow->released_amount ?: $escrow->amount), 2) }} {{ $escrow->currency }}</td>
                                <td>{{ $escrow->released_at?->format('d M Y, h:i A') ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-inline badge-soft-{{ $latestSettlement ? 'info' : 'success' }}">
                                        {{ $latestSettlement ? ucfirst(str_replace('_', ' ', $latestSettlement->status)) : translate('Ready for request') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ translate('No released escrows found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $releasedEscrows->links() }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('Supplier Payout Requests') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Settlement') }}</th>
                            <th>{{ translate('Supplier Company') }}</th>
                            <th>{{ translate('Method') }}</th>
                            <th>{{ translate('Net Amount') }}</th>
                            <th>{{ translate('Requested At') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($settlements as $settlement)
                            <tr>
                                <td>
                                    <div class="fw-600">#{{ $settlement->id }}</div>
                                    <small class="text-muted">{{ $settlement->reference ?: '-' }}</small>
                                </td>
                                <td>{{ optional(optional(optional($settlement->escrow)->paymentTransaction)->supplierCompany)->company_name ?: ('#' . $settlement->supplier_company_id) }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $settlement->settlement_method)) }}</td>
                                <td>{{ number_format((float) $settlement->net_amount, 2) }} {{ $settlement->currency }}</td>
                                <td>{{ $settlement->requested_at?->format('d M Y, h:i A') ?? '-' }}</td>
                                <td><span class="badge badge-inline badge-soft-primary">{{ ucfirst(str_replace('_', ' ', $settlement->status)) }}</span></td>
                                <td>
                                    @if ($settlement->status === 'pending_approval')
                                        <form action="{{ route('admin.b2b.trade-finance.settlements.approve', $settlement->id) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <button class="btn btn-success btn-sm">{{ translate('Approve') }}</button>
                                        </form>
                                    @elseif ($settlement->status === 'approved')
                                        <form action="{{ route('admin.b2b.trade-finance.settlements.complete', $settlement->id) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <button class="btn btn-primary btn-sm">{{ translate('Complete') }}</button>
                                        </form>
                                    @else
                                        <span class="text-muted">{{ translate('Completed') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ translate('No supplier payout requests found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $settlements->links() }}</div>
        </div>
    </div>
@endsection
