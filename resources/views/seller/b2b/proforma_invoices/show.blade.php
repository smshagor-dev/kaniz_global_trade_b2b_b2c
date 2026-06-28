@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3">{{ translate('Proforma Invoice') }}: {{ $invoice->invoice_number }}</h1>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('seller.b2b.proforma-invoices.index') }}" class="btn btn-soft-primary">{{ translate('Back') }}</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">{{ translate('Invoice Items') }}</div>
                <div class="card-body">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ translate('Product') }}</th>
                                <th>{{ translate('Quantity') }}</th>
                                <th>{{ translate('Unit Price') }}</th>
                                <th>{{ translate('Line Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->quantity }} {{ $item->unit }}</td>
                                    <td>{{ $item->unit_price }} {{ $invoice->currency }}</td>
                                    <td>{{ $item->line_total }} {{ $invoice->currency }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @include('b2b.partials.trade_timeline', ['timeline' => $timeline])
            @include('b2b.partials.proforma_invoice_finance_board', ['invoice' => $invoice])
            @if ($invoice->shipments->first())
                @include('b2b.partials.shipment_timeline', ['shipment' => $invoice->shipments->sortByDesc('created_at')->first()])
            @endif
            @include('b2b.partials.trade_documents', ['documentable' => $invoice, 'documentTypeKey' => 'proforma-invoice', 'allowUpload' => true])
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">{{ translate('Summary') }}</div>
                <div class="card-body">
                    <p><strong>{{ translate('Buyer Company') }}:</strong> {{ $invoice->buyerCompany?->company_name }}</p>
                    <p><strong>{{ translate('PO') }}:</strong> {{ $invoice->purchaseOrder?->po_number }}</p>
                    <p><strong>{{ translate('Incoterm') }}:</strong> {{ $invoice->incoterm ?: '-' }}</p>
                    <p><strong>{{ translate('Buyer Payable Total') }}:</strong> {{ $invoice->buyer_payable_total }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Escrow Fee') }}:</strong> {{ $invoice->escrow_fee_amount }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Platform Service Fee') }}:</strong> {{ $invoice->platform_fee_amount }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Supplier Payout') }}:</strong> {{ $invoice->supplier_payout_amount }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Order Value') }}:</strong> {{ $invoice->grand_total }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Valid Until') }}:</strong> {{ optional($invoice->valid_until)->format('d M, Y') ?: '-' }}</p>
                    <p><strong>{{ translate('Status') }}:</strong> <span class="badge badge-inline badge-secondary">{{ ucfirst($invoice->status) }}</span></p>
                    <p><strong>{{ translate('Escrow Status') }}:</strong> <span class="badge badge-inline badge-info">{{ $invoice->escrowStatusLabel() }}</span></p>
                    <p><strong>{{ translate('Escrow Reference') }}:</strong> {{ $invoice->escrow_payment_reference ?: '-' }}</p>
                    <p><strong>{{ translate('Dispute Reason') }}:</strong> {{ $invoice->escrow_dispute_reason ?: '-' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @if ($invoice->status === 'draft')
                        <form action="{{ route('seller.b2b.proforma-invoices.send', $invoice->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block">{{ translate('Send Invoice') }}</button>
                        </form>
                    @endif
                    @if (in_array($invoice->status, ['draft', 'sent']))
                        <form action="{{ route('seller.b2b.proforma-invoices.cancel', $invoice->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-soft-danger btn-block">{{ translate('Cancel Invoice') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('seller.b2b.shipments.create', ['proforma_invoice_id' => $invoice->id, 'purchase_order_id' => $invoice->purchase_order_id]) }}" class="btn btn-soft-info btn-block mt-2">{{ translate('Create Shipment') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
