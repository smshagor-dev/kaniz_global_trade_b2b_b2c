@extends('b2b.layouts.supplier')

@section('panel_content')
    @php
        $permissionService = app(\App\Services\B2BPermissionService::class);
        $canManageFreight = $permissionService->canManageFreight(auth()->id(), $quote->supplier_company_id);
        $canApproveFreightCosts = $permissionService->canApproveFreightCosts(auth()->id(), $quote->supplier_company_id);
        $canEditCosts = $canManageFreight || $canApproveFreightCosts;
        $canBookContainer = $canManageFreight && $quote->freight_mode === 'sea_freight' && $quote->status === 'selected';
    @endphp

    <div class="aiz-titlebar mt-2 mb-4"><h1 class="h3">{{ translate('Freight Quote') }}: {{ $quote->quote_number }}</h1></div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>{{ translate('Buyer') }}:</strong> {{ $quote->buyerCompany?->company_name ?: '-' }}</p>
                    <p><strong>{{ translate('Forwarder') }}:</strong> {{ $quote->forwarder?->name ?: translate('Manual / Rule Based') }}</p>
                    <p><strong>{{ translate('Mode') }}:</strong> {{ ucwords(str_replace('_', ' ', $quote->freight_mode)) }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>{{ translate('Status') }}:</strong> {{ ucwords(str_replace('_', ' ', $quote->status)) }}</p>
                    <p><strong>{{ translate('Container') }}:</strong> {{ $quote->container_type ?: '-' }} x {{ $quote->container_count }}</p>
                    <p><strong>{{ translate('Landed Cost') }}:</strong> {{ single_price($quote->landed_cost_total) }} {{ $quote->base_currency ?: $quote->currency }}</p>
                </div>
            </div>
        </div>
    </div>
    @if ($canBookContainer)
        <form action="{{ route('seller.b2b.container-shipments.store', $quote->id) }}" method="POST" class="mb-4">
            @csrf
            <button type="submit" class="btn btn-primary">{{ translate('Create Container Booking') }}</button>
        </form>
    @endif
    @include('b2b.partials.freight_quote_costs', [
        'quote' => $quote,
        'editable' => $canEditCosts,
        'storeRoute' => route('seller.b2b.freight-quotes.cost-lines.store', $quote->id),
        'updateRoute' => fn ($line) => route('seller.b2b.freight-quotes.cost-lines.update', [$quote->id, $line->id]),
        'deleteRoute' => fn ($line) => route('seller.b2b.freight-quotes.cost-lines.delete', [$quote->id, $line->id]),
    ])
@endsection
