@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3">{{ translate('Sample Order') }}: {{ $sampleOrder->sample_number }}</h1>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('seller.b2b.sample-orders.index') }}" class="btn btn-soft-primary">{{ translate('Back') }}</a>
            </div>
        </div>
    </div>

    @include('b2b.partials.trade_timeline', ['timeline' => $timeline])

    <div class="row">
        <div class="col-lg-8">
            @include('b2b.partials.shipping_quotes_table', ['quotes' => $sampleOrder->shippingQuotes, 'allowSelect' => false])

            @if ($sampleOrder->shipment)
                @include('b2b.partials.shipment_timeline', ['shipment' => $sampleOrder->shipment])
            @endif

            @include('b2b.partials.trade_documents', ['documentable' => $sampleOrder, 'documentTypeKey' => 'sample-order', 'allowUpload' => true])
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">{{ translate('Summary') }}</div>
                <div class="card-body">
                    <p><strong>{{ translate('Buyer Company') }}:</strong> {{ $sampleOrder->buyerCompany?->company_name }}</p>
                    <p><strong>{{ translate('Product') }}:</strong> {{ $sampleOrder->product?->getTranslation('name') ?: '-' }}</p>
                    <p><strong>{{ translate('Quantity') }}:</strong> {{ $sampleOrder->quantity }} {{ $sampleOrder->unit }}</p>
                    <p><strong>{{ translate('Sample Price') }}:</strong> {{ number_format((float) $sampleOrder->sample_price, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Shipping') }}:</strong> {{ number_format((float) $sampleOrder->shipping_amount, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Processing Fee') }}:</strong> {{ number_format((float) $sampleOrder->sample_processing_fee_amount, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Buyer Payable Total') }}:</strong> {{ number_format((float) $sampleOrder->total_amount, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Status') }}:</strong> {{ ucwords(str_replace('_', ' ', $sampleOrder->status)) }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @if ($sampleOrder->status === 'requested')
                        <form action="{{ route('seller.b2b.sample-orders.accept', $sampleOrder->id) }}" method="POST" class="mb-3">
                            @csrf
                            <div class="form-group">
                                <label>{{ translate('Sample Price') }}</label>
                                <input type="number" step="0.01" name="sample_price" class="form-control" value="{{ $sampleOrder->sample_price }}">
                            </div>
                            <button type="submit" class="btn btn-success btn-block">{{ translate('Accept Sample Request') }}</button>
                        </form>
                        <form action="{{ route('seller.b2b.sample-orders.reject', $sampleOrder->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-soft-danger btn-block">{{ translate('Reject Sample Request') }}</button>
                        </form>
                    @endif

                    <a href="{{ route('seller.b2b.shipping-quotes.sample-orders.create', $sampleOrder->id) }}" class="btn btn-primary btn-block mt-2">{{ translate('Create Shipping Quote') }}</a>

                    @if (in_array($sampleOrder->status, ['paid', 'in_shipment', 'shipping_quoted'], true))
                        <a href="{{ route('seller.b2b.shipments.create', ['sample_order_id' => $sampleOrder->id]) }}" class="btn btn-soft-info btn-block mt-2">{{ translate('Create Shipment') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
