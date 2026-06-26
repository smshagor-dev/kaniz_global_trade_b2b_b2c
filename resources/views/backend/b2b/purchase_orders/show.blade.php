@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Purchase Order') }}: {{ $purchaseOrder->po_number }}</h1>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">{{ translate('Items') }}</div>
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
                            @foreach ($purchaseOrder->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->quantity }} {{ $item->unit }}</td>
                                    <td>{{ $item->unit_price }} {{ $purchaseOrder->currency }}</td>
                                    <td>{{ $item->line_total }} {{ $purchaseOrder->currency }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @include('b2b.partials.trade_timeline', ['timeline' => $timeline])
            @include('b2b.partials.shipping_quotes_table', ['quotes' => $purchaseOrder->shippingQuotes, 'allowSelect' => false])
            @if ($purchaseOrder->shipments->first())
                @include('b2b.partials.shipment_timeline', ['shipment' => $purchaseOrder->shipments->sortByDesc('created_at')->first()])
            @endif
            @include('b2b.partials.trade_documents', ['documentable' => $purchaseOrder, 'documentTypeKey' => 'purchase-order', 'allowUpload' => false])
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">{{ translate('Summary') }}</div>
                <div class="card-body">
                    <p><strong>{{ translate('Buyer Company') }}:</strong> {{ $purchaseOrder->buyerCompany?->company_name }}</p>
                    <p><strong>{{ translate('Supplier Company') }}:</strong> {{ $purchaseOrder->supplierCompany?->company_name }}</p>
                    <p><strong>{{ translate('RFQ') }}:</strong> {{ $purchaseOrder->rfq?->title }}</p>
                    <p><strong>{{ translate('Quotation') }}:</strong> #{{ $purchaseOrder->quotation_id }}</p>
                    <p><strong>{{ translate('Payment Terms') }}:</strong> {{ $purchaseOrder->payment_terms ?: '-' }}</p>
                    <p><strong>{{ translate('Shipping Terms') }}:</strong> {{ $purchaseOrder->shipping_terms ?: '-' }}</p>
                    <p><strong>{{ translate('Incoterm') }}:</strong> {{ $purchaseOrder->incoterms ?: '-' }}</p>
                    <p><strong>{{ translate('Status') }}:</strong> <span class="badge badge-inline badge-secondary">{{ ucfirst($purchaseOrder->status) }}</span></p>
                </div>
            </div>
        </div>
    </div>
@endsection
