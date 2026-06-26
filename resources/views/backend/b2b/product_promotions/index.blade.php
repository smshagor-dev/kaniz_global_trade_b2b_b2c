@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h3 class="h3">{{ translate('Sponsored Product Packages') }}</h3>
            <p class="text-muted mb-0">{{ translate('Create separate paid plans suppliers can buy to promote wholesale products.') }}</p>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('admin.b2b.product-promotions.requests') }}" class="btn btn-soft-info mr-2">{{ translate('Promotion Package Requests') }}</a>
            <a href="{{ route('admin.b2b.product-promotions.create') }}" class="btn btn-info">{{ translate('Add Sponsored Product Package') }}</a>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">{{ translate('Revenue Projection') }}</h5>
        <div class="fs-16 text-muted">
            {{ $projection['plan_name'] ?: translate('Sponsored Product') }}:
            <strong>{{ single_price($projection['monthly_unit_price'] ?? 0) }}/{{ translate('month') }}</strong>
        </div>
        <div class="fs-20 fw-700 mt-2">
            {{ $projection['product_count'] ?? 0 }} x {{ single_price($projection['monthly_unit_price'] ?? 0) }} = {{ single_price($projection['projected_monthly_revenue'] ?? 0) }}/{{ translate('month') }}
        </div>
    </div>
</div>

<div class="row">
    @foreach ($packages as $package)
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge badge-inline badge-warning">{{ translate('Sponsored Product') }}</span>
                        @if ($package->highlight_text)
                            <span class="badge badge-inline badge-soft-primary">{{ $package->highlight_text }}</span>
                        @endif
                    </div>
                    <div class="text-center mb-3">
                        @if ($package->logo)
                            <img src="{{ uploaded_asset($package->logo) }}" height="72" class="mw-100 mx-auto mb-3">
                        @endif
                        <h5 class="mb-1">{{ $package->name }}</h5>
                        <div class="fs-20 fw-700">{{ single_price($package->amount) }}</div>
                        <small class="text-muted">{{ $package->duration }} {{ translate('days') }}</small>
                    </div>
                    <div class="mb-3 text-muted">{{ $package->description ?: translate('Separate sponsored product promotion package.') }}</div>
                    <ul class="list-unstyled fs-13 mb-4">
                        <li>{{ translate('Sponsored Product Limit') }}: {{ number_format($package->product_limit) }}</li>
                        <li>{{ translate('Monthly Revenue Per Package') }}: {{ single_price($package->monthlyEquivalent()) }}</li>
                        <li>{{ translate('Per Product Rate') }}: {{ single_price(round($package->monthlyEquivalent() / max($package->product_limit, 1), 2)) }}/{{ translate('month') }}</li>
                    </ul>
                    <div class="d-flex flex-wrap">
                        <a href="{{ route('admin.b2b.product-promotions.edit', $package->id) }}" class="btn btn-soft-primary btn-sm mr-2 mb-2">{{ translate('Edit') }}</a>
                        <form action="{{ route('admin.b2b.product-promotions.delete', $package->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-soft-danger btn-sm">{{ translate('Delete') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
