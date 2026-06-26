@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Sample Orders') }}</h1>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Sample No') }}</th>
                        <th>{{ translate('Supplier') }}</th>
                        <th>{{ translate('Product') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Total') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sampleOrders as $sampleOrder)
                        <tr>
                            <td>{{ $sampleOrder->sample_number }}</td>
                            <td>{{ $sampleOrder->supplierCompany?->company_name }}</td>
                            <td>{{ $sampleOrder->product?->getTranslation('name') ?: '-' }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucwords(str_replace('_', ' ', $sampleOrder->status)) }}</span></td>
                            <td>{{ number_format((float) $sampleOrder->total_amount, 2) }} {{ $sampleOrder->currency }}</td>
                            <td class="text-right">
                                <a href="{{ route('b2b.sample-orders.show', $sampleOrder->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm">
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
