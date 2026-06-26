@php
    $tradeServicesData = array_merge([
        'verified_logistics_partners' => 0,
        'active_shipments' => 0,
        'public_suppliers' => 0,
        'featured_suppliers' => 0,
        'featured_supplier_monthly_price' => 0,
        'featured_supplier_package_name' => null,
        'featured_suppliers_list' => collect(),
    ], is_array($tradeServicesData ?? null) ? $tradeServicesData : []);
@endphp

<section class="py-5 bg-white border-top">
    <div class="container">
        <div class="row align-items-center mb-4">
            <div class="col-lg-7">
                <h2 class="fs-24 fw-700 text-dark mb-2">{{ translate('Global Trade Services') }}</h2>
                <p class="fs-14 text-secondary mb-0">
                    {{ translate('Source products, compare freight, protect international orders, and manage delivery milestones from RFQ to final shipment.') }}
                </p>
            </div>
            <div class="col-lg-5 mt-3 mt-lg-0 text-lg-right">
                <a href="{{ route('b2b.suppliers.index') }}" class="btn btn-primary mr-2">{{ translate('Find Suppliers') }}</a>
                @auth
                    <a href="{{ route('b2b.rfqs.create') }}" class="btn btn-outline-primary">{{ translate('Request Freight Quote') }}</a>
                @else
                    <a href="{{ route('user.login') }}" class="btn btn-outline-primary">{{ translate('Request Freight Quote') }}</a>
                @endauth
            </div>
        </div>

        <div class="row gutters-16">
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="border h-100 p-4">
                    <div class="fs-18 fw-700 text-dark mb-2">{{ translate('Trade Services') }}</div>
                    <p class="fs-13 text-secondary mb-0">{{ translate('RFQ, quotation, PI, shipment, and document workflows built for cross-border wholesale trade.') }}</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="border h-100 p-4">
                    <div class="fs-18 fw-700 text-dark mb-2">{{ translate('Global Shipping') }}</div>
                    <p class="fs-13 text-secondary mb-0">{{ translate('Compare air, sea, rail, truck, and courier options with shipping, insurance, and customs estimates.') }}</p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="border h-100 p-4">
                    <div class="fs-18 fw-700 text-dark mb-2">{{ translate('Verified Logistics Partners') }}</div>
                    <p class="fs-13 text-secondary mb-2">{{ translate('Active verified freight providers ready for supplier-side quote creation.') }}</p>
                    <div class="fs-24 fw-700 text-primary">{{ $tradeServicesData['verified_logistics_partners'] }}</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="border h-100 p-4">
                    <div class="fs-18 fw-700 text-dark mb-2">{{ translate('Trade Protection') }}</div>
                    <p class="fs-13 text-secondary mb-2">{{ translate('Track active shipments, attach export documents, and keep buyers and suppliers aligned on one timeline.') }}</p>
                    <div class="fs-13 text-dark">
                        {{ $tradeServicesData['active_shipments'] }} {{ translate('active shipments') }} | {{ $tradeServicesData['public_suppliers'] }} {{ translate('public suppliers') }}
                    </div>
                </div>
            </div>
        </div>

        @if (count($tradeServicesData['featured_suppliers_list']) > 0)
            <div class="row align-items-center mt-4 mb-3">
                <div class="col-lg-8">
                    <h3 class="fs-22 fw-700 text-dark mb-2">{{ translate('Featured Suppliers On Homepage') }}</h3>
                    <p class="fs-14 text-secondary mb-0">
                        {{ translate('Suppliers can subscribe for premium homepage placement and stronger B2B discovery.') }}
                        @if (($tradeServicesData['featured_supplier_monthly_price'] ?? 0) > 0)
                            {{ translate('Current monthly plan') }}:
                            <strong>{{ single_price($tradeServicesData['featured_supplier_monthly_price']) }}</strong>.
                        @endif
                    </p>
                </div>
                <div class="col-lg-4 mt-3 mt-lg-0 text-lg-right">
                    <div class="d-inline-block border rounded px-4 py-3 bg-white">
                        <div class="fs-12 text-muted">{{ translate('Example Revenue') }}</div>
                        <div class="fs-22 fw-700 text-dark">
                            {{ single_price(($tradeServicesData['featured_supplier_monthly_price'] ?? 0) * 200) }}/{{ translate('month') }}
                        </div>
                        <div class="fs-12 text-muted">200 x {{ single_price($tradeServicesData['featured_supplier_monthly_price'] ?? 0) }}</div>
                    </div>
                </div>
            </div>

            <div class="row gutters-16">
                @foreach ($tradeServicesData['featured_suppliers_list'] as $supplier)
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="border h-100 p-4 bg-white">
                            <div class="d-flex align-items-center mb-3">
                                <div class="mr-3" style="width: 64px;">
                                    @if ($supplier->logo)
                                        <img src="{{ asset($supplier->logo) }}" class="img-fluid border p-2 bg-white" alt="{{ $supplier->company_name }}">
                                    @else
                                        <div class="border p-3 text-center text-muted bg-light">{{ translate('Logo') }}</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="fs-16 fw-700 text-dark">{{ $supplier->company_name }}</div>
                                    <div class="fs-12 text-muted">{{ ucfirst($supplier->company_type) }} | {{ $supplier->country }}</div>
                                </div>
                            </div>
                            <p class="fs-13 text-secondary mb-2">{{ \Illuminate\Support\Str::limit($supplier->business_scope ?: $supplier->description, 90) ?: '-' }}</p>
                            <p class="fs-12 text-dark mb-3">
                                {{ translate('Categories') }}:
                                {{ $supplier->categories->take(2)->map(fn ($category) => $category->getTranslation('name'))->implode(', ') ?: '-' }}
                            </p>
                            <a href="{{ route('b2b.suppliers.show', $supplier->public_slug) }}" class="btn btn-outline-primary btn-block">{{ translate('View Supplier') }}</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
