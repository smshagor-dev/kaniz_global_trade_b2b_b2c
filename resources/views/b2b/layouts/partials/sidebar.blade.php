@php
    use App\Services\B2BCompanyService;
    use App\Services\B2BGlobalConfigService;
    use App\Services\B2BPermissionService;
    use Illuminate\Support\Facades\Auth;

    $companyService = app(B2BCompanyService::class);
    $resolvedCompany = Auth::check() ? $companyService->getCompanyByUser(Auth::id()) : null;
    $portalName = $portal === 'supplier' ? translate('Supplier Portal') : translate('Buyer Portal');
    $company = $company ?? $resolvedCompany;
    $switchableCompanies = $switchableCompanies ?? (Auth::check() ? $companyService->getSwitchableCompaniesByUser(Auth::id()) : collect());
    $permissionService = app(B2BPermissionService::class);
    $globalConfigService = app(B2BGlobalConfigService::class);
    $userId = Auth::id();
    $companyId = $company?->id;
    $abilities = $companyId ? $permissionService->getPortalAbilityMatrix($userId, $companyId) : [];
    $canAccessCompany = $abilities['can_access_company'] ?? false;
    $canManageCompany = $abilities['can_manage_company'] ?? false;
    $canInviteMembers = $abilities['can_invite_members'] ?? false;
    $canManageSupplierProfile = $abilities['can_manage_supplier_profile'] ?? false;
    $canCreateRfq = $abilities['can_create_rfq'] ?? false;
    $canSubmitQuotation = $abilities['can_submit_quotation'] ?? false;
    $canManagePurchaseOrder = $abilities['can_manage_purchase_order'] ?? false;
    $canManageInvoice = $abilities['can_manage_invoice'] ?? false;
    $canManageFreight = $abilities['can_manage_freight'] ?? false;
    $canApproveFreightCosts = $abilities['can_approve_freight_costs'] ?? false;
    $canManageInsurance = $abilities['can_manage_insurance'] ?? false;
    $canManageTradeFinance = $abilities['can_manage_trade_finance'] ?? false;
    $canParticipateInNegotiation = $abilities['can_participate_in_negotiation'] ?? false;
    $canUseAiTradeDesk = $company
        && $globalConfigService->aiVisible()
        && $canAccessCompany;
    $navItems = $portal === 'supplier'
        ? [
            ['label' => translate('Dashboard'), 'route' => 'supplier.dashboard', 'patterns' => ['supplier.dashboard']],
            ['label' => translate('Product'), 'route' => 'supplier.b2b.products.index', 'patterns' => ['supplier.b2b.products.index', 'supplier.b2b.products.create', 'supplier.b2b.products.edit'], 'visible' => $canAccessCompany],
            ['label' => translate('Catalogs'), 'route' => 'seller.b2b.company.catalogs', 'patterns' => ['seller.b2b.company.catalogs'], 'visible' => $canManageSupplierProfile],
            ['label' => translate('RFQ Opportunities'), 'route' => 'seller.b2b.rfqs.index', 'patterns' => ['seller.b2b.rfqs.index', 'seller.b2b.rfqs.quote'], 'visible' => $canAccessCompany],
            ['label' => translate('Quotations'), 'route' => 'seller.b2b.quotations.index', 'patterns' => ['seller.b2b.quotations.index', 'seller.b2b.quotations.show', 'seller.b2b.quotations.edit'], 'visible' => $canSubmitQuotation],
            ['label' => translate('Conversations'), 'route' => 'seller.b2b.negotiations.index', 'patterns' => ['seller.b2b.negotiations.index', 'seller.b2b.negotiations.show'], 'visible' => $canParticipateInNegotiation],
            ['label' => translate('Purchase Orders'), 'route' => 'seller.b2b.purchase-orders.index', 'patterns' => ['seller.b2b.purchase-orders.index', 'seller.b2b.purchase-orders.show'], 'visible' => $canAccessCompany],
            ['label' => translate('Proforma Invoices'), 'route' => 'seller.b2b.proforma-invoices.index', 'patterns' => ['seller.b2b.proforma-invoices.index', 'seller.b2b.proforma-invoices.show', 'seller.b2b.proforma-invoices.create'], 'visible' => $canAccessCompany],
            ['label' => translate('Sample Requests'), 'route' => 'seller.b2b.sample-orders.index', 'patterns' => ['seller.b2b.sample-orders.index', 'seller.b2b.sample-orders.show'], 'visible' => $canAccessCompany],
            ['label' => translate('Shipments'), 'route' => 'seller.b2b.shipments.index', 'patterns' => ['seller.b2b.shipments.index', 'seller.b2b.shipments.show', 'seller.b2b.shipments.create'], 'visible' => $canAccessCompany],
            ['label' => translate('Freight'), 'route' => 'seller.b2b.freight-quotes.index', 'patterns' => ['seller.b2b.freight-quotes.index', 'seller.b2b.freight-quotes.show', 'seller.b2b.shipping-quotes.purchase-orders.create', 'seller.b2b.shipping-quotes.sample-orders.create'], 'visible' => $canAccessCompany],
            ['label' => translate('Insurance'), 'route' => 'seller.b2b.insurance.dashboard', 'patterns' => ['seller.b2b.insurance.dashboard'], 'visible' => $canAccessCompany],
            ['label' => translate('Trade Finance'), 'route' => 'seller.b2b.trade-finance.dashboard', 'patterns' => ['seller.b2b.trade-finance.dashboard'], 'visible' => $canAccessCompany],
            ['label' => translate('Earnings'), 'route' => 'seller.b2b.finance.earnings', 'patterns' => ['seller.b2b.finance.earnings'], 'visible' => $canAccessCompany],
            ['label' => translate('Payouts'), 'route' => 'seller.b2b.finance.payouts', 'patterns' => ['seller.b2b.finance.payouts'], 'visible' => $canAccessCompany],
            ['label' => translate('Packages'), 'route' => 'b2b.packages.index', 'patterns' => ['b2b.packages.index'], 'visible' => $canAccessCompany],
            ['label' => translate('Premium Verification'), 'route' => 'b2b.premium-verifications.index', 'patterns' => ['b2b.premium-verifications.index'], 'visible' => $canAccessCompany],
            ['label' => translate('Sponsored Products'), 'route' => 'seller.b2b.product-promotions.index', 'patterns' => ['seller.b2b.product-promotions.index'], 'visible' => $canAccessCompany],
        ]
        : [
            ['label' => translate('Dashboard'), 'route' => 'buyer.dashboard', 'patterns' => ['buyer.dashboard']],
            ['label' => translate('RFQs'), 'route' => 'b2b.rfqs.index', 'patterns' => ['b2b.rfqs.index', 'b2b.rfqs.create', 'b2b.rfqs.show', 'b2b.rfqs.edit'], 'visible' => $canAccessCompany],
            ['label' => translate('Conversations'), 'route' => 'b2b.negotiations.index', 'patterns' => ['b2b.negotiations.index', 'b2b.negotiations.show'], 'visible' => $canParticipateInNegotiation],
            ['label' => translate('Purchase Orders'), 'route' => 'b2b.purchase-orders.index', 'patterns' => ['b2b.purchase-orders.index', 'b2b.purchase-orders.show'], 'visible' => $canManagePurchaseOrder || $canParticipateInNegotiation],
            ['label' => translate('Proforma Invoices'), 'route' => 'b2b.proforma-invoices.index', 'patterns' => ['b2b.proforma-invoices.index', 'b2b.proforma-invoices.show'], 'visible' => $canManageInvoice || $canParticipateInNegotiation],
            ['label' => translate('Sample Orders'), 'route' => 'b2b.sample-orders.index', 'patterns' => ['b2b.sample-orders.index', 'b2b.sample-orders.create', 'b2b.sample-orders.show'], 'visible' => $canManagePurchaseOrder || $canParticipateInNegotiation],
            ['label' => translate('Shipments'), 'route' => 'b2b.shipments.index', 'patterns' => ['b2b.shipments.index', 'b2b.shipments.show'], 'visible' => $canManagePurchaseOrder || $canParticipateInNegotiation],
            ['label' => translate('Freight'), 'route' => 'b2b.freight-quotes.index', 'patterns' => ['b2b.freight-quotes.index', 'b2b.freight-quotes.show'], 'visible' => $canManageFreight || $canApproveFreightCosts],
            ['label' => translate('Insurance'), 'route' => 'b2b.insurance.dashboard', 'patterns' => ['b2b.insurance.dashboard'], 'visible' => $canManageInsurance],
            ['label' => translate('Trade Finance'), 'route' => 'b2b.trade-finance.dashboard', 'patterns' => ['b2b.trade-finance.dashboard'], 'visible' => $canManageTradeFinance],
            ['label' => translate('Packages'), 'route' => 'b2b.packages.index', 'patterns' => ['b2b.packages.index'], 'visible' => $canAccessCompany],
            ['label' => translate('Premium Verification'), 'route' => 'b2b.premium-verifications.index', 'patterns' => ['b2b.premium-verifications.index'], 'visible' => $canAccessCompany],
        ];

    $navItems = array_merge($navItems, [
        ['label' => translate('AI Trade Desk'), 'route' => 'b2b.ai.dashboard', 'patterns' => ['b2b.ai.dashboard', 'b2b.ai.rfq-assistant', 'b2b.ai.hs-code', 'b2b.ai.trade-assistant', 'b2b.ai.price-recommendation', 'b2b.ai.freight-recommendation', 'b2b.ai.currency-analysis', 'b2b.ai.trade-finance', 'b2b.ai.opportunities', 'b2b.ai.notifications', 'b2b.ai.dashboard-insights'], 'visible' => $canUseAiTradeDesk],
    ]);

    $settingsItems = [
        ['label' => translate('Company Settings'), 'route' => 'b2b.company.edit', 'patterns' => ['b2b.company.edit'], 'visible' => $canManageCompany],
        ['label' => translate('My Company'), 'route' => 'b2b.company.show', 'patterns' => ['b2b.company.show', 'buyer.onboarding', 'supplier.onboarding'], 'visible' => $canAccessCompany],
        ['label' => translate('Public Supplier Profile'), 'route' => 'seller.b2b.company.public-profile', 'patterns' => ['seller.b2b.company.public-profile'], 'visible' => $portal === 'supplier' && $canAccessCompany],
        ['label' => translate('Team'), 'route' => 'b2b.company.members.index', 'patterns' => ['b2b.company.members.index', 'b2b.company.members.invite'], 'visible' => $canAccessCompany],
        ['label' => translate('Role Permissions'), 'route' => 'b2b.company.roles.index', 'patterns' => ['b2b.company.roles.index'], 'visible' => $canAccessCompany],
    ];

    $settingsVisible = collect($settingsItems)->contains(fn ($item) => ($item['visible'] ?? true) && Route::has($item['route']));
    $settingsActivePatterns = collect($settingsItems)->flatMap(fn ($item) => $item['patterns'])->values()->all();
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
                    @if (($item['visible'] ?? true) && Route::has($item['route']))
                        <li class="aiz-side-nav-item">
                            <a href="{{ route($item['route']) }}" class="aiz-side-nav-link {{ areActiveRoutes($item['patterns']) }}">
                                <i class="las la-angle-right aiz-side-nav-icon"></i>
                                <span class="aiz-side-nav-text">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach

                @if ($settingsVisible)
                    <li class="aiz-side-nav-item">
                        <a href="javascript:void(0);" class="aiz-side-nav-link {{ areActiveRoutes($settingsActivePatterns) }}">
                            <i class="las la-cog aiz-side-nav-icon"></i>
                            <span class="aiz-side-nav-text">{{ translate('Settings') }}</span>
                            <span class="aiz-side-nav-arrow"></span>
                        </a>
                        <ul class="aiz-side-nav-list level-2">
                            @foreach ($settingsItems as $item)
                                @if (($item['visible'] ?? true) && Route::has($item['route']))
                                    <li class="aiz-side-nav-item">
                                        <a href="{{ route($item['route']) }}" class="aiz-side-nav-link {{ areActiveRoutes($item['patterns']) }}">
                                            <span class="aiz-side-nav-text">{{ $item['label'] }}</span>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
