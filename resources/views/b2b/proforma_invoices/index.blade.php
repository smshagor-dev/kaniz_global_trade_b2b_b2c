@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <h1 class="fs-20 fw-700 text-dark">{{ translate('Proforma Invoices') }}</h1>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Invoice Number') }}</th>
                        <th>{{ translate('Supplier') }}</th>
                        <th>{{ translate('PO') }}</th>
                        <th>{{ translate('Buyer Pays') }}</th>
                        <th>{{ translate('Escrow Fee') }}</th>
                        <th>{{ translate('Escrow Status') }}</th>
                        <th>{{ translate('Platform Fee') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->supplierCompany?->company_name }}</td>
                            <td>{{ $invoice->purchaseOrder?->po_number }}</td>
                            <td>{{ $invoice->buyer_payable_total }} {{ $invoice->currency }}</td>
                            <td>{{ $invoice->escrow_fee_amount }} {{ $invoice->currency }}</td>
                            <td><span class="badge badge-inline badge-info">{{ $invoice->escrowStatusLabel() }}</span></td>
                            <td>{{ $invoice->platform_fee_amount }} {{ $invoice->currency }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($invoice->status) }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('b2b.proforma-invoices.show', $invoice->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">{{ translate('No proforma invoices found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">{{ $invoices->links() }}</div>
        </div>
    </div>
@endsection
