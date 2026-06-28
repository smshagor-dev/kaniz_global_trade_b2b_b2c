@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Purchase Orders') }}</h1>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('PO Number') }}</th>
                        <th>{{ translate('Supplier') }}</th>
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
                            <td>{{ $purchaseOrder->supplierCompany?->company_name }}</td>
                            <td>{{ $purchaseOrder->rfq?->title }}</td>
                            <td>{{ $purchaseOrder->total_amount }} {{ $purchaseOrder->currency }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($purchaseOrder->status) }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('b2b.purchase-orders.show', $purchaseOrder->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No purchase orders found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">{{ $purchaseOrders->links() }}</div>
        </div>
    </div>
@endsection
