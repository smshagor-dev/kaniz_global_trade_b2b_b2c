@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <h1 class="h3">{{ translate('Create Shipment') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('seller.b2b.shipments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder?->id }}">
                <input type="hidden" name="sample_order_id" value="{{ $sampleOrder?->id }}">
                <input type="hidden" name="proforma_invoice_id" value="{{ $proformaInvoice?->id }}">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Reference') }}</label>
                        <input type="text" class="form-control" value="{{ $purchaseOrder?->po_number ?: ($sampleOrder?->sample_number ?: ($proformaInvoice?->invoice_number ?: '-')) }}" disabled>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Shipping Quote') }}</label>
                        <select class="form-control aiz-selectpicker" name="shipping_quote_id" data-live-search="true">
                            <option value="">{{ translate('Select Quote') }}</option>
                            @foreach ($shippingQuotes as $quote)
                                <option value="{{ $quote->id }}">{{ $quote->quote_number }} - {{ $quote->shippingProvider?->name ?: translate('Direct Supplier Quote') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Shipping Provider') }}</label>
                        <select class="form-control aiz-selectpicker" name="shipping_provider_id">
                            <option value="">{{ translate('Select Provider') }}</option>
                            @foreach ($providers as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Transport Mode') }}</label>
                        <select class="form-control aiz-selectpicker" name="transport_mode" required>
                            @foreach ($transportModes as $mode)
                                <option value="{{ $mode }}">{{ ucwords(str_replace('_', ' ', $mode)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Incoterm') }}</label>
                        <select class="form-control aiz-selectpicker" name="incoterm" required>
                            @foreach ($incoterms as $incoterm)
                                <option value="{{ $incoterm }}">{{ $incoterm }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Tracking Number') }}</label>
                        <input type="text" class="form-control" name="tracking_number">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Carrier Reference') }}</label>
                        <input type="text" class="form-control" name="carrier_reference">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Carrier Service') }}</label>
                        <input type="text" class="form-control" name="carrier_service">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Tracking URL') }}</label>
                        <input type="url" class="form-control" name="tracking_url">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Origin Country') }}</label>
                        <input type="text" class="form-control" name="origin_country">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Destination Country') }}</label>
                        <input type="text" class="form-control" name="destination_country">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Estimated Departure') }}</label>
                        <input type="date" class="form-control" name="estimated_departure">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Estimated Arrival') }}</label>
                        <input type="date" class="form-control" name="estimated_arrival">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ translate('Notes') }}</label>
                        <textarea class="form-control" name="notes" rows="4"></textarea>
                    </div>
                    <div class="col-md-12 form-group">
                        <label class="aiz-checkbox">
                            <input type="checkbox" name="live_tracking_enabled" value="1">
                            <span class="aiz-square-check"></span>
                            <span>{{ translate('Enable Live Tracking') }}</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ translate('Create Shipment') }}</button>
            </form>
        </div>
    </div>
@endsection
