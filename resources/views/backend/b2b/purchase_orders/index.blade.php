@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('B2B Purchase Orders') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('PO Number') }}</th>
                        <th>{{ translate('Buyer Company') }}</th>
                        <th>{{ translate('Supplier Company') }}</th>
                        <th>{{ translate('RFQ') }}</th>
                        <th>{{ translate('Total') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseOrders as $purchaseOrder)
                        <tr>
                            <td>{{ $purchaseOrder->po_number }}</td>
                            <td>{{ $purchaseOrder->buyerCompany?->company_name }}</td>
                            <td>{{ $purchaseOrder->supplierCompany?->company_name }}</td>
                            <td>{{ $purchaseOrder->rfq?->title }}</td>
                            <td>{{ $purchaseOrder->total_amount }} {{ $purchaseOrder->currency }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($purchaseOrder->status) }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('admin.b2b.purchase-orders.show', $purchaseOrder->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ translate('No purchase orders found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">{{ $purchaseOrders->links() }}</div>
        </div>
    </div>
@endsection
