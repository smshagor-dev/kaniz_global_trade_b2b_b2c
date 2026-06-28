@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3">{{ translate('Purchase Order') }}: {{ $purchaseOrder->po_number }}</h1>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('seller.b2b.purchase-orders.index') }}" class="btn btn-soft-primary">{{ translate('Back') }}</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">{{ translate('Order Items') }}</div>
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
            @include('b2b.partials.purchase_order_finance_board', ['purchaseOrder' => $purchaseOrder])
            @include('b2b.partials.shipping_quotes_table', ['quotes' => $purchaseOrder->shippingQuotes, 'allowSelect' => false])
            @if ($purchaseOrder->shipments->first())
                @include('b2b.partials.shipment_timeline', ['shipment' => $purchaseOrder->shipments->sortByDesc('created_at')->first()])
            @endif
            @include('b2b.partials.trade_documents', ['documentable' => $purchaseOrder, 'documentTypeKey' => 'purchase-order', 'allowUpload' => true])
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">{{ translate('Summary') }}</div>
                <div class="card-body">
                    <p><strong>{{ translate('Buyer Company') }}:</strong> {{ $purchaseOrder->buyerCompany?->company_name }}</p>
                    <p><strong>{{ translate('Payment Terms') }}:</strong> {{ $purchaseOrder->payment_terms ?: '-' }}</p>
                    <p><strong>{{ translate('Shipping Terms') }}:</strong> {{ $purchaseOrder->shipping_terms ?: '-' }}</p>
                    <p><strong>{{ translate('Incoterms') }}:</strong> {{ $purchaseOrder->incoterms ?: '-' }}</p>
                    <p><strong>{{ translate('Delivery Address') }}:</strong> {{ $purchaseOrder->delivery_address ?: '-' }}</p>
                    <p><strong>{{ translate('Status') }}:</strong> <span class="badge badge-inline badge-secondary">{{ ucfirst($purchaseOrder->status) }}</span></p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @if (in_array($purchaseOrder->status, ['draft', 'sent']))
                        <form action="{{ route('seller.b2b.purchase-orders.accept', $purchaseOrder->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block">{{ translate('Accept Purchase Order') }}</button>
                        </form>
                        <form action="{{ route('seller.b2b.purchase-orders.reject', $purchaseOrder->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-soft-danger btn-block">{{ translate('Reject Purchase Order') }}</button>
                        </form>
                    @endif
                    @if ($purchaseOrder->status === 'accepted')
                        <a href="{{ route('seller.b2b.proforma-invoices.create', $purchaseOrder->id) }}" class="btn btn-primary btn-block mt-2">{{ translate('Generate Proforma Invoice') }}</a>
                    @endif
                    <a href="{{ route('seller.b2b.shipping-quotes.purchase-orders.create', $purchaseOrder->id) }}" class="btn btn-soft-primary btn-block mt-2">{{ translate('Create Shipping Quote') }}</a>
                    <a href="{{ route('seller.b2b.shipments.create', ['purchase_order_id' => $purchaseOrder->id]) }}" class="btn btn-soft-info btn-block mt-2">{{ translate('Create Shipment') }}</a>
                    @if ($purchaseOrder->negotiation)
                        <a href="{{ route('seller.b2b.negotiations.show', $purchaseOrder->negotiation->id) }}" class="btn btn-soft-info btn-block mt-2">{{ translate('Open Negotiation') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
