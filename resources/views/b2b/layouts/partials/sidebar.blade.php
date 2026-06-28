@php
    $portalName = $portal === 'supplier' ? translate('Supplier Portal') : translate('Buyer Portal');
    $company = $company ?? null;
    $switchableCompanies = $switchableCompanies ?? collect();
    $navItems = $portal === 'supplier'
        ? [
            ['label' => translate('Dashboard'), 'route' => 'supplier.dashboard', 'patterns' => ['supplier.dashboard']],
            ['label' => translate('My Company'), 'route' => 'b2b.company.show', 'patterns' => ['b2b.company.show', 'b2b.company.edit', 'supplier.onboarding']],
            ['label' => translate('Team'), 'route' => 'b2b.company.members.index', 'patterns' => ['b2b.company.members.index', 'b2b.company.members.invite']],
            ['label' => translate('Public Supplier Profile'), 'route' => 'seller.b2b.company.public-profile', 'patterns' => ['seller.b2b.company.public-profile']],
            ['label' => translate('Wholesale Products'), 'route' => 'seller.wholesale_products_list', 'patterns' => ['seller.wholesale_products_list']],
            ['label' => translate('RFQ Opportunities'), 'route' => 'seller.b2b.rfqs.index', 'patterns' => ['seller.b2b.rfqs.index', 'seller.b2b.rfqs.quote']],
            ['label' => translate('Quotations'), 'route' => 'seller.b2b.quotations.index', 'patterns' => ['seller.b2b.quotations.index', 'seller.b2b.quotations.show', 'seller.b2b.quotations.edit']],
            ['label' => translate('Purchase Orders'), 'route' => 'seller.b2b.purchase-orders.index', 'patterns' => ['seller.b2b.purchase-orders.index', 'seller.b2b.purchase-orders.show']],
            ['label' => translate('Proforma Invoices'), 'route' => 'seller.b2b.proforma-invoices.index', 'patterns' => ['seller.b2b.proforma-invoices.index', 'seller.b2b.proforma-invoices.show', 'seller.b2b.proforma-invoices.create']],
            ['label' => translate('Sample Requests'), 'route' => 'seller.b2b.sample-orders.index', 'patterns' => ['seller.b2b.sample-orders.index', 'seller.b2b.sample-orders.show']],
            ['label' => translate('Shipments'), 'route' => 'seller.b2b.shipments.index', 'patterns' => ['seller.b2b.shipments.index', 'seller.b2b.shipments.show', 'seller.b2b.shipments.create']],
            ['label' => translate('Freight'), 'route' => 'seller.b2b.freight-quotes.index', 'patterns' => ['seller.b2b.freight-quotes.index', 'seller.b2b.freight-quotes.show', 'seller.b2b.shipping-quotes.purchase-orders.create', 'seller.b2b.shipping-quotes.sample-orders.create']],
            ['label' => translate('Insurance'), 'route' => 'seller.b2b.insurance.dashboard', 'patterns' => ['seller.b2b.insurance.dashboard']],
            ['label' => translate('Trade Finance'), 'route' => 'seller.b2b.trade-finance.dashboard', 'patterns' => ['seller.b2b.trade-finance.dashboard']],
        ]
        : [
            ['label' => translate('Dashboard'), 'route' => 'buyer.dashboard', 'patterns' => ['buyer.dashboard']],
            ['label' => translate('My Company'), 'route' => 'b2b.company.show', 'patterns' => ['b2b.company.show', 'b2b.company.edit', 'buyer.onboarding']],
            ['label' => translate('Team'), 'route' => 'b2b.company.members.index', 'patterns' => ['b2b.company.members.index', 'b2b.company.members.invite']],
            ['label' => translate('RFQs'), 'route' => 'b2b.rfqs.index', 'patterns' => ['b2b.rfqs.index', 'b2b.rfqs.create', 'b2b.rfqs.show', 'b2b.rfqs.edit']],
            ['label' => translate('Purchase Orders'), 'route' => 'b2b.purchase-orders.index', 'patterns' => ['b2b.purchase-orders.index', 'b2b.purchase-orders.show']],
            ['label' => translate('Proforma Invoices'), 'route' => 'b2b.proforma-invoices.index', 'patterns' => ['b2b.proforma-invoices.index', 'b2b.proforma-invoices.show']],
            ['label' => translate('Sample Orders'), 'route' => 'b2b.sample-orders.index', 'patterns' => ['b2b.sample-orders.index', 'b2b.sample-orders.create', 'b2b.sample-orders.show']],
            ['label' => translate('Shipments'), 'route' => 'b2b.shipments.index', 'patterns' => ['b2b.shipments.index', 'b2b.shipments.show']],
            ['label' => translate('Freight'), 'route' => 'b2b.freight-quotes.index', 'patterns' => ['b2b.freight-quotes.index', 'b2b.freight-quotes.show']],
            ['label' => translate('Insurance'), 'route' => 'b2b.insurance.dashboard', 'patterns' => ['b2b.insurance.dashboard']],
            ['label' => translate('Trade Finance'), 'route' => 'b2b.trade-finance.dashboard', 'patterns' => ['b2b.trade-finance.dashboard']],
        ];

    $navItems = array_merge($navItems, [
        ['label' => translate('AI Trade Desk'), 'route' => 'b2b.ai.dashboard', 'patterns' => ['b2b.ai.dashboard', 'b2b.ai.rfq-assistant', 'b2b.ai.hs-code', 'b2b.ai.trade-assistant', 'b2b.ai.price-recommendation', 'b2b.ai.supplier-risk', 'b2b.ai.buyer-risk', 'b2b.ai.freight-recommendation', 'b2b.ai.currency-analysis', 'b2b.ai.trade-finance', 'b2b.ai.opportunities', 'b2b.ai.notifications', 'b2b.ai.dashboard-insights']],
        ['label' => translate('Settings'), 'route' => 'b2b.packages.index', 'patterns' => ['b2b.packages.index', 'b2b.premium-verifications.index', 'seller.b2b.product-promotions.index']],
    ]);
@endphp

<div class="aiz-sidebar-wrap b2b-sidebar-wrap">
    <div class="aiz-sidebar left c-scrollbar">
        <div class="aiz-side-nav-logo-wrap">
            <span class="b2b-portal-badge">{{ translate('Enterprise B2B') }}</span>
            <div class="d-block">
                <img class="mw-100 mb-3" src="{{ uploaded_asset(get_setting('header_logo')) }}" alt="{{ get_setting('site_name') }}">
                <h3 class="fs-16 m-0 text-primary">{{ $company?->company_name ?? $portalName }}</h3>
                <p class="text-primary mb-0">{{ ucfirst($company?->company_type ?? $portal) }}</p>
            </div>
        </div>
        <div class="aiz-side-nav-wrap">
            <div class="px-20px mb-3 mt-3">
                <input class="form-control bg-soft-secondary border-0 form-control-sm" type="text" placeholder="{{ translate('Search in menu') }}" id="menu-search" onkeyup="menuSearch()">
            </div>

            @if ($company && $switchableCompanies->count() > 1)
                <div class="px-20px mb-3">
                    <form method="POST" action="{{ route('b2b.company.switch') }}">
                        @csrf
                        <select class="form-control aiz-selectpicker" name="company_id" onchange="this.form.submit()">
                            @foreach ($switchableCompanies as $switchableCompany)
                                <option value="{{ $switchableCompany->id }}" @selected((int) $switchableCompany->id === (int) $company->id)>
                                    {{ $switchableCompany->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            <ul class="aiz-side-nav-list" id="search-menu"></ul>
            <ul class="aiz-side-nav-list" id="main-menu" data-toggle="aiz-side-menu">
                @foreach ($navItems as $item)
                    @if (Route::has($item['route']))
                        <li class="aiz-side-nav-item">
                            <a href="{{ route($item['route']) }}" class="aiz-side-nav-link {{ areActiveRoutes($item['patterns']) }}">
                                <i class="las la-angle-right aiz-side-nav-icon"></i>
                                <span class="aiz-side-nav-text">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</div>
