@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h3 class="h3">{{ translate('Premium Verification Packages') }}</h3>
            <p class="text-muted mb-0">{{ translate('Create separate paid company verification plans with premium verified status.') }}</p>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('admin.b2b.premium-verifications.requests') }}" class="btn btn-soft-info mr-2">{{ translate('Premium Verification Requests') }}</a>
            <a href="{{ route('admin.b2b.premium-verifications.create') }}" class="btn btn-info">{{ translate('Add Premium Verification Package') }}</a>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">{{ translate('Revenue Projection') }}</h5>
        <div class="fs-16 text-muted">
            {{ $projection['plan_name'] ?: translate('Premium Verification') }}:
            <strong>{{ single_price($projection['price'] ?? 0) }}</strong>
        </div>
        <div class="fs-20 fw-700 mt-2">
            {{ $projection['company_count'] ?? 0 }} x {{ single_price($projection['price'] ?? 0) }} = {{ single_price($projection['projected_revenue'] ?? 0) }}
        </div>
    </div>
</div>

<div class="row">
    @foreach ($packages as $package)
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge badge-inline badge-success">{{ translate('Premium Verification') }}</span>
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
                    </div>
                    <div class="mb-3 text-muted">{{ $package->description ?: translate('Separate premium company verification package.') }}</div>
                    <ul class="list-unstyled fs-13 mb-4">
                        <li>{{ translate('One-time premium verification fee') }}</li>
                        <li>{{ translate('Premium verified company status') }}</li>
                        <li>{{ translate('Projection') }}: {{ number_format($projection['company_count'] ?? 0) }} {{ translate('approved companies') }} x {{ single_price($package->amount) }} = {{ single_price($package->amount * ($projection['company_count'] ?? 0)) }}</li>
                    </ul>
                    <div class="d-flex flex-wrap">
                        <a href="{{ route('admin.b2b.premium-verifications.edit', $package->id) }}" class="btn btn-soft-primary btn-sm mr-2 mb-2">{{ translate('Edit') }}</a>
                        <form action="{{ route('admin.b2b.premium-verifications.delete', $package->id) }}" method="POST" class="mb-2">
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
