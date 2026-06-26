@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3"><h1 class="h3">{{ translate('Freight Quote') }}: {{ $quote->quote_number }}</h1></div>
    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-header">{{ translate('Quote Summary') }}</div><div class="card-body">
                <p><strong>{{ translate('Forwarder') }}:</strong> {{ $quote->forwarder?->name ?: translate('Manual / Rule Based') }}</p>
                <p><strong>{{ translate('Route') }}:</strong> {{ $quote->origin_country }} / {{ $quote->destination_country }}</p>
                <p><strong>{{ translate('Mode') }}:</strong> {{ ucwords(str_replace('_', ' ', $quote->freight_mode)) }}</p>
                <p><strong>{{ translate('Service') }}:</strong> {{ ucwords(str_replace('_', ' ', $quote->service_type)) }}</p>
                <p><strong>{{ translate('Container') }}:</strong> {{ $quote->container_type ?: '-' }} x {{ $quote->container_count }}</p>
                <p><strong>{{ translate('HS Code') }}:</strong> {{ $quote->hs_code ?: '-' }}</p>
                <p><strong>{{ translate('Pricing Rule') }}:</strong> {{ $quote->pricingRule?->name ?: '-' }}</p>
                <p><strong>{{ translate('Landed Cost') }}:</strong> {{ single_price($quote->landed_cost_total) }} {{ $quote->base_currency ?: $quote->currency }}</p>
            </div></div>
        </div>
        <div class="col-lg-8">
            @include('b2b.partials.freight_quote_costs', [
                'quote' => $quote,
                'editable' => true,
                'storeRoute' => route('admin.b2b.freight-quotes.cost-lines.store', $quote->id),
                'updateRoute' => fn ($line) => route('admin.b2b.freight-quotes.cost-lines.update', [$quote->id, $line->id]),
                'deleteRoute' => fn ($line) => route('admin.b2b.freight-quotes.cost-lines.delete', [$quote->id, $line->id]),
            ])
        </div>
    </div>
@endsection
