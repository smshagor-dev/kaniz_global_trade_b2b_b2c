@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Edit Quotation') }}</h1>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('RFQ Summary') }}</h5>
        </div>
        <div class="card-body">
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Buyer Company') }}</div><div class="col-md-9">{{ $quotation->rfq?->company?->company_name }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Title') }}</div><div class="col-md-9">{{ $quotation->rfq?->title }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Description') }}</div><div class="col-md-9">{{ $quotation->rfq?->description }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Quantity') }}</div><div class="col-md-9">{{ $quotation->rfq?->quantity }} {{ $quotation->rfq?->unit }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Status') }}</div><div class="col-md-9"><span class="badge badge-inline badge-secondary">{{ ucfirst($quotation->status) }}</span></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('Quotation Details') }}</h5>
        </div>
        <div class="card-body">
            @php
                $rfq = $quotation->rfq;
                $quotationProductId = $quotation->product_id;
                $price = $quotation->price;
                $currency = $quotation->currency;
                $moq = $quotation->moq;
                $leadTimeDays = $quotation->lead_time_days;
                $shippingTerms = $quotation->shipping_terms;
                $incotermValue = $quotation->incoterm;
                $paymentTerms = $quotation->payment_terms;
                $message = $quotation->message;
                $attachment = $quotation->attachment;
                $action = route('seller.b2b.quotations.update', $quotation->id);
                $submitLabel = translate('Update Quotation');
            @endphp

            @include('seller.b2b.quotations._form')
        </div>
    </div>
@endsection
