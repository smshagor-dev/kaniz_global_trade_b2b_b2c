@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Proforma Invoice') }}: {{ $invoice->invoice_number }}</h1>
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
            @if ($invoice->shipments->first())
                @include('b2b.partials.shipment_timeline', ['shipment' => $invoice->shipments->sortByDesc('created_at')->first()])
            @endif
            @include('b2b.partials.trade_documents', ['documentable' => $invoice, 'documentTypeKey' => 'proforma-invoice', 'allowUpload' => false])
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">{{ translate('Summary') }}</div>
                <div class="card-body">
                    <p><strong>{{ translate('Buyer Company') }}:</strong> {{ $invoice->buyerCompany?->company_name }}</p>
                    <p><strong>{{ translate('Supplier Company') }}:</strong> {{ $invoice->supplierCompany?->company_name }}</p>
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
                    <p><strong>{{ translate('Resolution') }}:</strong> {{ $invoice->escrow_resolution ? ucfirst($invoice->escrow_resolution) : '-' }}</p>
                    <p><strong>{{ translate('Resolution Notes') }}:</strong> {{ $invoice->escrow_resolution_notes ?: '-' }}</p>
                </div>
            </div>

            @if ($invoice->usesEscrow() && in_array($invoice->escrow_status, ['funded', 'disputed']))
                <div class="card mt-3">
                    <div class="card-header">{{ translate('Escrow Actions') }}</div>
                    <div class="card-body">
                        <form action="{{ route('admin.b2b.proforma-invoices.release', $invoice->id) }}" method="POST" class="mb-3">
                            @csrf
                            <div class="form-group">
                                <textarea name="escrow_resolution_notes" class="form-control" rows="2" placeholder="{{ translate('Release notes') }}"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-block">{{ translate('Release Escrow To Supplier') }}</button>
                        </form>

                        <form action="{{ route('admin.b2b.proforma-invoices.refund', $invoice->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <textarea name="escrow_resolution_notes" class="form-control" rows="2" placeholder="{{ translate('Refund notes') }}"></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning btn-block">{{ translate('Refund Escrow To Buyer') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
