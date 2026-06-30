@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Purchase Order') }}: {{ $purchaseOrder->po_number }}</h1>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('b2b.purchase-orders.index') }}" class="btn btn-soft-primary rounded-0">{{ translate('Back') }}</a>
            </div>
        </div>
    </div>

    <div class="row gutters-16">
        <div class="col-lg-8">
            <div class="card rounded-0 shadow-none border">
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
                                    <td>
                                        <div>{{ $item->product_name }}</div>
                                        @if ($item->description)
                                            <small class="text-muted">{{ $item->description }}</small>
                                        @endif
                                    </td>
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
            @include('b2b.partials.shipping_quotes_table', ['quotes' => $purchaseOrder->shippingQuotes, 'allowSelect' => true])
            @if ($purchaseOrder->shipments->first())
                @include('b2b.partials.shipment_timeline', ['shipment' => $purchaseOrder->shipments->sortByDesc('created_at')->first()])
            @endif
            @include('b2b.partials.trade_documents', ['documentable' => $purchaseOrder, 'documentTypeKey' => 'purchase-order', 'allowUpload' => false])
        </div>

        <div class="col-lg-4">
            <div class="card rounded-0 shadow-none border mb-3">
                <div class="card-header">{{ translate('Summary') }}</div>
                <div class="card-body">
                    <p><strong>{{ translate('Supplier') }}:</strong> {{ $purchaseOrder->supplierCompany?->company_name }}</p>
                    <p><strong>{{ translate('RFQ') }}:</strong> {{ $purchaseOrder->rfq?->title }}</p>
                    <p><strong>{{ translate('Payment Terms') }}:</strong> {{ $purchaseOrder->payment_terms ?: '-' }}</p>
                    <p><strong>{{ translate('Shipping Terms') }}:</strong> {{ $purchaseOrder->shipping_terms ?: '-' }}</p>
                    <p><strong>{{ translate('Incoterm') }}:</strong> {{ $purchaseOrder->incoterms ?: '-' }}</p>
                    <p><strong>{{ translate('Delivery Deadline') }}:</strong> {{ optional($purchaseOrder->delivery_deadline)->format('d M, Y') ?: '-' }}</p>
                    <p><strong>{{ translate('Status') }}:</strong> <span class="badge badge-inline badge-secondary">{{ ucfirst($purchaseOrder->status) }}</span></p>
                    <p class="mb-0"><strong>{{ translate('Delivery Address') }}:</strong> {{ $purchaseOrder->delivery_address ?: '-' }}</p>
                </div>
            </div>

            <div class="card rounded-0 shadow-none border">
                <div class="card-body">
                    @if (($canManagePurchaseOrder ?? false) && in_array($purchaseOrder->status, ['draft', 'sent']))
                        <form action="{{ route('b2b.purchase-orders.cancel', $purchaseOrder->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-soft-danger btn-block rounded-0">{{ translate('Cancel Purchase Order') }}</button>
                        </form>
                    @endif
                    @if (($canManagePurchaseOrder ?? false) && $purchaseOrder->status === 'accepted')
                        <form action="{{ route('b2b.purchase-orders.complete', $purchaseOrder->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block rounded-0">{{ translate('Mark Completed') }}</button>
                        </form>
                    @endif
                    @if (($canParticipateInNegotiation ?? false) && $purchaseOrder->negotiation)
                        <a href="{{ route('b2b.negotiations.show', $purchaseOrder->negotiation->id) }}" class="btn btn-soft-info btn-block rounded-0 mt-2">{{ translate('Open Negotiation') }}</a>
                    @endif
                    @if ($purchaseOrder->shipments->first())
                        <a href="{{ route('b2b.shipments.show', $purchaseOrder->shipments->sortByDesc('created_at')->first()->id) }}" class="btn btn-soft-info btn-block rounded-0 mt-2">{{ translate('Track Shipment') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
