@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3">{{ translate('Shipment') }}: {{ $shipment->shipment_number }}</h1>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('seller.b2b.shipments.index') }}" class="btn btn-soft-primary">{{ translate('Back') }}</a>
            </div>
        </div>
    </div>

    @include('b2b.partials.shipment_timeline', ['shipment' => $shipment])

    <div class="row">
        <div class="col-lg-8">
            @include('b2b.partials.trade_documents', ['documentable' => $shipment, 'documentTypeKey' => 'shipment', 'allowUpload' => true])
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">{{ translate('Shipment Summary') }}</div>
                <div class="card-body">
                    <p><strong>{{ translate('Reference') }}:</strong> {{ $shipment->purchaseOrder?->po_number ?: ($shipment->sampleOrder?->sample_number ?: '-') }}</p>
                    <p><strong>{{ translate('Provider') }}:</strong> {{ $shipment->shippingProvider?->name ?: '-' }}</p>
                    <p><strong>{{ translate('Provider Type') }}:</strong> {{ ucfirst($shipment->shippingProvider?->provider_type ?: 'manual') }}</p>
                    <p><strong>{{ translate('Tracking Number') }}:</strong> {{ $shipment->tracking_number ?: '-' }}</p>
                    <p><strong>{{ translate('Carrier Reference') }}:</strong> {{ $shipment->carrier_reference ?: '-' }}</p>
                    <p><strong>{{ translate('Carrier Service') }}:</strong> {{ $shipment->carrier_service ?: '-' }}</p>
                    <p><strong>{{ translate('Tracking URL') }}:</strong>
                        @if ($shipment->tracking_url)
                            <a href="{{ $shipment->tracking_url }}" target="_blank">{{ translate('Open Tracking') }}</a>
                        @else
                            -
                        @endif
                    </p>
                    <p><strong>{{ translate('Live Tracking') }}:</strong> {{ $shipment->live_tracking_enabled ? translate('Enabled') : translate('Disabled') }}</p>
                    <p><strong>{{ translate('Live Status') }}:</strong> {{ $shipment->carrier_status ?: ucwords(str_replace('_', ' ', $shipment->status)) }}</p>
                    <p><strong>{{ translate('Last Tracked') }}:</strong> {{ optional($shipment->last_tracked_at)->format('d M Y H:i') ?: '-' }}</p>
                    @if ($shipment->sync_error)
                        <div class="alert alert-warning mb-0">{{ $shipment->sync_error }}</div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">{{ translate('Tracking Settings') }}</div>
                <div class="card-body">
                    <form action="{{ route('seller.b2b.shipments.tracking', $shipment->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>{{ translate('Shipping Provider') }}</label>
                            <select class="form-control aiz-selectpicker" name="shipping_provider_id" data-live-search="true">
                                <option value="">{{ translate('Select Provider') }}</option>
                                @foreach (\App\Models\B2BShippingProvider::where('is_active', true)->orderBy('name')->get() as $provider)
                                    <option value="{{ $provider->id }}" @selected($shipment->shipping_provider_id === $provider->id)>{{ $provider->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Tracking Number') }}</label>
                            <input type="text" class="form-control" name="tracking_number" value="{{ $shipment->tracking_number }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Carrier Reference') }}</label>
                            <input type="text" class="form-control" name="carrier_reference" value="{{ $shipment->carrier_reference }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Carrier Service') }}</label>
                            <input type="text" class="form-control" name="carrier_service" value="{{ $shipment->carrier_service }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Tracking URL') }}</label>
                            <input type="url" class="form-control" name="tracking_url" value="{{ $shipment->tracking_url }}">
                        </div>
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="live_tracking_enabled" value="1" @checked($shipment->live_tracking_enabled)>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Enable Live Tracking') }}</span>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">{{ translate('Save Tracking Settings') }}</button>
                    </form>

                    <form action="{{ route('seller.b2b.shipments.sync', $shipment->id) }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-soft-info btn-block">{{ translate('Sync Live Tracking Now') }}</button>
                    </form>
                </div>
            </div>

            @php
                $providerType = $shipment->shippingProvider?->provider_type ?? 'manual';
            @endphp
            @if ($providerType === 'manual' || !$shipment->shippingProvider)
                <div class="card">
                    <div class="card-header">{{ translate('Update Shipment Status') }}</div>
                    <div class="card-body">
                        <form action="{{ route('seller.b2b.shipments.status', $shipment->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>{{ translate('Status') }}</label>
                                <select class="form-control aiz-selectpicker" name="status" required>
                                    @foreach (\App\Services\B2BTradeService::SHIPMENT_STATUSES as $status)
                                        <option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Title') }}</label>
                                <input type="text" class="form-control" name="title">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Location') }}</label>
                                <input type="text" class="form-control" name="location">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Description') }}</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Event Time') }}</label>
                                <input type="datetime-local" class="form-control" name="event_at">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">{{ translate('Add Tracking Update') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
