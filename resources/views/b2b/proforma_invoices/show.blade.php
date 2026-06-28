@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Proforma Invoice') }}: {{ $invoice->invoice_number }}</h1>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('b2b.proforma-invoices.index') }}" class="btn btn-soft-primary rounded-0">{{ translate('Back') }}</a>
            </div>
        </div>
    </div>

    <div class="row gutters-16">
        <div class="col-lg-8">
            <div class="card rounded-0 shadow-none border">
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
            @include('b2b.partials.trade_documents', ['documentable' => $invoice, 'documentTypeKey' => 'proforma-invoice', 'allowUpload' => false])
        </div>

        <div class="col-lg-4">
            <div class="card rounded-0 shadow-none border">
                <div class="card-header">{{ translate('Summary') }}</div>
                <div class="card-body">
                    <p><strong>{{ translate('Supplier') }}:</strong> {{ $invoice->supplierCompany?->company_name }}</p>
                    <p><strong>{{ translate('PO') }}:</strong> {{ $invoice->purchaseOrder?->po_number }}</p>
                    <p><strong>{{ translate('Subtotal') }}:</strong> {{ $invoice->subtotal }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Tax') }}:</strong> {{ $invoice->tax_amount }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Shipping') }}:</strong> {{ $invoice->shipping_amount }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Incoterm') }}:</strong> {{ $invoice->incoterm ?: '-' }}</p>
                    <p><strong>{{ translate('Discount') }}:</strong> {{ $invoice->discount_amount }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Buyer Payable Total') }}:</strong> {{ $invoice->buyer_payable_total }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Escrow Fee') }}:</strong> {{ $invoice->escrow_fee_amount }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Platform Service Fee') }}:</strong> {{ $invoice->platform_fee_amount }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Supplier Payout') }}:</strong> {{ $invoice->supplier_payout_amount }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Order Value') }}:</strong> {{ $invoice->grand_total }} {{ $invoice->currency }}</p>
                    <p><strong>{{ translate('Valid Until') }}:</strong> {{ optional($invoice->valid_until)->format('d M, Y') ?: '-' }}</p>
                    <p><strong>{{ translate('Status') }}:</strong> <span class="badge badge-inline badge-secondary">{{ ucfirst($invoice->status) }}</span></p>
                    <p><strong>{{ translate('Escrow Status') }}:</strong> <span class="badge badge-inline badge-info">{{ $invoice->escrowStatusLabel() }}</span></p>
                    <p><strong>{{ translate('Escrow Reference') }}:</strong> {{ $invoice->escrow_payment_reference ?: '-' }}</p>
                    <p><strong>{{ translate('Escrow Dispute') }}:</strong> {{ $invoice->escrow_dispute_reason ?: '-' }}</p>
                    <p class="mb-0"><strong>{{ translate('Notes') }}:</strong> {{ $invoice->notes ?: '-' }}</p>
                </div>
            </div>

            @if ($invoice->status === 'sent')
                <form action="{{ route('b2b.proforma-invoices.accept', $invoice->id) }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-success btn-block rounded-0">{{ translate('Accept Proforma Invoice') }}</button>
                </form>
            @endif
            @if ($invoice->canFundEscrow())
                <form action="{{ route('b2b.proforma-invoices.fund', $invoice->id) }}" method="POST" class="mt-3">
                    @csrf
                    <div class="form-group mb-2">
                        <input type="text" name="escrow_payment_reference" class="form-control rounded-0" placeholder="{{ translate('Escrow payment reference') }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block rounded-0">{{ translate('Fund Escrow') }}</button>
                </form>
            @endif
            @if ($invoice->canReleaseEscrow())
                <form action="{{ route('b2b.proforma-invoices.release', $invoice->id) }}" method="POST" class="mt-3">
                    @csrf
                    <div class="form-group mb-2">
                        <textarea name="escrow_resolution_notes" class="form-control rounded-0" rows="2" placeholder="{{ translate('Release note (optional)') }}"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block rounded-0">{{ translate('Release Escrow') }}</button>
                </form>
            @endif
            @if ($invoice->canDisputeEscrow())
                <form action="{{ route('b2b.proforma-invoices.dispute', $invoice->id) }}" method="POST" class="mt-3">
                    @csrf
                    <div class="form-group mb-2">
                        <textarea name="escrow_dispute_reason" class="form-control rounded-0" rows="3" placeholder="{{ translate('Describe the escrow dispute') }}" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning btn-block rounded-0">{{ translate('Raise Escrow Dispute') }}</button>
                </form>
            @endif
            @if ($invoice->shipments->first())
                <a href="{{ route('b2b.shipments.show', $invoice->shipments->sortByDesc('created_at')->first()->id) }}" class="btn btn-soft-info btn-block rounded-0 mt-2">{{ translate('Track Shipment') }}</a>
            @endif
        </div>
    </div>
@endsection
