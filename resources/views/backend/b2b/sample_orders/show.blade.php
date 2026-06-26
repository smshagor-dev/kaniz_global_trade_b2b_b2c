@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Sample Order') }}: {{ $sampleOrder->sample_number }}</h1>
    </div>

    @include('b2b.partials.trade_timeline', ['timeline' => $timeline])
    @include('b2b.partials.shipping_quotes_table', ['quotes' => $sampleOrder->shippingQuotes, 'allowSelect' => false])

    <div class="row">
        <div class="col-lg-8">
            @if ($sampleOrder->shipment)
                @include('b2b.partials.shipment_timeline', ['shipment' => $sampleOrder->shipment])
            @endif
            @include('b2b.partials.trade_documents', ['documentable' => $sampleOrder, 'documentTypeKey' => 'sample-order', 'allowUpload' => false])
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">{{ translate('Summary') }}</div>
                <div class="card-body">
                    <p><strong>{{ translate('Buyer') }}:</strong> {{ $sampleOrder->buyerCompany?->company_name }}</p>
                    <p><strong>{{ translate('Supplier') }}:</strong> {{ $sampleOrder->supplierCompany?->company_name }}</p>
                    <p><strong>{{ translate('Sample Price') }}:</strong> {{ number_format((float) $sampleOrder->sample_price, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Shipping') }}:</strong> {{ number_format((float) $sampleOrder->shipping_amount, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Processing Fee') }}:</strong> {{ number_format((float) $sampleOrder->sample_processing_fee_amount, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Buyer Payable Total') }}:</strong> {{ number_format((float) $sampleOrder->total_amount, 2) }} {{ $sampleOrder->currency }}</p>
                    <p><strong>{{ translate('Status') }}:</strong> {{ ucwords(str_replace('_', ' ', $sampleOrder->status)) }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
