@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Sample Order') }}: {{ $sampleOrder->sample_number }}</h1>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('b2b.sample-orders.index') }}" class="btn btn-soft-primary rounded-0">{{ translate('Back') }}</a>
            </div>
        </div>
    </div>

    @include('b2b.partials.trade_timeline', ['timeline' => $timeline])
    @include('b2b.partials.shipping_quotes_table', ['quotes' => $sampleOrder->shippingQuotes, 'allowSelect' => true])

    <div class="row gutters-16">
        <div class="col-lg-8">
            @if ($sampleOrder->shipment)
                @include('b2b.partials.shipment_timeline', ['shipment' => $sampleOrder->shipment])
            @endif
            @include('b2b.partials.trade_documents', ['documentable' => $sampleOrder, 'documentTypeKey' => 'sample-order', 'allowUpload' => false])
        </div>
        <div class="col-lg-4">
            <div class="card rounded-0 shadow-none border mb-4">
                <div class="card-header">{{ translate('Summary') }}</div>
                <div class="card-body">
                    <p><strong>{{ translate('Supplier') }}:</strong> {{ $sampleOrder->supplierCompany?->company_name }}</p>
                    <p><strong>{{ translate('Product') }}:</strong> {{ $sampleOrder->product?->getTranslation('name') ?: '-' }}</p>
                    <p><strong>{{ translate('Quantity') }}:</strong> {{ $sampleOrder->quantity }} {{ $sampleOrder->unit }}</p>
                    <p><strong>{{ translate('Sample Price') }}:</strong> {{ number_format((float) $sampleOrder->sample_price, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Shipping') }}:</strong> {{ number_format((float) $sampleOrder->shipping_amount, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Processing Fee') }}:</strong> {{ number_format((float) $sampleOrder->sample_processing_fee_amount, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Total') }}:</strong> {{ number_format((float) $sampleOrder->total_amount, 2) }} {{ $sampleOrder->currency }}</p>
                    <p class="mb-0"><strong>{{ translate('Status') }}:</strong> {{ ucwords(str_replace('_', ' ', $sampleOrder->status)) }}</p>
                </div>
            </div>

            @if ($sampleOrder->status === 'payment_pending')
                <div class="card rounded-0 shadow-none border">
                    <div class="card-body">
                        <form action="{{ route('b2b.sample-orders.pay', $sampleOrder->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>{{ translate('Payment Reference') }}</label>
                                <input type="text" class="form-control" name="payment_reference" required>
                            </div>
                            <button type="submit" class="btn btn-success btn-block rounded-0">{{ translate('Confirm Payment') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
