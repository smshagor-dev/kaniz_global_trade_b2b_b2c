@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <h1 class="fs-20 fw-700 text-dark">{{ translate('Negotiations') }}</h1>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('RFQ') }}</th>
                        <th>{{ translate('Supplier') }}</th>
                        <th>{{ translate('PO') }}</th>
                        <th>{{ translate('Last Message') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($negotiations as $negotiation)
                        <tr>
                            <td>{{ $negotiation->rfq?->title ?: '-' }}</td>
                            <td>{{ $negotiation->supplierCompany?->company_name }}</td>
                            <td>{{ $negotiation->purchaseOrder?->po_number ?: '-' }}</td>
                            <td>{{ optional($negotiation->last_message_at)->diffForHumans() ?: '-' }}</td>
                            <td class="text-right">
                                <a href="{{ route('b2b.negotiations.show', $negotiation->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm">
                                    <i class="las la-comments"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">{{ translate('No negotiations found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">{{ $negotiations->links() }}</div>
        </div>
    </div>
@endsection
