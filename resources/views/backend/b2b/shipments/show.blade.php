@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Shipment') }}: {{ $shipment->shipment_number }}</h1>
    </div>

    @include('b2b.partials.shipment_timeline', ['shipment' => $shipment])
    @include('b2b.partials.trade_documents', ['documentable' => $shipment, 'documentTypeKey' => 'shipment', 'allowUpload' => false])

    <div class="card">
        <div class="card-header">{{ translate('Shipment Summary') }}</div>
        <div class="card-body">
            <p><strong>{{ translate('Buyer') }}:</strong> {{ $shipment->buyerCompany?->company_name }}</p>
            <p><strong>{{ translate('Supplier') }}:</strong> {{ $shipment->supplierCompany?->company_name }}</p>
            <p><strong>{{ translate('Provider') }}:</strong> {{ $shipment->shippingProvider?->name ?: '-' }}</p>
            <p><strong>{{ translate('Tracking') }}:</strong> {{ $shipment->tracking_number ?: '-' }}</p>
            <p><strong>{{ translate('Carrier Reference') }}:</strong> {{ $shipment->carrier_reference ?: '-' }}</p>
            <p><strong>{{ translate('Carrier Service') }}:</strong> {{ $shipment->carrier_service ?: '-' }}</p>
            <p><strong>{{ translate('Live Tracking') }}:</strong> {{ $shipment->live_tracking_enabled ? translate('Enabled') : translate('Disabled') }}</p>
            <p><strong>{{ translate('Carrier Status') }}:</strong> {{ $shipment->carrier_status ?: '-' }}</p>
            <p><strong>{{ translate('Last Tracked') }}:</strong> {{ optional($shipment->last_tracked_at)->format('d M Y H:i') ?: '-' }}</p>
            <p><strong>{{ translate('Status') }}:</strong> {{ ucwords(str_replace('_', ' ', $shipment->status)) }}</p>
            @if ($shipment->tracking_url)
                <p><strong>{{ translate('Tracking URL') }}:</strong> <a href="{{ $shipment->tracking_url }}" target="_blank">{{ $shipment->tracking_url }}</a></p>
            @endif
            @if ($shipment->sync_error)
                <div class="alert alert-warning">{{ $shipment->sync_error }}</div>
            @endif
            <form action="{{ route('admin.b2b.shipments.sync', $shipment->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">{{ translate('Sync Shipment Now') }}</button>
            </form>
        </div>
    </div>
@endsection
