@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4"><h1 class="h3">{{ translate('Freight Quote') }}: {{ $quote->quote_number }}</h1></div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>{{ translate('Buyer') }}:</strong> {{ $quote->buyerCompany?->company_name ?: '-' }}</p>
                    <p><strong>{{ translate('Forwarder') }}:</strong> {{ $quote->forwarder?->name ?: translate('Manual / Rule Based') }}</p>
                    <p><strong>{{ translate('Mode') }}:</strong> {{ ucwords(str_replace('_', ' ', $quote->freight_mode)) }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>{{ translate('Status') }}:</strong> {{ ucwords(str_replace('_', ' ', $quote->status)) }}</p>
                    <p><strong>{{ translate('Container') }}:</strong> {{ $quote->container_type ?: '-' }} x {{ $quote->container_count }}</p>
                    <p><strong>{{ translate('Landed Cost') }}:</strong> {{ single_price($quote->landed_cost_total) }} {{ $quote->base_currency ?: $quote->currency }}</p>
                </div>
            </div>
        </div>
    </div>
    @include('b2b.partials.freight_quote_costs', [
        'quote' => $quote,
        'editable' => true,
        'storeRoute' => route('seller.b2b.freight-quotes.cost-lines.store', $quote->id),
        'updateRoute' => fn ($line) => route('seller.b2b.freight-quotes.cost-lines.update', [$quote->id, $line->id]),
        'deleteRoute' => fn ($line) => route('seller.b2b.freight-quotes.cost-lines.delete', [$quote->id, $line->id]),
    ])
@endsection
