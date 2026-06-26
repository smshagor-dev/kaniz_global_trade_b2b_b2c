@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3">{{ translate('Supplier Trade Finance Dashboard') }}</h1>
                <p class="text-muted mb-0">{{ translate('Settlement operations, released escrow, disputes and milestone pipeline.') }}</p>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted fs-12">{{ translate('Pending Settlements') }}</div><div class="fs-28 fw-700">{{ $stats['finance_pending_settlements'] ?? 0 }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted fs-12">{{ translate('Completed Settlements') }}</div><div class="fs-28 fw-700">{{ $stats['finance_completed_settlements'] ?? 0 }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted fs-12">{{ translate('Open Disputes') }}</div><div class="fs-28 fw-700">{{ $stats['finance_open_disputes'] ?? 0 }}</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted fs-12">{{ translate('Milestones Active') }}</div><div class="fs-28 fw-700">{{ $stats['finance_milestones_due'] ?? 0 }}</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header">{{ translate('Supplier Finance Queue') }}</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                    <tr>
                        <th>{{ translate('PO') }}</th>
                        <th>{{ translate('Buyer') }}</th>
                        <th>{{ translate('Milestones') }}</th>
                        <th>{{ translate('LC') }}</th>
                        <th>{{ translate('Disputes') }}</th>
                        <th>{{ translate('Invoice') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($purchaseOrders as $purchaseOrder)
                        <tr>
                            <td><a href="{{ route('seller.b2b.purchase-orders.show', $purchaseOrder->id) }}">{{ $purchaseOrder->po_number }}</a></td>
                            <td>{{ $purchaseOrder->buyerCompany?->company_name }}</td>
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
