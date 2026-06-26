@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4"><h1 class="fs-20 fw-700 text-dark">{{ translate('Freight Quote') }}: {{ $quote->quote_number }}</h1></div>
    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>{{ translate('Forwarder') }}:</strong> {{ $quote->forwarder?->name ?: translate('Manual / Rule Based') }}</p>
                    <p><strong>{{ translate('Route') }}:</strong> {{ $quote->origin_country }} / {{ $quote->destination_country }}</p>
                    <p><strong>{{ translate('Container') }}:</strong> {{ $quote->container_type ?: '-' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>{{ translate('HS Code') }}:</strong> {{ $quote->hs_code ?: '-' }}</p>
                    <p><strong>{{ translate('Status') }}:</strong> {{ ucwords(str_replace('_', ' ', $quote->status)) }}</p>
                    <p><strong>{{ translate('Landed Cost') }}:</strong> {{ single_price($quote->landed_cost_total) }} {{ $quote->base_currency ?: $quote->currency }}</p>
                </div>
            </div>
        </div>
    </div>
    @include('b2b.partials.freight_quote_costs', ['quote' => $quote, 'editable' => false])
@endsection
