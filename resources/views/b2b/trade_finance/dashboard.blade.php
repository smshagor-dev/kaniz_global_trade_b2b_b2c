@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Trade Finance Dashboard') }}</h1>
                <p class="text-muted mb-0">{{ translate('Outstanding payments, milestones, escrow operations and dispute readiness in one board.') }}</p>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-3">
        <div class="col-md-3"><div class="card rounded-0 shadow-none border"><div class="card-body"><div class="text-muted fs-12">{{ translate('Outstanding') }}</div><div class="fs-28 fw-700">{{ single_price($stats['finance_outstanding'] ?? 0) }}</div></div></div></div>
        <div class="col-md-3"><div class="card rounded-0 shadow-none border"><div class="card-body"><div class="text-muted fs-12">{{ translate('Milestones Due') }}</div><div class="fs-28 fw-700">{{ $stats['finance_milestones_due'] ?? 0 }}</div></div></div></div>
        <div class="col-md-3"><div class="card rounded-0 shadow-none border"><div class="card-body"><div class="text-muted fs-12">{{ translate('Open Disputes') }}</div><div class="fs-28 fw-700">{{ $stats['finance_open_disputes'] ?? 0 }}</div></div></div></div>
        <div class="col-md-3"><div class="card rounded-0 shadow-none border"><div class="card-body"><div class="text-muted fs-12">{{ translate('Refunds') }}</div><div class="fs-28 fw-700">{{ $stats['finance_refunds'] ?? 0 }}</div></div></div></div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-header">{{ translate('Finance Queue') }}</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                    <tr>
                        <th>{{ translate('PO') }}</th>
                        <th>{{ translate('Supplier') }}</th>
                        <th>{{ translate('Milestones') }}</th>
                        <th>{{ translate('LC') }}</th>
                        <th>{{ translate('Disputes') }}</th>
                        <th>{{ translate('Invoice') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($purchaseOrders as $purchaseOrder)
                        <tr>
                            <td><a href="{{ route('b2b.purchase-orders.show', $purchaseOrder->id) }}">{{ $purchaseOrder->po_number }}</a></td>
                            <td>{{ $purchaseOrder->supplierCompany?->company_name }}</td>
                            <td>{{ $purchaseOrder->milestones->count() }}</td>
                            <td>{{ $purchaseOrder->lettersOfCredit->count() }}</td>
                            <td>{{ $purchaseOrder->financeDisputes->where('status', 'open')->count() }}</td>
                            <td>{{ optional($purchaseOrder->proformaInvoices->sortByDesc('id')->first())->invoice_number ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $purchaseOrders->links() }}</div>
        </div>
    </div>
@endsection
