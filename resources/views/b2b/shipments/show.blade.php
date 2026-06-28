@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Shipment') }}: {{ $shipment->shipment_number }}</h1>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('b2b.shipments.index') }}" class="btn btn-soft-primary rounded-0">{{ translate('Back') }}</a>
            </div>
        </div>
    </div>

    @include('b2b.partials.shipment_timeline', ['shipment' => $shipment])
    @include('b2b.partials.trade_documents', ['documentable' => $shipment, 'documentTypeKey' => 'shipment', 'allowUpload' => false])

    <div class="card rounded-0 shadow-none border">
        <div class="card-header">{{ translate('Shipment Summary') }}</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>{{ translate('Provider') }}:</strong> {{ $shipment->shippingProvider?->name ?: '-' }}</p>
                    <p><strong>{{ translate('Provider Type') }}:</strong> {{ ucfirst($shipment->shippingProvider?->provider_type ?: 'manual') }}</p>
                    <p><strong>{{ translate('Mode') }}:</strong> {{ ucwords(str_replace('_', ' ', $shipment->transport_mode)) }}</p>
                    <p><strong>{{ translate('Incoterm') }}:</strong> {{ $shipment->incoterm ?: '-' }}</p>
                    <p><strong>{{ translate('Carrier Service') }}:</strong> {{ $shipment->carrier_service ?: '-' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>{{ translate('Tracking Number') }}:</strong> {{ $shipment->tracking_number ?: '-' }}</p>
                    <p><strong>{{ translate('Tracking URL') }}:</strong>
                        @if ($shipment->tracking_url)
                            <a href="{{ $shipment->tracking_url }}" target="_blank">{{ translate('Open Tracking') }}</a>
                        @else
                            -
                        @endif
                    </p>
                    <p><strong>{{ translate('Origin / Destination') }}:</strong> {{ $shipment->origin_country ?: '-' }} / {{ $shipment->destination_country ?: '-' }}</p>
                    <p><strong>{{ translate('Status') }}:</strong> {{ ucwords(str_replace('_', ' ', $shipment->status)) }}</p>
                    <p><strong>{{ translate('Live Carrier Status') }}:</strong> {{ $shipment->carrier_status ?: '-' }}</p>
                    <p><strong>{{ translate('Last Tracked') }}:</strong> {{ optional($shipment->last_tracked_at)->format('d M Y H:i') ?: '-' }}</p>
                </div>
            </div>
            @if ($shipment->sync_error)
                <div class="alert alert-warning mt-3 mb-0">{{ $shipment->sync_error }}</div>
            @endif
        </div>
    </div>
@endsection
