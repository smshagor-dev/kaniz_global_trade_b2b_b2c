@extends('frontend.layouts.app')

@section('content')
    <section class="py-4 bg-light">
        <div class="container">
            <div class="row align-items-center mb-4">
                <div class="col-md-7">
                    <h1 class="h3 mb-1">{{ translate('Supplier Directory') }}</h1>
                    <p class="text-muted mb-0">{{ translate('Discover verified B2B suppliers, manufacturers, distributors, and wholesalers.') }}</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('b2b.suppliers.index') }}">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <input type="text" class="form-control" name="keyword" value="{{ request('keyword') }}" placeholder="{{ translate('Keyword') }}">
                            </div>
                            <div class="col-md-2 mb-3">
                                <select class="form-control aiz-selectpicker" name="country" data-live-search="true">
                                    <option value="">{{ translate('All Countries') }}</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country }}" @selected(request('country') === $country)>{{ $country }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <select class="form-control aiz-selectpicker" name="company_type">
                                    <option value="">{{ translate('All Types') }}</option>
                                    @foreach (\App\Models\B2BCompany::SUPPLIER_TYPES as $type)
                                        <option value="{{ $type }}" @selected(request('company_type') === $type)>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <select class="form-control aiz-selectpicker" name="category">
                                    <option value="">{{ translate('All Categories') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->getTranslation('name') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <select class="form-control aiz-selectpicker" name="sort">
                                    <option value="featured" @selected($sort === 'featured')>{{ translate('Featured First') }}</option>
                                    <option value="newest" @selected($sort === 'newest')>{{ translate('Newest') }}</option>
                                    <option value="response_rate" @selected($sort === 'response_rate')>{{ translate('Response Rate') }}</option>
                                    <option value="profile_score" @selected($sort === 'profile_score')>{{ translate('Profile Score') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="aiz-checkbox">
                                    <input type="checkbox" name="verified_supplier_badge" value="1" @checked(request()->boolean('verified_supplier_badge'))>
                                    <span class="aiz-square-check"></span>
                                    <span>{{ translate('Verified Supplier Only') }}</span>
                                </label>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="aiz-checkbox">
                                    <input type="checkbox" name="featured_supplier" value="1" @checked(request()->boolean('featured_supplier'))>
                                    <span class="aiz-square-check"></span>
                                    <span>{{ translate('Featured Supplier Only') }}</span>
                                </label>
                            </div>
                            <div class="col-md-6 mb-3 text-md-right">
                                <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                @forelse ($suppliers as $supplier)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="mr-3" style="width:72px;">
                                        @if ($supplier->logo)
                                            <img src="{{ asset($supplier->logo) }}" class="img-fluid border p-2 bg-white" alt="{{ $supplier->company_name }}">
                                        @else
                                            <div class="border p-3 text-center text-muted bg-white">{{ translate('No Logo') }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        <h5 class="mb-1">{{ $supplier->company_name }}</h5>
                                        @include('frontend.partials.supplier_badge', ['company' => $supplier])
                                        @if ($supplier->hasActiveFeaturedHomepagePlan())
                                            <span class="badge badge-inline badge-warning">{{ translate('Featured') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <p class="mb-1 text-muted">{{ ucfirst($supplier->company_type) }} • {{ $supplier->country }}{{ $supplier->city ? ', ' . $supplier->city : '' }}</p>
                                <p class="mb-2">{{ \Illuminate\Support\Str::limit($supplier->business_scope ?: $supplier->description, 120) ?: '-' }}</p>
                                <p class="mb-2"><strong>{{ translate('Categories') }}:</strong> {{ $supplier->categories->take(3)->map(fn ($category) => $category->getTranslation('name'))->implode(', ') ?: '-' }}</p>
                                <p class="mb-2"><strong>{{ translate('Response Rate') }}:</strong> {{ $supplier->response_rate ? $supplier->response_rate . '%' : '-' }}</p>
                                <p class="mb-0"><strong>{{ translate('Profile Score') }}:</strong> {{ $supplier->profile_score }}</p>
                            </div>
                            <div class="card-footer bg-white border-0 pt-0">
                                <a href="{{ route('b2b.suppliers.show', $supplier->public_slug) }}" class="btn btn-soft-primary btn-block">{{ translate('View Supplier Profile') }}</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info">{{ translate('No suppliers matched your filters.') }}</div>
                    </div>
                @endforelse
            </div>

            <div class="aiz-pagination">
                {{ $suppliers->links() }}
            </div>
        </div>
    </section>
@endsection
