<div class="card rounded-0 shadow-none border mb-4">
    <div class="card-header">{{ translate('Shipment Tracking Timeline') }}</div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="small text-muted">{{ translate('Carrier') }}</div>
                <div class="fw-600">{{ $shipment->shippingProvider?->name ?: '-' }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">{{ translate('Live Status') }}</div>
                <div class="fw-600">{{ $shipment->carrier_status ?: ucwords(str_replace('_', ' ', $shipment->status)) }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">{{ translate('Tracking Number') }}</div>
                <div class="fw-600">{{ $shipment->tracking_number ?: '-' }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">{{ translate('Last Tracked') }}</div>
                <div class="fw-600">{{ optional($shipment->last_tracked_at)->format('d M Y H:i') ?: '-' }}</div>
            </div>
        </div>

        @forelse ($shipment->events as $event)
            <div class="d-flex mb-3">
                <div class="pr-3">
                    <span class="badge badge-inline badge-soft-info">{{ ucwords(str_replace('_', ' ', $event->status)) }}</span>
                </div>
                <div>
                    <div class="fw-600">{{ $event->title ?: ucwords(str_replace('_', ' ', $event->status)) }}</div>
                    <div class="small text-muted">{{ optional($event->event_at)->format('d M Y H:i') }} @if($event->location) | {{ $event->location }} @endif</div>
                    @if ($event->description)
                        <div class="small">{{ $event->description }}</div>
                    @endif
                </div>
            </div>
        @empty
            <p class="mb-0 text-muted">{{ translate('No shipment updates recorded yet.') }}</p>
        @endforelse
    </div>
</div>
