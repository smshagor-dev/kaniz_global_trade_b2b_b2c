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
                $readOnlyQuote = !empty($existingQuotation);
                $quotationProductId = $existingQuotation->product_id ?? null;
                $price = $existingQuotation->price ?? null;
                $currency = $existingQuotation->currency ?? null;
                $moq = $existingQuotation->moq ?? null;
                $leadTimeDays = $existingQuotation->lead_time_days ?? null;
                $shippingTerms = $existingQuotation->shipping_terms ?? null;
                $incotermValue = $existingQuotation->incoterm ?? $rfq->incoterm;
                $paymentTerms = $existingQuotation->payment_terms ?? null;
                $message = $existingQuotation->message ?? null;
                $attachment = $existingQuotation->attachment ?? null;
                $action = route('seller.b2b.rfqs.quote.store', $rfq->id);
                $submitLabel = translate('Submit Quotation');
            @endphp

            @if ($readOnlyQuote)
                <div class="alert alert-info">
                    {{ translate('You have already submitted a quotation for this RFQ. You can review it here, but cannot submit a new quotation.') }}
                    <a href="{{ route('seller.b2b.quotations.show', $existingQuotation->id) }}" class="btn btn-soft-info btn-sm ml-2">{{ translate('Open Quotation') }}</a>
                    @if ($existingQuotation->negotiation)
                    <a href="{{ route('seller.b2b.negotiations.show', $existingQuotation->negotiation->id) }}" class="btn btn-soft-success btn-sm ml-2">{{ translate('Open Conversation') }}</a>
                    @endif
                    @if ($existingQuotation->status === 'pending')
                        <a href="{{ route('seller.b2b.quotations.edit', $existingQuotation->id) }}" class="btn btn-soft-primary btn-sm ml-2">{{ translate('Edit Existing Quote') }}</a>
                    @endif
                </div>
            @endif

            @include('seller.b2b.quotations._form')
        </div>
    </div>
@endsection
