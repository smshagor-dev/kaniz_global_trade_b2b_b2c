@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <h1 class="h3">{{ translate('Shipments') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Shipment No') }}</th>
                        <th>{{ translate('Reference') }}</th>
                        <th>{{ translate('Provider') }}</th>
                        <th>{{ translate('Tracking') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shipments as $shipment)
                        <tr>
                            <td>{{ $shipment->shipment_number }}</td>
                            <td>{{ $shipment->purchaseOrder?->po_number ?: ($shipment->sampleOrder?->sample_number ?: '-') }}</td>
                            <td>{{ $shipment->shippingProvider?->name ?: '-' }}</td>
                            <td>{{ $shipment->tracking_number ?: '-' }}</td>
                            <td>
                                <span class="badge badge-inline badge-secondary">{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span>
                                @if ($shipment->live_tracking_enabled)
                                    <div class="small text-muted mt-1">{{ $shipment->carrier_status ?: translate('Live tracking enabled') }}</div>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('seller.b2b.shipments.show', $shipment->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm"><i class="las la-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No shipments found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">{{ $shipments->links() }}</div>
        </div>
    </div>
@endsection
