@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="fs-20 fw-700 text-dark mb-1">{{ translate('Supplier Earnings') }}</h1>
                <p class="text-muted mb-0">{{ $company->company_name }} | {{ translate('Revenue, deductions, and net supplier earnings from B2B invoices') }}</p>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Gross Revenue') }}</div>
                    <div class="fs-24 fw-700">{{ single_price($totals['gross_revenue']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Platform Fees') }}</div>
                    <div class="fs-24 fw-700">{{ single_price($totals['platform_fees']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Net Earnings') }}</div>
                    <div class="fs-24 fw-700">{{ single_price($totals['net_earnings']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Released Earnings') }}</div>
                    <div class="fs-24 fw-700">{{ single_price($totals['released_earnings']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-md-6">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Escrow Charges') }}</div>
                    <div class="fs-24 fw-700">{{ single_price($totals['escrow_fees']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted fs-12">{{ translate('Invoice Count') }}</div>
                    <div class="fs-24 fw-700">{{ $invoices->total() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ translate('Supplier Earning History') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Invoice') }}</th>
                            <th>{{ translate('Buyer') }}</th>
                            <th>{{ translate('Gross') }}</th>
                            <th>{{ translate('Platform Fee') }}</th>
                            <th>{{ translate('Net Earning') }}</th>
                            <th>{{ translate('Escrow') }}</th>
                            <th>{{ translate('Payout Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td>
                                    <div class="fw-600">{{ $invoice->invoice_number }}</div>
                                    <small class="text-muted">{{ $invoice->created_at?->format('d M Y') }}</small>
                                </td>
                                <td>{{ $invoice->buyerCompany?->company_name ?: '-' }}</td>
                                <td>{{ number_format((float) $invoice->grand_total, 2) }} {{ $invoice->currency }}</td>
                                <td>{{ number_format((float) $invoice->platform_fee_amount, 2) }} {{ $invoice->currency }}</td>
                                <td>{{ number_format((float) $invoice->supplier_payout_amount, 2) }} {{ $invoice->currency }}</td>
                                <td>
                                    <span class="badge badge-inline badge-soft-{{ $invoice->usesEscrow() ? 'warning' : 'secondary' }}">
                                        {{ $invoice->usesEscrow() ? $invoice->escrowStatusLabel() : translate('Not Applicable') }}
                                    </span>
                                </td>
                                <td>
                                    @if ($invoice->supplier_paid_out_at)
                                        <span class="badge badge-inline badge-success">{{ translate('Released') }}</span>
                                    @else
                                        <span class="badge badge-inline badge-soft-info">{{ ucfirst($invoice->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ translate('No earning records found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $invoices->links() }}</div>
        </div>
    </div>
@endsection
