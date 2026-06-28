@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Submit Quotation') }}</h1>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('RFQ Summary') }}</h5>
        </div>
        <div class="card-body">
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Buyer Company') }}</div><div class="col-md-9">{{ $rfq->company?->company_name }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Title') }}</div><div class="col-md-9">{{ $rfq->title }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Description') }}</div><div class="col-md-9">{{ $rfq->description }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Quantity') }}</div><div class="col-md-9">{{ $rfq->quantity }} {{ $rfq->unit }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Target Price') }}</div><div class="col-md-9">{{ $rfq->target_price ? $rfq->target_price . ' ' . $rfq->currency : '-' }}</div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('Quotation Details') }}</h5>
        </div>
        <div class="card-body">
            @php
                $quotationProductId = null;
                $price = null;
                $currency = null;
                $moq = null;
                $leadTimeDays = null;
                $shippingTerms = null;
                $incotermValue = $rfq->incoterm;
                $paymentTerms = null;
                $message = null;
                $attachment = null;
                $action = route('seller.b2b.rfqs.quote.store', $rfq->id);
                $submitLabel = translate('Submit Quotation');
            @endphp

            @include('seller.b2b.quotations._form')
        </div>
    </div>
@endsection
