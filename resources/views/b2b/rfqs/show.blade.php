@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('RFQ Details') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                @if (($canCreateRfq ?? false) && !in_array($rfq->status, ['closed', 'cancelled']))
                    <a href="{{ route('b2b.rfqs.edit', $rfq->id) }}" class="btn btn-soft-primary rounded-0 mr-2">{{ translate('Edit RFQ') }}</a>
                    <form action="{{ route('b2b.rfqs.cancel', $rfq->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-soft-danger rounded-0">{{ translate('Cancel RFQ') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-body">
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Title') }}</div><div class="col-md-9">{{ $rfq->title }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Product') }}</div><div class="col-md-9">{{ $rfq->product?->getTranslation('name') ?? '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Category') }}</div><div class="col-md-9">{{ $rfq->category?->getTranslation('name') ?? '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Description') }}</div><div class="col-md-9">{{ $rfq->description }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Quantity') }}</div><div class="col-md-9">{{ $rfq->quantity }} {{ $rfq->unit }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Target Price') }}</div><div class="col-md-9">{{ $rfq->target_price ? $rfq->target_price . ' ' . $rfq->currency : '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Incoterm') }}</div><div class="col-md-9">{{ $rfq->incoterm ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Destination') }}</div><div class="col-md-9">{{ $rfq->destination_city ?: '-' }}{{ $rfq->destination_country ? ', ' . $rfq->destination_country : '' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Expected Delivery') }}</div><div class="col-md-9">{{ optional($rfq->expected_delivery_date)->format('Y-m-d') ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Expires At') }}</div><div class="col-md-9">{{ optional($rfq->expires_at)->format('Y-m-d H:i') ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Status') }}</div><div class="col-md-9"><span class="badge badge-inline badge-secondary">{{ ucfirst($rfq->status) }}</span></div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Attachment') }}</div><div class="col-md-9">@if($rfq->attachment)<a href="{{ asset($rfq->attachment) }}" target="_blank">{{ translate('View attachment') }}</a>@else - @endif</div></div>
        </div>
    </div>

    @if (Route::has('b2b.ai.rfqs.supplier-matches'))
        <div class="card rounded-0 shadow-none border mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ translate('AI Insights') }}</h5>
                <div>
                    <a href="{{ route('b2b.ai.rfqs.supplier-matches', $rfq->id) }}" class="btn btn-soft-primary btn-sm">{{ translate('Supplier Matches') }}</a>
                    <a href="{{ route('b2b.ai.summary', ['type' => 'rfq', 'id' => $rfq->id]) }}" class="btn btn-soft-info btn-sm">{{ translate('Summarize RFQ') }}</a>
                </div>
            </div>
            <div class="card-body">
                <p class="mb-0 text-muted">{{ translate('Review ranked supplier matches and generate a permission-aware RFQ summary without changing the current quotation workflow.') }}</p>
            </div>
        </div>
    @endif

    <div class="card rounded-0 shadow-none border">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('Supplier Quotations') }}</h5>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Supplier') }}</th>
                        <th>{{ translate('Price') }}</th>
                        <th>{{ translate('MOQ') }}</th>
                        <th>{{ translate('Lead Time') }}</th>
                        <th>{{ translate('Shipping Terms') }}</th>
                        <th>{{ translate('Incoterm') }}</th>
                        <th>{{ translate('Payment Terms') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rfq->quotations as $quotation)
                        <tr>
                            <td>
                                <div>{{ $quotation->supplierCompany?->company_name }}</div>
                                <small class="text-muted">{{ $quotation->supplier?->name }}</small>
                            </td>
                            <td>{{ $quotation->price }} {{ $quotation->currency }}</td>
                            <td>{{ $quotation->moq ?: '-' }}</td>
                            <td>{{ $quotation->lead_time_days ? $quotation->lead_time_days . ' ' . translate('days') : '-' }}</td>
                            <td>{{ $quotation->shipping_terms ?: '-' }}</td>
                            <td>{{ $quotation->incoterm ?: '-' }}</td>
                            <td>{{ $quotation->payment_terms ?: '-' }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($quotation->status) }}</span></td>
                            <td class="text-right">
                                @if ($quotation->message)
                                    <span class="d-inline-block text-left small text-muted mr-2">{{ \Illuminate\Support\Str::limit($quotation->message, 60) }}</span>
                                @endif
                                @if ($quotation->attachment)
                                    <a href="{{ asset($quotation->attachment) }}" target="_blank" class="btn btn-soft-info btn-sm">{{ translate('Attachment') }}</a>
                                @endif
                                @if ($quotation->negotiation)
                                    <a href="{{ route('b2b.negotiations.show', $quotation->negotiation->id) }}" class="btn btn-soft-info btn-sm">{{ translate('Open Conversation') }}</a>
                                @endif
                                @if (($canManagePurchaseOrder ?? false) && $quotation->status === 'pending' && !in_array($rfq->status, ['closed', 'cancelled']))
                                    <form action="{{ route('b2b.quotations.accept', $quotation->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">{{ translate('Accept') }}</button>
                                    </form>
                                    <form action="{{ route('b2b.quotations.reject', $quotation->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">{{ translate('Reject') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">{{ translate('No quotations received yet') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
