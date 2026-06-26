@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Shipments') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Shipment No') }}</th>
                        <th>{{ translate('Buyer') }}</th>
                        <th>{{ translate('Supplier') }}</th>
                        <th>{{ translate('Carrier') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shipments as $shipment)
                        <tr>
                            <td>{{ $shipment->shipment_number }}</td>
                            <td>{{ $shipment->buyerCompany?->company_name }}</td>
                            <td>{{ $shipment->supplierCompany?->company_name }}</td>
                            <td>{{ $shipment->shippingProvider?->name ?: '-' }}</td>
                            <td>
                                <span class="badge badge-inline badge-secondary">{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span>
                                @if ($shipment->sync_error)
                                    <div class="small text-danger mt-1">{{ translate('Sync error recorded') }}</div>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.b2b.shipments.show', $shipment->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm"><i class="las la-eye"></i></a>
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
