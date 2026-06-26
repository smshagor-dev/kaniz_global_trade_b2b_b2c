@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h3 class="h3">{{ $pageTitle ?? translate('B2B Packages') }}</h3>
            @if (!empty($pageDescription))
                <p class="text-muted mb-0">{{ $pageDescription }}</p>
            @endif
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route($requestsRoute ?? 'admin.b2b.package-requests.index') }}" class="btn btn-soft-info mr-2">{{ $requestsButtonLabel ?? translate('Package Requests') }}</a>
            <a href="{{ route($createRoute ?? 'admin.b2b.packages.create') }}" class="btn btn-info">
                {{ !empty($forceSupplierFeaturedPackage) ? translate('Add Supplier Featured Package') : translate('Add New Package') }}
            </a>
        </div>
    </div>
</div>

@if (!empty($forceSupplierFeaturedPackage))
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-2">{{ translate('Revenue Projection') }}</h5>
            <div class="fs-16 text-muted">
                {{ $featuredProjection['plan_name'] ?? translate('Featured Supplier') }}:
                <strong>{{ single_price($featuredProjection['monthly_price'] ?? 0) }}/{{ translate('month') }}</strong>
            </div>
            <div class="fs-20 fw-700 mt-2">
                {{ $featuredProjection['company_count'] ?? 0 }} x {{ single_price($featuredProjection['monthly_price'] ?? 0) }} = {{ single_price($featuredProjection['projected_monthly_revenue'] ?? 0) }}/{{ translate('month') }}
            </div>
        </div>
    </div>
@endif

<div class="row">
    @foreach ($packages as $package)
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge badge-inline {{ $package->package_for === 'buyer' ? 'badge-soft-primary' : 'badge-soft-success' }}">{{ ucfirst($package->package_for) }}</span>
                        @if ($package->highlight_text)
                            <span class="badge badge-inline badge-warning">{{ $package->highlight_text }}</span>
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
                    <div class="mb-3 text-muted">
                        {{ $package->description ?: translate('No package description added yet.') }}
                    </div>
                    <ul class="list-unstyled fs-13 mb-4">
                        @if (!empty($forceSupplierFeaturedPackage))
                            <li>{{ translate('Homepage featured visibility included') }}</li>
                            <li>{{ $package->priority_listing ? translate('Priority listing included') : translate('Standard listing') }}</li>
                            <li>{{ $package->verified_badge ? translate('Verified badge included') : translate('No verified badge') }}</li>
                            <li>{{ translate('Projection') }}: {{ number_format($featuredProjection['company_count'] ?? 0) }} {{ translate('companies') }} x {{ single_price($package->monthlyEquivalent()) }} = {{ single_price(($featuredProjection['company_count'] ?? 0) * $package->monthlyEquivalent()) }}/{{ translate('month') }}</li>
                        @else
                            <li>{{ translate('RFQ Limit') }}: {{ $package->rfq_limit ?: translate('Unlimited') }}</li>
                            <li>{{ translate('Quotation Limit') }}: {{ $package->quotation_limit ?: translate('Unlimited') }}</li>
                            <li>{{ translate('Product Limit') }}: {{ $package->product_limit ?: translate('Unlimited') }}</li>
                            <li>{{ translate('Team Members') }}: {{ $package->member_limit }}</li>
                        @endif
                    </ul>
                    <div class="d-flex flex-wrap">
                        <a href="{{ route($editRoute ?? 'admin.b2b.packages.edit', $package->id) }}" class="btn btn-soft-primary btn-sm mr-2 mb-2">{{ translate('Edit') }}</a>
                        <form action="{{ route($deleteRoute ?? 'admin.b2b.packages.delete', $package->id) }}" method="POST" class="mb-2">
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
