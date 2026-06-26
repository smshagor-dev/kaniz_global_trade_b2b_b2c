@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Sample Orders') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Sample No') }}</th>
                        <th>{{ translate('Buyer') }}</th>
                        <th>{{ translate('Supplier') }}</th>
                        <th>{{ translate('Total') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sampleOrders as $sampleOrder)
                        <tr>
                            <td>{{ $sampleOrder->sample_number }}</td>
                            <td>{{ $sampleOrder->buyerCompany?->company_name }}</td>
                            <td>{{ $sampleOrder->supplierCompany?->company_name }}</td>
                            <td>{{ number_format((float) $sampleOrder->total_amount, 2) }} {{ $sampleOrder->currency }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucwords(str_replace('_', ' ', $sampleOrder->status)) }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('admin.b2b.sample-orders.show', $sampleOrder->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No sample orders found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">{{ $sampleOrders->links() }}</div>
        </div>
    </div>
@endsection
