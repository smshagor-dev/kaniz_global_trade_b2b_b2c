@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4"><h1 class="fs-20 fw-700 text-dark">{{ translate('Freight Recommendation AI') }}</h1></div>
    <div class="card rounded-0 shadow-none border mb-4"><div class="card-body">
        <form method="POST" action="{{ route('b2b.ai.freight-recommendation') }}">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>{{ translate('Freight Quote') }}</label>
                    <select class="form-control aiz-selectpicker" data-live-search="true" name="freight_quote_id">
                        <option value="">{{ translate('Select Quote') }}</option>
                        @foreach($freightQuotes as $quote)
                            <option value="{{ $quote->id }}">{{ $quote->quote_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>{{ translate('Shipment') }}</label>
                    <select class="form-control aiz-selectpicker" data-live-search="true" name="shipment_id">
                        <option value="">{{ translate('Select Shipment') }}</option>
                        @foreach($shipments as $shipment)
                            <option value="{{ $shipment->id }}">{{ $shipment->shipment_number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary rounded-0">{{ translate('Generate Freight Recommendation') }}</button>
        </form>
    </div></div>
    @if ($recommendation)
        <div class="card rounded-0 shadow-none border mb-4"><div class="card-body">
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Mode / Strategy') }}</div><div class="col-md-9">{{ $recommendation->recommended_mode }} / {{ $recommendation->recommended_strategy }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Forwarder') }}</div><div class="col-md-9">{{ $recommendation->recommended_forwarder_name ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Delivery / Customs') }}</div><div class="col-md-9">{{ $recommendation->estimated_delivery_days }} {{ translate('days') }} / {{ $recommendation->estimated_customs_delay_days }} {{ translate('days') }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Cost Saving / Carbon') }}</div><div class="col-md-9">{{ $recommendation->cost_saving_estimate }} / {{ $recommendation->carbon_estimate }}</div></div>
            <div class="row"><div class="col-md-3 text-secondary">{{ translate('Explanation') }}</div><div class="col-md-9">{{ $recommendation->explanation }}</div></div>
        </div></div>
    @endif
    @include('b2b.ai.partials.history_table', ['title' => translate('Freight Recommendation History'), 'records' => $history, 'columns' => ['created_at' => 'Date', 'recommended_mode' => 'Mode', 'recommended_forwarder_name' => 'Forwarder', 'estimated_shipping_cost' => 'Cost', 'confidence_score' => 'Confidence']])
@endsection
