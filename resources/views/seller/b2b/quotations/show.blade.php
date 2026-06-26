@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Quotation Details') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                @if ($quotation->status === 'pending')
                    <a href="{{ route('seller.b2b.quotations.edit', $quotation->id) }}" class="btn btn-soft-primary rounded-0">{{ translate('Edit Quotation') }}</a>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('RFQ') }}</div><div class="col-md-9">{{ $quotation->rfq?->title }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Buyer Company') }}</div><div class="col-md-9">{{ $quotation->rfq?->company?->company_name }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Supplier Company') }}</div><div class="col-md-9">{{ $quotation->supplierCompany?->company_name }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Offer Product') }}</div><div class="col-md-9">{{ $quotation->product?->getTranslation('name') ?? '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Price') }}</div><div class="col-md-9">{{ $quotation->price }} {{ $quotation->currency }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('MOQ') }}</div><div class="col-md-9">{{ $quotation->moq ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Lead Time Days') }}</div><div class="col-md-9">{{ $quotation->lead_time_days ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Shipping Terms') }}</div><div class="col-md-9">{{ $quotation->shipping_terms ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Incoterm') }}</div><div class="col-md-9">{{ $quotation->incoterm ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Payment Terms') }}</div><div class="col-md-9">{{ $quotation->payment_terms ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Message') }}</div><div class="col-md-9">{{ $quotation->message ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Attachment') }}</div><div class="col-md-9">@if($quotation->attachment)<a href="{{ asset($quotation->attachment) }}" target="_blank">{{ translate('View attachment') }}</a>@else - @endif</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Status') }}</div><div class="col-md-9"><span class="badge badge-inline badge-secondary">{{ ucfirst($quotation->status) }}</span></div></div>
        </div>
    </div>
@endsection
