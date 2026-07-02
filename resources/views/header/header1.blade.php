@php
    $headerSearchScope = request('scope', 'ai_mode');
    $headerSearchQuery = request('q', request('keyword', ''));
    $headerImageSearchEnabled = get_setting('enable_global_search_image', '1') === '1';
    $header_logo = get_setting('header_logo');
    $system_language = get_system_language();
    $system_currency = get_system_currency();
    $activeLanguages = collect(get_all_active_language());
    $activeCurrencies = collect(get_all_active_currency());
    $selectedDeliveryCountry = selected_delivery_country();
    $countryOptions = \App\Models\Country::query()
        ->orderBy('name')
        ->get()
        ->map(function ($country) {
        return [
            'id' => $country->id,
            'code' => strtoupper((string) $country->code),
            'name' => $country->name,
        ];
    });
    $selectedCountry = $selectedDeliveryCountry
        ? [
            'id' => $selectedDeliveryCountry->id,
            'code' => strtoupper((string) $selectedDeliveryCountry->code),
            'name' => $selectedDeliveryCountry->name,
        ]
        : ($countryOptions->firstWhere('code', 'US') ?: $countryOptions->first() ?: ['id' => null, 'code' => 'US', 'name' => 'United States']);
    $headerMenus = collect(json_decode(get_setting('header_menu_labels'), true) ?? [])
        ->map(fn ($label, $index) => [
            'label' => $label,
            'link' => json_decode(get_setting('header_menu_links'), true)[$index] ?? '#',
        ]);
@endphp

<header class="@if (get_setting('header_stikcy') == 'on') sticky-top @endif kaniz-header-wrap">
    <div class="kaniz-header-top">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="kaniz-header-top-inner">
                <div class="kaniz-header-top-group">
                    <div class="dropdown" id="country-deliver-change">
                        <a href="javascript:void(0)" class="kaniz-top-link dropdown-toggle" data-toggle="dropdown"
                            data-display="static" aria-expanded="false">
                            <span>Deliver to:</span>
                            <span class="kaniz-flag kaniz-selected-country-code">{{ $selectedCountry['code'] }}</span>
                            <span class="kaniz-selected-country-name">{{ $selectedCountry['name'] }}</span>
                        </a>
                        <div class="dropdown-menu kaniz-top-dropdown">
                            <div class="kaniz-top-dropdown-search">
                                <i class="las la-search"></i>
                                <input type="text" class="kaniz-dropdown-filter" placeholder="Search country">
                            </div>
                            <div class="kaniz-top-dropdown-list">
                                @foreach ($countryOptions as $country)
                                    <a href="javascript:void(0)" class="dropdown-item kaniz-dropdown-item kaniz-country-option"
                                        data-country-id="{{ $country['id'] }}" data-country-code="{{ $country['code'] }}" data-country-name="{{ $country['name'] }}">
                                        <span class="kaniz-flag">{{ $country['code'] }}</span>
                                        <span>{{ $country['name'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="dropdown">
                        <a href="javascript:void(0)" class="kaniz-top-link dropdown-toggle" data-toggle="dropdown"
                            data-display="static" aria-expanded="false">
                            <i class="las la-globe"></i>
                            <span>{{ $system_language->name ?? 'English' }}-{{ $system_currency->code ?? 'USD' }}</span>
                        </a>
                        <div class="dropdown-menu kaniz-top-dropdown kaniz-top-dropdown-wide">
                            <div class="kaniz-top-dropdown-search">
                                <i class="las la-search"></i>
                                <input type="text" class="kaniz-dropdown-filter" placeholder="Search language or currency">
                            </div>
                            <div class="kaniz-top-dropdown-columns">
                                <div class="kaniz-top-dropdown-column">
                                    <div class="kaniz-top-dropdown-title">Languages</div>
                                    <div class="kaniz-top-dropdown-list">
                                        @foreach ($activeLanguages as $language)
                                            <a href="javascript:void(0)" data-flag="{{ $language->code }}"
                                                class="dropdown-item kaniz-dropdown-item @if (($system_language->code ?? null) == $language->code) active @endif">
                                                <img src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                    data-src="{{ static_asset('assets/img/flags/' . $language->code . '.png') }}"
                                                    class="mr-2 lazyload" alt="{{ $language->name }}" height="11">
                                                <span>{{ $language->name }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="kaniz-top-dropdown-column">
                                    <div class="kaniz-top-dropdown-title">Currencies</div>
                                    <div class="kaniz-top-dropdown-list">
                                        @foreach ($activeCurrencies as $currency)
                                            <a href="javascript:void(0)" data-currency="{{ $currency->code }}"
                                                class="dropdown-item kaniz-dropdown-item @if (($system_currency->code ?? null) == $currency->code) active @endif">
                                                <span>{{ $currency->name }} ({{ $currency->symbol }})</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('buyer.portal') }}" class="kaniz-top-link">
                        <i class="las la-user-circle"></i>
                        <span>Buyer Central</span>
                    </a>
                    <a href="{{ route('home') }}" class="kaniz-top-link">
                        <i class="las la-question-circle"></i>
                        <span>Help Center</span>
                    </a>
                </div>
                <div class="kaniz-header-top-group kaniz-header-top-group-right">
                    <a href="javascript:void(0)" class="kaniz-top-link">
                        <i class="las la-mobile-alt"></i>
                        <span>Get the app</span>
                    </a>
                    <a href="{{ route('b2b.portal.become-supplier') }}" class="kaniz-top-link">
                        <i class="las la-store-alt"></i>
                        <span>Become a supplier</span>
                    </a>
                    <div class="dropdown" id="country-ship-change">
                        <a href="javascript:void(0)" class="kaniz-top-link dropdown-toggle" data-toggle="dropdown"
                            data-display="static" aria-expanded="false">
                            <span>Ship to:</span>
                            <span class="kaniz-flag kaniz-selected-country-code">{{ $selectedCountry['code'] }}</span>
                            <span class="kaniz-selected-country-name">{{ $selectedCountry['name'] }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right kaniz-top-dropdown">
                            <div class="kaniz-top-dropdown-search">
                                <i class="las la-search"></i>
                                <input type="text" class="kaniz-dropdown-filter" placeholder="Search country">
                            </div>
                            <div class="kaniz-top-dropdown-list">
                                @foreach ($countryOptions as $country)
                                    <a href="javascript:void(0)" class="dropdown-item kaniz-dropdown-item kaniz-country-option"
                                        data-country-id="{{ $country['id'] }}" data-country-code="{{ $country['code'] }}" data-country-name="{{ $country['name'] }}">
                                        <span class="kaniz-flag">{{ $country['code'] }}</span>
                                        <span>{{ $country['name'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="kaniz-header-main">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="kaniz-header-main-inner">
                <div class="kaniz-header-logo-group">
                    <button type="button" class="btn kaniz-mobile-menu d-lg-none" data-toggle="class-toggle"
                        data-target=".aiz-top-menu-sidebar" aria-label="{{ translate('Open menu') }}">
                        <i class="las la-bars"></i>
                    </button>
                    <a class="kaniz-logo-link" href="{{ route('home') }}">
                        @if ($header_logo != null)
                            <img id="header-logo-preview" src="{{ uploaded_asset($header_logo) }}" alt="{{ env('APP_NAME') }}">
                        @else
                            <img id="header-logo-preview" src="{{ static_asset('assets/img/logo.png') }}" alt="{{ env('APP_NAME') }}">
                        @endif
                    </a>
                </div>

                <div class="kaniz-header-search">
                    <form action="{{ route('search') }}" method="GET" class="stop-propagation"
                        id="header-global-search-form">
                        <input type="hidden" name="scope" id="header-global-search-scope" value="{{ $headerSearchScope }}">
                        <input type="hidden" name="country" id="header-global-search-country" value="{{ $selectedCountry['name'] }}">
                        <div class="kaniz-search-shell">
                            <button type="button" class="kaniz-search-category">
                                <span>Products</span>
                                <i class="las la-angle-down"></i>
                            </button>
                            <div class="kaniz-search-input-wrap">
                                <input type="text" id="search" name="keyword" value="{{ $headerSearchQuery }}"
                                    placeholder="What are you looking for..." autocomplete="off">
                                @if ($headerImageSearchEnabled)
                                    <button type="button" class="header-global-search-image" data-toggle="modal"
                                        data-target="#globalSearchImageModal"
                                        aria-label="{{ translate('Image Search') }}">
                                        <i class="las la-camera"></i>
                                    </button>
                                @endif
                            </div>
                            <button type="submit" class="kaniz-search-submit">Search</button>
                        </div>
                    </form>

                    <div class="typed-search-box stop-propagation document-click-d-none d-none bg-white rounded shadow-lg position-absolute left-0 top-100 w-100"
                        style="min-height: 200px">
                        <div class="search-preloader absolute-top-center">
                            <div class="dot-loader">
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                        </div>
                        <div class="search-nothing d-none p-3 text-center fs-16"></div>
                        <div id="search-content" class="text-left"></div>
                    </div>
                </div>

                <div class="kaniz-header-actions">
                    @auth
                        <a href="{{ route('dashboard') }}" class="kaniz-action-link">
                            <i class="lar la-user"></i>
                            <span>{{ \Illuminate\Support\Str::limit(Auth::user()->name, 12) }}</span>
                        </a>
                    @else
                        <a href="{{ route('user.login') }}" class="kaniz-action-link kaniz-action-auth">
                            <i class="lar la-user"></i>
                            <span>Sign in<br><strong>Join free</strong></span>
                        </a>
                    @endauth

                    <a href="{{ route('conversations.index') }}" class="kaniz-action-link">
                        <i class="lar la-comment-alt"></i>
                        <span>Messages</span>
                    </a>

                    <a href="{{ route('purchase_history.index') }}" class="kaniz-action-link">
                        <i class="las la-clipboard-list"></i>
                        <span>Orders</span>
                    </a>

                    <div id="cart_items" class="kaniz-cart-box">
                        @include('frontend.partials.cart.cart')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="kaniz-header-bottom">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="kaniz-header-bottom-inner">
                <a href="{{ route('categories.all') }}" class="kaniz-bottom-link kaniz-bottom-categories">
                    <i class="las la-bars"></i>
                    <span>All categories</span>
                </a>

                <nav class="kaniz-bottom-nav">
                    <a href="{{ $headerMenus->get(0)['link'] ?? route('home') }}" class="kaniz-bottom-link">Featured selections</a>
                    <a href="{{ $headerMenus->get(1)['link'] ?? route('home') }}" class="kaniz-bottom-link">Trade Assurance</a>
                    <a href="{{ route('buyer.portal') }}" class="kaniz-bottom-link">Buyer Central</a>
                    <a href="{{ route('b2b.portal.become-supplier') }}" class="kaniz-bottom-link">Sell on Kaniz Global Trade</a>
                    <a href="{{ $headerMenus->get(2)['link'] ?? route('home') }}" class="kaniz-bottom-link">Help</a>
                </nav>
            </div>
        </div>
    </div>
</header>

@if ($headerImageSearchEnabled)
    @include('frontend.enterprise_search.partials.image_search_modal')
@endif

@once
    <style>
        .kaniz-header-wrap {
            background: #fff;
            border-top: 1px solid #1f1f1f;
            border-bottom: 1px solid #ececec;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.04);
        }

        .kaniz-header-top {
            border-bottom: 1px solid #efefef;
            background: #fff;
        }

        .kaniz-header-top-inner,
        .kaniz-header-main-inner,
        .kaniz-header-bottom-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .kaniz-header-top-inner {
            min-height: 40px;
            font-size: 12px;
            color: #4b5563;
        }

        .kaniz-header-top-group {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .kaniz-top-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #4b5563;
            text-decoration: none;
            white-space: nowrap;
        }

        .kaniz-top-link:hover {
            color: #ff6a00;
            text-decoration: none;
        }

        .kaniz-top-link.dropdown-toggle::after {
            margin-left: 2px;
            vertical-align: middle;
        }

        .kaniz-top-dropdown {
            min-width: 260px;
            padding: 10px 0;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
        }

        .kaniz-top-dropdown-wide {
            min-width: 420px;
        }

        .kaniz-top-dropdown-search {
            position: relative;
            padding: 0 12px 10px;
        }

        .kaniz-top-dropdown-search i {
            position: absolute;
            top: 50%;
            left: 24px;
            transform: translateY(calc(-50% - 5px));
            color: #9ca3af;
            font-size: 15px;
        }

        .kaniz-top-dropdown-search input {
            width: 100%;
            height: 38px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            outline: 0;
            padding: 0 14px 0 36px;
            font-size: 13px;
        }

        .kaniz-top-dropdown-title {
            padding: 6px 16px 8px;
            color: #9ca3af;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .kaniz-top-dropdown-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .kaniz-top-dropdown-column + .kaniz-top-dropdown-column {
            border-left: 1px solid #f3f4f6;
        }

        .kaniz-top-dropdown-list {
            max-height: 240px;
            overflow-y: auto;
        }

        .kaniz-top-dropdown .kaniz-dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            color: #374151 !important;
            font-size: 13px;
            background: #fff !important;
        }

        .kaniz-top-dropdown .kaniz-dropdown-item.active,
        .kaniz-top-dropdown .kaniz-dropdown-item:hover,
        .kaniz-top-dropdown .kaniz-dropdown-item:focus {
            background: #fff7ed !important;
            color: #ea580c !important;
        }

        .kaniz-top-dropdown .kaniz-dropdown-item.active span,
        .kaniz-top-dropdown .kaniz-dropdown-item:hover span,
        .kaniz-top-dropdown .kaniz-dropdown-item:focus span {
            color: inherit !important;
        }

        .kaniz-top-dropdown .kaniz-dropdown-item img {
            flex: 0 0 auto;
            opacity: 1;
        }

        .kaniz-flag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 12px;
            padding: 0 3px;
            border-radius: 2px;
            background: linear-gradient(180deg, #b22234 0 50%, #3c3b6e 50% 100%);
            color: #fff;
            font-size: 8px;
            font-weight: 700;
        }

        .kaniz-header-main {
            background: #fff;
        }

        .kaniz-header-main-inner {
            padding: 18px 0 16px;
        }

        .kaniz-header-logo-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 0 0 auto;
        }

        .kaniz-mobile-menu {
            padding: 0;
            color: #222;
            font-size: 28px;
            line-height: 1;
            box-shadow: none !important;
        }

        .kaniz-logo-link img {
            display: block;
            max-width: 250px;
            max-height: 64px;
            width: auto;
            height: auto;
        }

        .kaniz-header-search {
            position: relative;
            flex: 1 1 auto;
            max-width: 760px;
        }

        .kaniz-search-shell {
            display: flex;
            align-items: stretch;
            width: 100%;
            min-height: 52px;
            background: #fff;
            border: 2px solid #ff6a00;
            border-radius: 14px;
            overflow: hidden;
        }

        .kaniz-search-category {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 0;
            border-right: 1px solid #ededed;
            background: #fff;
            color: #1f2937;
            font-size: 14px;
            font-weight: 600;
            padding: 0 18px;
            white-space: nowrap;
        }

        .kaniz-search-input-wrap {
            position: relative;
            flex: 1 1 auto;
            display: flex;
            align-items: center;
        }

        .kaniz-search-input-wrap input {
            width: 100%;
            height: 100%;
            border: 0;
            outline: 0;
            padding: 0 54px 0 18px;
            font-size: 14px;
            color: #111827;
        }

        .kaniz-search-input-wrap input::placeholder {
            color: #9ca3af;
        }

        .kaniz-search-submit {
            border: 0;
            background: linear-gradient(180deg, #ff7a14 0%, #ff5a00 100%);
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            padding: 0 34px;
            min-width: 120px;
        }

        .kaniz-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }

        .kaniz-action-link,
        .kaniz-cart-box > a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #1f2937;
            text-decoration: none;
            min-height: 48px;
            padding: 0 8px;
            font-size: 13px;
            line-height: 1.2;
            white-space: nowrap;
        }

        .kaniz-action-link i {
            font-size: 26px;
            color: #374151;
        }

        .kaniz-action-link:hover,
        .kaniz-cart-box > a:hover {
            color: #ff6a00;
            text-decoration: none;
        }

        .kaniz-action-auth strong {
            font-weight: 700;
        }

        .kaniz-cart-box {
            position: relative;
        }

        .kaniz-cart-box > a {
            padding-right: 0;
        }

        .kaniz-cart-box .dropdown-menu {
            margin-top: 6px;
        }

        .kaniz-cart-box .nav-box-text,
        .kaniz-cart-box .cart-count {
            color: #1f2937 !important;
        }

        .kaniz-cart-box .bottom-text-color-visibility,
        .kaniz-cart-box .middle-text-color-visibility,
        .kaniz-cart-box .top-text-color-visibility {
            color: #1f2937 !important;
        }

        .kaniz-header-bottom {
            border-top: 1px solid #f3f4f6;
            background: #fff;
        }

        .kaniz-header-bottom-inner {
            min-height: 48px;
            justify-content: flex-start;
            gap: 22px;
        }

        .kaniz-bottom-nav {
            display: flex;
            align-items: center;
            gap: 26px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .kaniz-bottom-nav::-webkit-scrollbar {
            display: none;
        }

        .kaniz-bottom-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .kaniz-bottom-link:hover {
            color: #ff6a00;
            text-decoration: none;
        }

        .kaniz-bottom-categories {
            min-width: 160px;
        }

        .header-global-search-image {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #6b7280;
            font-size: 22px;
            padding: 0;
            z-index: 2;
        }

        .header-global-search-image:hover {
            color: #ff6a00;
        }

        @media (max-width: 1199.98px) {
            .kaniz-header-top {
                display: none;
            }

            .kaniz-header-main-inner {
                flex-wrap: wrap;
            }

            .kaniz-header-search {
                order: 3;
                width: 100%;
                max-width: none;
            }

            .kaniz-header-actions {
                margin-left: auto;
            }

            .kaniz-action-link span,
            .kaniz-cart-box .nav-box-text,
            .kaniz-cart-box .d-xl-block {
                display: none !important;
            }
        }

        @media (max-width: 767.98px) {
            .kaniz-logo-link img {
                max-width: 170px;
                max-height: 46px;
            }

            .kaniz-search-shell {
                min-height: 46px;
                border-radius: 12px;
            }

            .kaniz-search-category {
                display: none;
            }

            .kaniz-search-submit {
                min-width: 92px;
                padding: 0 18px;
                font-size: 14px;
            }

            .kaniz-header-bottom-inner {
                gap: 14px;
            }

            .kaniz-bottom-categories {
                min-width: auto;
            }

            .kaniz-top-dropdown-wide {
                min-width: 300px;
            }

            .kaniz-top-dropdown-columns {
                grid-template-columns: 1fr;
            }

            .kaniz-top-dropdown-column + .kaniz-top-dropdown-column {
                border-left: 0;
                border-top: 1px solid #f3f4f6;
            }
        }
    </style>
    <script>
        (function () {
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('header-global-search-form');
                var searchInput = document.getElementById('search');
                var scopeInput = document.getElementById('header-global-search-scope');
                var countryInput = document.getElementById('header-global-search-country');
                var imageForm = document.getElementById('global-search-image-form');
                var imageFeedback = document.getElementById('global-search-image-feedback');
                var dropdownFilters = document.querySelectorAll('.kaniz-dropdown-filter');
                var csrfToken = document.querySelector('meta[name="csrf-token"]');

                if (!form || !searchInput || !scopeInput) {
                    return;
                }

                dropdownFilters.forEach(function (filterInput) {
                    filterInput.addEventListener('input', function () {
                        var query = filterInput.value.trim().toLowerCase();
                        var dropdown = filterInput.closest('.kaniz-top-dropdown');

                        if (!dropdown) {
                            return;
                        }

                        dropdown.querySelectorAll('.kaniz-dropdown-item').forEach(function (item) {
                            var text = item.textContent.trim().toLowerCase();
                            item.style.display = text.indexOf(query) !== -1 ? '' : 'none';
                        });
                    });
                });

                document.querySelectorAll('[data-flag]').forEach(function (item) {
                    item.addEventListener('click', function (event) {
                        event.preventDefault();

                        fetch('{{ route('language.change') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                locale: item.getAttribute('data-flag')
                            })
                        }).then(function () {
                            window.location.reload();
                        });
                    });
                });

                document.querySelectorAll('[data-currency]').forEach(function (item) {
                    item.addEventListener('click', function (event) {
                        event.preventDefault();

                        fetch('{{ route('currency.change') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                currency_code: item.getAttribute('data-currency')
                            })
                        }).then(function () {
                            window.location.reload();
                        });
                    });
                });

                document.querySelectorAll('.kaniz-country-option').forEach(function (item) {
                    item.addEventListener('click', function (event) {
                        event.preventDefault();

                        var dropdown = item.closest('.dropdown');
                        if (!dropdown) {
                            return;
                        }
                        fetch('{{ route('delivery.country.change') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                country_id: item.getAttribute('data-country-id')
                            })
                        }).then(function () {
                            window.location.reload();
                        });
                    });
                });

                scopeInput.value = scopeInput.value || 'ai_mode';

                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    var query = searchInput.value.trim();
                    var target = new URL('{{ route('global.search') }}', window.location.origin);

                    if (query !== '') {
                        target.searchParams.set('q', query);
                    }

                    target.searchParams.set('scope', scopeInput.value || 'ai_mode');
                    if (countryInput && countryInput.value) {
                        target.searchParams.set('country', countryInput.value);
                    }
                    window.location.href = target.toString();
                });

                if (imageForm && imageFeedback) {
                    imageForm.addEventListener('submit', function (event) {
                        event.preventDefault();
                        imageFeedback.className = 'mt-3';
                        imageFeedback.innerHTML = '<div class="alert alert-info mb-0">{{ translate('Analyzing image...') }}</div>';

                        fetch(imageForm.action, {
                            method: 'POST',
                            body: new FormData(imageForm),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, data: data };
                            });
                        }).then(function (payload) {
                            if (!payload.ok) {
                                throw new Error(payload.data.message || '{{ translate('Image search failed.') }}');
                            }

                            var target = new URL('{{ route('global.search') }}', window.location.origin);
                            target.searchParams.set('scope', 'products');

                            if (payload.data.query) {
                                target.searchParams.set('q', payload.data.query);
                            }

                            window.location.href = target.toString();
                        }).catch(function (error) {
                            imageFeedback.className = 'mt-3';
                            imageFeedback.innerHTML = '<div class="alert alert-danger mb-0">' + error.message + '</div>';
                        });
                    });
                }
            });
        })();
    </script>
@endonce
