@extends('frontend.layouts.app')

@section('content')
    <section class="py-4 bg-light">
        <div class="container">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 mb-3 mb-md-0">
                            @if ($supplier->logo)
                                <img src="{{ asset($supplier->logo) }}" class="img-fluid border p-2 bg-white" alt="{{ $supplier->company_name }}">
                            @else
                                <div class="border p-4 text-center text-muted bg-white">{{ translate('No Logo') }}</div>
                            @endif
                        </div>
                        <div class="col-md-7">
                            <h1 class="h3 mb-2">{{ $supplier->company_name }}</h1>
                            @include('frontend.partials.supplier_badge', ['company' => $supplier])
                            @if ($supplier->hasActiveFeaturedHomepagePlan())
                                <span class="badge badge-inline badge-warning">{{ translate('Featured Supplier') }}</span>
                            @endif
                            @if (!empty($trustStatus['label']))
                                <div class="mt-2">
                                    <span class="badge badge-inline badge-{{ $trustStatus['tone'] === 'success' ? 'success' : ($trustStatus['tone'] === 'danger' ? 'danger' : 'warning') }}">{{ $trustStatus['label'] }}</span>
                                </div>
                            @endif
                            <p class="mb-1 mt-2">{{ ucfirst($supplier->company_type) }} • {{ $supplier->country }}{{ $supplier->city ? ', ' . $supplier->city : '' }}</p>
                            <p class="mb-0 text-muted">{{ $supplier->business_scope ?: $supplier->description ?: '-' }}</p>
                        </div>
                        <div class="col-md-3 text-md-right mt-3 mt-md-0">
                            <a href="mailto:{{ $supplier->business_email }}" class="btn btn-soft-primary btn-block mb-2">{{ translate('Contact Supplier') }}</a>
                            @auth
                                <a href="{{ route('b2b.rfqs.create', ['supplier_company_id' => $supplier->id]) }}" class="btn btn-primary btn-block mb-2">{{ translate('Request Quote') }}</a>
                                <a href="{{ route('b2b.sample-orders.create', ['supplier_company_id' => $supplier->id]) }}" class="btn btn-soft-info btn-block">{{ translate('Request Sample') }}</a>
                            @else
                                <a href="{{ route('user.login') }}" class="btn btn-primary btn-block">{{ translate('Request Quote') }}</a>
                            @endauth
                            @auth
                                <form action="{{ route('users.report', $supplier->user_id) }}" method="POST" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="report_type" value="fake_supplier">
                                    <button type="submit" class="btn btn-soft-danger btn-block">{{ translate('Report Supplier') }}</button>
                                </form>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white"><h5 class="mb-0">{{ translate('Factory Capability') }}</h5></div>
                        <div class="card-body">
                            <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Production Capacity') }}</div><div class="col-md-8">{{ $supplier->production_capacity ?: '-' }}</div></div>
                            <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Factory Size') }}</div><div class="col-md-8">{{ $supplier->factory_size ?: '-' }}</div></div>
                            <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Factory Location') }}</div><div class="col-md-8">{{ $supplier->factory_location ?: '-' }}</div></div>
                            <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Quality Control') }}</div><div class="col-md-8">{{ $supplier->quality_control ?: '-' }}</div></div>
                            <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Lead Time') }}</div><div class="col-md-8">{{ $supplier->lead_time_summary ?: '-' }}</div></div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white"><h5 class="mb-0">{{ translate('Categories') }}</h5></div>
                        <div class="card-body">
                            @forelse ($supplier->categories as $category)
                                <span class="badge badge-inline badge-secondary mb-2">{{ $category->getTranslation('name') }}</span>
                            @empty
                                <p class="mb-0 text-muted">-</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white"><h5 class="mb-0">{{ translate('Certifications') }}</h5></div>
                        <div class="card-body">
                            @forelse ($supplier->certifications as $certification)
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $certification->name }}</strong>
                                        <span class="badge badge-inline badge-success">{{ ucfirst($certification->verification_status) }}</span>
                                    </div>
                                    <div class="text-muted small">{{ $certification->issuing_authority ?: '-' }}</div>
                                    <div class="small">{{ translate('Certificate No') }}: {{ $certification->certificate_number ?: '-' }}</div>
                                    <div class="small">{{ translate('Issue / Expiry') }}: {{ optional($certification->issue_date)->format('d M Y') ?: '-' }} / {{ optional($certification->expiry_date)->format('d M Y') ?: '-' }}</div>
                                    @if ($certification->file)
                                        <a href="{{ asset($certification->file) }}" target="_blank" class="small">{{ translate('View File') }}</a>
                                    @endif
                                </div>
                            @empty
                                <p class="mb-0 text-muted">{{ translate('No approved certifications listed yet.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white"><h5 class="mb-0">{{ translate('Wholesale Products') }}</h5></div>
                        <div class="card-body">
                            <div class="row">
                                @forelse ($supplier->wholesaleProducts as $product)
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-2 h-100">
                                            <a href="{{ route('product', $product->slug) }}" class="d-block mb-2">
                                                <img src="{{ get_image($product->thumbnail) }}" class="img-fluid" alt="{{ $product->getTranslation('name') }}">
                                            </a>
                                            <a href="{{ route('product', $product->slug) }}" class="fw-600 d-block">{{ $product->getTranslation('name') }}</a>
                                            <div class="text-primary">{{ home_discounted_base_price($product) }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted">{{ translate('No wholesale products available.') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white"><h5 class="mb-0">{{ translate('Supplier Snapshot') }}</h5></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2"><span>{{ translate('Year Established') }}</span><strong>{{ $supplier->year_established ?: '-' }}</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>{{ translate('Years on Platform') }}</span><strong>{{ optional($supplier->created_at)->diffInYears(now()) ?? 0 }}</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>{{ translate('Employees') }}</span><strong>{{ $supplier->employee_count ?: '-' }}</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>{{ translate('Annual Revenue') }}</span><strong>{{ $supplier->annual_revenue ?: '-' }}</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>{{ translate('Main Markets') }}</span><strong>{{ $supplier->main_markets ?: '-' }}</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>{{ translate('Export Percentage') }}</span><strong>{{ $supplier->export_percentage ? $supplier->export_percentage . '%' : '-' }}</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>{{ translate('Response Rate') }}</span><strong>{{ $supplier->response_rate ? $supplier->response_rate . '%' : '-' }}</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>{{ translate('Completed Orders') }}</span><strong>{{ $supplier->supplierPurchaseOrders()->count() }}</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>{{ translate('Reviews') }}</span><strong>{{ $supplier->wholesaleProducts()->withCount('reviews')->get()->sum('reviews_count') }}</strong></div>
                            <div class="d-flex justify-content-between"><span>{{ translate('Response Time') }}</span><strong>{{ $supplier->response_time_hours ? $supplier->response_time_hours . 'h' : '-' }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
