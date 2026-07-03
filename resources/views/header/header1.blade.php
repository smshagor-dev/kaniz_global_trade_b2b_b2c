@php
    $headerSearchScope = request('scope', 'products');
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
    $compareCount = Session::has('compare') ? count(Session::get('compare')) : 0;
    $wishlistCount = Auth::check() ? get_wishlists()->count() : 0;
    $notificationCount = Auth::check() ? Auth::user()->unreadNotifications->count() : 0;
    $dashboardRoute = isAdmin() ? route('admin.dashboard') : route('dashboard');
    $headerSearchScopes = collect([
        ['value' => 'products', 'label' => translate('Products')],
        ['value' => 'ai_mode', 'label' => translate('AI Search')],
        ['value' => 'buyer', 'label' => translate('Buyer')],
        ['value' => 'importer', 'label' => translate('Importer')],
        ['value' => 'retailer', 'label' => translate('Retailer')],
        ['value' => 'supplier', 'label' => translate('Supplier')],
        ['value' => 'manufacturer', 'label' => translate('Manufacturer')],
        ['value' => 'distributor', 'label' => translate('Distributor')],
        ['value' => 'wholesaler', 'label' => translate('Wholesaler')],
        ['value' => 'exporter', 'label' => translate('Exporter')],
        ['value' => 'worldwide', 'label' => translate('Worldwide')],
    ])->when(get_setting('enable_global_search_ai_mode', '1') !== '1', function ($items) {
        return $items->reject(fn ($item) => $item['value'] === 'ai_mode')->values();
    });
    $activeHeaderSearchScope = $headerSearchScopes->firstWhere('value', $headerSearchScope) ?? $headerSearchScopes->first();
    $headerSearchScope = $activeHeaderSearchScope['value'] ?? 'products';
    $headerSearchScopeLabel = $activeHeaderSearchScope['label'] ?? translate('Products');
@endphp

<header class="kaniz-header-wrap">
    <div class="kaniz-header-top">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="kaniz-header-top-inner">
                <div class="kaniz-header-top-group">
                    <div class="dropdown" id="country-deliver-change">
                        <a href="javascript:void(0)" class="kaniz-top-link dropdown-toggle" data-toggle="dropdown"
                            data-display="static" aria-expanded="false">
                            <span>Deliver from:</span>
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
                    <a href="{{ route('custom-pages.show_custom_page', 'contact-us') }}" class="kaniz-top-link">
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
                            <div class="dropdown kaniz-search-category-dropdown">
                                <button type="button" class="kaniz-search-category" data-toggle="dropdown" aria-expanded="false">
                                    <span id="header-search-scope-label">{{ $headerSearchScopeLabel }}</span>
                                    <i class="las la-angle-down"></i>
                                </button>
                                <div class="dropdown-menu kaniz-search-scope-menu">
                                    @foreach ($headerSearchScopes as $scopeOption)
                                        <button type="button"
                                            class="dropdown-item kaniz-search-scope-item @if ($headerSearchScope === $scopeOption['value']) active @endif"
                                            data-scope="{{ $scopeOption['value'] }}"
                                            data-label="{{ $scopeOption['label'] }}">
                                            {{ $scopeOption['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
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
                    <a href="{{ route('compare') }}" class="kaniz-action-link kaniz-action-icon" title="{{ translate('Compare') }}">
                        <span class="kaniz-action-icon-wrap">
                            <i class="las la-exchange-alt"></i>
                            @if ($compareCount > 0)
                                <span class="kaniz-action-badge">{{ $compareCount }}</span>
                            @endif
                        </span>
                        <span>{{ translate('Compare') }}</span>
                    </a>

                    <a href="{{ route('wishlists.index') }}" class="kaniz-action-link kaniz-action-icon" title="{{ translate('Favorite') }}">
                        <span class="kaniz-action-icon-wrap">
                            <i class="lar la-heart"></i>
                            @if ($wishlistCount > 0)
                                <span class="kaniz-action-badge">{{ $wishlistCount }}</span>
                            @endif
                        </span>
                        <span>{{ translate('Favorite') }}</span>
                    </a>

                    @auth
                        <div class="dropdown" data-kaniz-hover-dropdown>
                            <a href="{{ route('customer.all-notifications') }}" class="kaniz-action-link kaniz-action-icon"
                                data-toggle="dropdown" aria-expanded="false" title="{{ translate('Notifications') }}">
                                <span class="kaniz-action-icon-wrap">
                                    <i class="lar la-bell"></i>
                                    @if ($notificationCount > 0)
                                        <span class="kaniz-action-badge">{{ $notificationCount }}</span>
                                    @endif
                                </span>
                                <span>{{ translate('Notifications') }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right kaniz-profile-menu kaniz-notification-menu">
                                <div class="kaniz-notification-menu-title">{{ translate('Notifications') }}</div>
                                <div class="kaniz-notification-list">
                                    @forelse (Auth::user()->unreadNotifications->take(6) as $notification)
                                        @php
                                            $notificationText = $notification->data['message'] ?? $notification->data['title'] ?? null;
                                            if (!$notificationText && !empty($notification->data['order_code'])) {
                                                $notificationText = translate('Order') . ' #' . $notification->data['order_code'];
                                            }
                                            if (!$notificationText && !empty($notification->data['tracking_code'])) {
                                                $notificationText = translate('Tracking code') . ': ' . $notification->data['tracking_code'];
                                            }
                                            if (!$notificationText) {
                                                $notificationText = class_basename($notification->type);
                                            }
                                        @endphp
                                        <a href="{{ route('notification.read-and-redirect', encrypt($notification->id)) }}"
                                            class="dropdown-item kaniz-profile-item kaniz-notification-item">
                                            <i class="lar la-bell"></i>
                                            <span>{{ $notificationText }}</span>
                                        </a>
                                    @empty
                                        <div class="kaniz-notification-empty">{{ translate('No notification found') }}</div>
                                    @endforelse
                                </div>
                                <a href="{{ route('customer.all-notifications') }}"
                                    class="dropdown-item kaniz-profile-item kaniz-notification-footer">
                                    <i class="las la-arrow-right"></i>
                                    <span>{{ translate('View All Notifications') }}</span>
                                </a>
                            </div>
                        </div>

                        <div class="dropdown" data-kaniz-hover-dropdown>
                            <a href="{{ $dashboardRoute }}" class="kaniz-action-link kaniz-action-icon" data-toggle="dropdown"
                                aria-expanded="false" title="{{ translate('Profile') }}">
                                <span class="kaniz-action-icon-wrap">
                                    <i class="lar la-user"></i>
                                </span>
                                <span>{{ translate('Profile') }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right kaniz-profile-menu">
                                <a href="{{ $dashboardRoute }}" class="dropdown-item kaniz-profile-item">
                                    <i class="las la-th-large"></i>
                                    <span>{{ translate('Dashboard') }}</span>
                                </a>
                                <a href="{{ route('conversations.index') }}" class="dropdown-item kaniz-profile-item">
                                    <i class="lar la-comment-alt"></i>
                                    <span>{{ translate('Message') }}</span>
                                </a>
                                <a href="{{ route('purchase_history.index') }}" class="dropdown-item kaniz-profile-item">
                                    <i class="las la-clipboard-list"></i>
                                    <span>{{ translate('Order') }}</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('user.login') }}" class="kaniz-action-link kaniz-action-icon" title="{{ translate('Profile') }}">
                            <span class="kaniz-action-icon-wrap">
                                <i class="lar la-user"></i>
                            </span>
                            <span>{{ translate('Profile') }}</span>
                        </a>
                    @endauth

                    <div id="cart_items" class="kaniz-cart-box" data-kaniz-hover-dropdown>
                        @include('frontend.partials.cart.cart')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="kaniz-header-bottom">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="kaniz-header-bottom-inner">
                <nav class="kaniz-bottom-nav">
                    <a href="{{ $headerMenus->get(0)['link'] ?? route('home') }}" class="kaniz-bottom-link">Featured selections</a>
                    <a href="{{ $headerMenus->get(1)['link'] ?? route('home') }}" class="kaniz-bottom-link">Trade Assurance</a>
                    <a href="{{ route('buyer.portal') }}" class="kaniz-bottom-link">Buyer Central</a>
                    <a href="{{ route('seller.login') }}" class="kaniz-bottom-link">Seller</a>
                    <a href="{{ route('shops.create') }}" class="kaniz-bottom-link">Sell on Kaniz Global Trade</a>
                    <a href="{{ route('custom-pages.show_custom_page', 'contact-us') }}" class="kaniz-bottom-link">Help</a>
                </nav>
            </div>
        </div>
    </div>
</header>

<div class="kaniz-header-sticky" id="kaniz-header-sticky" aria-hidden="true">
    <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
        <div class="kaniz-header-sticky-inner">
            <div class="kaniz-header-logo-group kaniz-header-logo-group-sticky">
                <a class="kaniz-logo-link" href="{{ route('home') }}">
                    @if ($header_logo != null)
                        <img src="{{ uploaded_asset($header_logo) }}" alt="{{ env('APP_NAME') }}">
                    @else
                        <img src="{{ static_asset('assets/img/logo.png') }}" alt="{{ env('APP_NAME') }}">
                    @endif
                </a>
            </div>

            <div class="kaniz-header-search kaniz-header-search-sticky">
                <form action="{{ route('search') }}" method="GET" class="stop-propagation"
                    data-kaniz-search-form>
                    <input type="hidden" name="scope" data-kaniz-search-scope value="{{ $headerSearchScope }}">
                    <input type="hidden" name="country" data-kaniz-search-country value="{{ $selectedCountry['name'] }}">
                    <div class="kaniz-search-shell kaniz-search-shell-sticky">
                        <div class="dropdown kaniz-search-category-dropdown">
                            <button type="button" class="kaniz-search-category" data-toggle="dropdown" aria-expanded="false">
                                <span data-kaniz-search-scope-label>{{ $headerSearchScopeLabel }}</span>
                                <i class="las la-angle-down"></i>
                            </button>
                            <div class="dropdown-menu kaniz-search-scope-menu">
                                @foreach ($headerSearchScopes as $scopeOption)
                                    <button type="button"
                                        class="dropdown-item kaniz-search-scope-item @if ($headerSearchScope === $scopeOption['value']) active @endif"
                                        data-scope="{{ $scopeOption['value'] }}"
                                        data-label="{{ $scopeOption['label'] }}">
                                        {{ $scopeOption['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="kaniz-search-input-wrap">
                            <input type="text" name="keyword" value="{{ $headerSearchQuery }}"
                                placeholder="What are you looking for..." autocomplete="off"
                                data-kaniz-search-input>
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
                    style="min-height: 200px" data-kaniz-typed-search-box>
                    <div class="search-preloader absolute-top-center">
                        <div class="dot-loader">
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                    <div class="search-nothing d-none p-3 text-center fs-16" data-kaniz-search-nothing></div>
                    <div class="text-left" data-kaniz-search-content></div>
                </div>
            </div>

            <div class="kaniz-header-actions kaniz-header-actions-sticky">
                <a href="{{ route('compare') }}" class="kaniz-action-link kaniz-action-icon" title="{{ translate('Compare') }}">
                    <span class="kaniz-action-icon-wrap">
                        <i class="las la-exchange-alt"></i>
                        @if ($compareCount > 0)
                            <span class="kaniz-action-badge">{{ $compareCount }}</span>
                        @endif
                    </span>
                    <span>{{ translate('Compare') }}</span>
                </a>

                <a href="{{ route('wishlists.index') }}" class="kaniz-action-link kaniz-action-icon" title="{{ translate('Favorite') }}">
                    <span class="kaniz-action-icon-wrap">
                        <i class="lar la-heart"></i>
                        @if ($wishlistCount > 0)
                            <span class="kaniz-action-badge">{{ $wishlistCount }}</span>
                        @endif
                    </span>
                    <span>{{ translate('Favorite') }}</span>
                </a>

                @auth
                    <div class="dropdown" data-kaniz-hover-dropdown>
                        <a href="{{ route('customer.all-notifications') }}" class="kaniz-action-link kaniz-action-icon"
                            data-toggle="dropdown" aria-expanded="false" title="{{ translate('Notifications') }}">
                            <span class="kaniz-action-icon-wrap">
                                <i class="lar la-bell"></i>
                                @if ($notificationCount > 0)
                                    <span class="kaniz-action-badge">{{ $notificationCount }}</span>
                                @endif
                            </span>
                            <span>{{ translate('Notifications') }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right kaniz-profile-menu kaniz-notification-menu">
                            <div class="kaniz-notification-menu-title">{{ translate('Notifications') }}</div>
                            <div class="kaniz-notification-list">
                                @forelse (Auth::user()->unreadNotifications->take(6) as $notification)
                                    @php
                                        $notificationText = $notification->data['message'] ?? $notification->data['title'] ?? null;
                                        if (!$notificationText && !empty($notification->data['order_code'])) {
                                            $notificationText = translate('Order') . ' #' . $notification->data['order_code'];
                                        }
                                        if (!$notificationText && !empty($notification->data['tracking_code'])) {
                                            $notificationText = translate('Tracking code') . ': ' . $notification->data['tracking_code'];
                                        }
                                        if (!$notificationText) {
                                            $notificationText = class_basename($notification->type);
                                        }
                                    @endphp
                                    <a href="{{ route('notification.read-and-redirect', encrypt($notification->id)) }}"
                                        class="dropdown-item kaniz-profile-item kaniz-notification-item">
                                        <i class="lar la-bell"></i>
                                        <span>{{ $notificationText }}</span>
                                    </a>
                                @empty
                                    <div class="kaniz-notification-empty">{{ translate('No notification found') }}</div>
                                @endforelse
                            </div>
                            <a href="{{ route('customer.all-notifications') }}"
                                class="dropdown-item kaniz-profile-item kaniz-notification-footer">
                                <i class="las la-arrow-right"></i>
                                <span>{{ translate('View All Notifications') }}</span>
                            </a>
                        </div>
                    </div>

                    <div class="dropdown" data-kaniz-hover-dropdown>
                        <a href="{{ $dashboardRoute }}" class="kaniz-action-link kaniz-action-icon" data-toggle="dropdown"
                            aria-expanded="false" title="{{ translate('Profile') }}">
                            <span class="kaniz-action-icon-wrap">
                                <i class="lar la-user"></i>
                            </span>
                            <span>{{ translate('Profile') }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right kaniz-profile-menu">
                            <a href="{{ $dashboardRoute }}" class="dropdown-item kaniz-profile-item">
                                <i class="las la-th-large"></i>
                                <span>{{ translate('Dashboard') }}</span>
                            </a>
                            <a href="{{ route('conversations.index') }}" class="dropdown-item kaniz-profile-item">
                                <i class="lar la-comment-alt"></i>
                                <span>{{ translate('Message') }}</span>
                            </a>
                            <a href="{{ route('purchase_history.index') }}" class="dropdown-item kaniz-profile-item">
                                <i class="las la-clipboard-list"></i>
                                <span>{{ translate('Order') }}</span>
                            </a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('user.login') }}" class="kaniz-action-link kaniz-action-icon" title="{{ translate('Profile') }}">
                        <span class="kaniz-action-icon-wrap">
                            <i class="lar la-user"></i>
                        </span>
                        <span>{{ translate('Profile') }}</span>
                    </a>
                @endauth

                <div class="kaniz-cart-box" data-kaniz-hover-dropdown>
                    @include('frontend.partials.cart.cart')
                </div>
            </div>
        </div>
    </div>
</div>

@if ($headerImageSearchEnabled)
    @include('frontend.enterprise_search.partials.image_search_modal')
@endif

@once
    <style>
        .kaniz-header-wrap {
            position: relative;
            z-index: 1100;
            overflow: visible;
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
            overflow: visible;
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
            z-index: 3300;
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
            position: relative;
            z-index: 2;
            box-shadow: none;
        }

        .kaniz-header-main-inner {
            padding: 18px 0 16px;
        }

        .kaniz-header-sticky {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1500;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid #ececec;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
            backdrop-filter: blur(10px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-100%);
            transition: transform 0.32s ease, opacity 0.32s ease, visibility 0.32s ease;
        }

        body.kaniz-sticky-header-visible .kaniz-header-sticky {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0);
        }

        .kaniz-header-sticky-inner {
            display: flex;
            align-items: center;
            gap: 18px;
            min-height: 76px;
            padding: 10px 0;
        }

        .kaniz-header-logo-group-sticky .kaniz-logo-link img {
            max-width: 190px;
            max-height: 48px;
        }

        .kaniz-header-search-sticky {
            max-width: none;
        }

        .kaniz-search-shell-sticky {
            min-height: 46px;
            border-radius: 12px;
        }

        .kaniz-search-shell-sticky .kaniz-search-category,
        .kaniz-search-shell-sticky .kaniz-search-input-wrap,
        .kaniz-search-shell-sticky .kaniz-search-submit {
            min-height: 44px;
        }

        .kaniz-search-shell-sticky .kaniz-search-submit {
            min-width: 108px;
            padding: 0 24px;
            font-size: 15px;
        }

        .kaniz-header-actions-sticky .kaniz-action-link,
        .kaniz-header-actions-sticky .kaniz-cart-box > a {
            min-height: 48px;
            min-width: 44px;
            padding: 0 4px;
        }

        .kaniz-header-actions-sticky {
            margin-left: auto;
            gap: 4px;
        }

        .kaniz-header-actions-sticky .kaniz-action-link > span:last-child,
        .kaniz-header-actions-sticky .kaniz-cart-box .nav-box-text,
        .kaniz-header-actions-sticky .kaniz-cart-box .d-xl-block {
            display: none !important;
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
            z-index: 1200;
            flex: 1 1 auto;
            max-width: 760px;
        }

        .kaniz-search-shell {
            display: flex;
            align-items: stretch;
            position: relative;
            width: 100%;
            min-height: 52px;
            padding: 3px;
            background: linear-gradient(135deg, #fffdfb 0%, #ffffff 58%, #fff7ef 100%);
            border: 1px solid rgba(255, 106, 0, 0.18);
            border-radius: 18px;
            overflow: visible;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08), 0 4px 12px rgba(255, 106, 0, 0.08);
            isolation: isolate;
            transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        }

        .kaniz-search-shell::before {
            content: "";
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(120deg, rgba(255, 106, 0, 0.22) 0%, rgba(255, 255, 255, 0.3) 24%, rgba(255, 106, 0, 0.9) 50%, rgba(255, 255, 255, 0.32) 76%, rgba(255, 106, 0, 0.22) 100%);
            background-size: 220% 100%;
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            animation: kanizSearchBorderFlow 4.8s linear infinite;
            pointer-events: none;
            opacity: 0.95;
        }

        .kaniz-search-shell::after {
            content: "";
            position: absolute;
            inset: 3px;
            border-radius: calc(18px - 4px);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.72), rgba(255, 255, 255, 0));
            pointer-events: none;
            z-index: -1;
        }

        .kaniz-search-shell:hover,
        .kaniz-search-shell:focus-within {
            transform: translateY(-1px);
            border-color: rgba(255, 106, 0, 0.28);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.12), 0 8px 20px rgba(255, 106, 0, 0.12);
        }

        @keyframes kanizSearchBorderFlow {
            0% {
                background-position: 200% 50%;
            }
            100% {
                background-position: -20% 50%;
            }
        }

        .kaniz-search-category-dropdown {
            flex: 0 0 168px;
            align-self: stretch;
        }

        .kaniz-search-category {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border: 0;
            border-right: 1px solid rgba(226, 232, 240, 0.9);
            background: rgba(255, 255, 255, 0.88);
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            width: 100%;
            height: 100%;
            min-height: 50px;
            padding: 0 18px 0 20px;
            white-space: nowrap;
            letter-spacing: 0.01em;
            border-radius: 14px 0 0 14px;
        }

        .kaniz-search-category i {
            color: #94a3b8;
            font-size: 16px;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .kaniz-search-category:hover i,
        .kaniz-search-category:focus i {
            color: #ea580c;
            transform: translateY(1px);
        }

        .kaniz-search-category:focus,
        .kaniz-search-category:focus-visible {
            outline: 0;
        }

        .kaniz-search-category-dropdown .dropdown-menu {
            margin-top: 8px;
            z-index: 3200;
        }

        .kaniz-search-scope-menu {
            min-width: 210px;
            padding: 8px;
            border: 1px solid rgba(255, 106, 0, 0.12);
            border-radius: 16px;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.14);
            z-index: 3020;
            max-width: min(320px, calc(100vw - 24px));
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(14px);
        }

        .kaniz-search-scope-item {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            padding: 11px 14px;
            background: transparent !important;
            border-radius: 12px;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .kaniz-search-scope-item.active,
        .kaniz-search-scope-item:hover,
        .kaniz-search-scope-item:focus {
            background: #fff7ed !important;
            color: #ea580c !important;
            transform: translateX(2px);
        }

        .kaniz-search-input-wrap {
            position: relative;
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            min-height: 50px;
            background: rgba(255, 255, 255, 0.78);
        }

        .kaniz-search-input-wrap input {
            width: 100%;
            height: 100%;
            border: 0;
            outline: 0;
            padding: 0 58px 0 20px;
            font-size: 14px;
            font-weight: 500;
            color: #0f172a;
            background: transparent;
        }

        .kaniz-search-input-wrap input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .kaniz-search-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            align-self: stretch;
            border: 0;
            background: linear-gradient(135deg, #ff8a1f 0%, #ff6a00 55%, #ef5b00 100%);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.01em;
            padding: 0 30px;
            min-width: 116px;
            min-height: 50px;
            border-radius: 14px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.28), 0 10px 18px rgba(255, 106, 0, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
            position: relative;
            z-index: 1;
        }

        .kaniz-search-submit:hover,
        .kaniz-search-submit:focus {
            color: #fff;
            filter: brightness(1.03);
            transform: translateY(-1px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.34), 0 14px 24px rgba(255, 106, 0, 0.28);
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
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: #1f2937;
            text-decoration: none;
            min-height: 56px;
            min-width: 52px;
            padding: 0 6px;
            font-size: 11px;
            line-height: 1.2;
            white-space: nowrap;
        }

        .kaniz-action-link i {
            font-size: 22px;
            color: #374151;
        }

        .kaniz-action-link:hover,
        .kaniz-cart-box > a:hover {
            color: #ff6a00;
            text-decoration: none;
        }

        .kaniz-action-icon-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #fff;
            transition: all 0.2s ease;
        }

        .kaniz-action-link:hover .kaniz-action-icon-wrap,
        .kaniz-cart-box > a:hover .kaniz-action-icon-wrap {
            border-color: #ff6a00;
            background: #fff7ed;
        }

        .kaniz-action-badge {
            position: absolute;
            top: -4px;
            right: -6px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 999px;
            background: #ff5a00;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            line-height: 18px;
            text-align: center;
        }

        .kaniz-profile-menu {
            min-width: 180px;
            margin-top: 10px;
            padding: 8px 0;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
        }

        @media (min-width: 1200px) {
            [data-kaniz-hover-dropdown] > .dropdown-menu {
                display: block;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transform: translateY(10px);
                transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
            }

            [data-kaniz-hover-dropdown].show > .dropdown-menu,
            [data-kaniz-hover-dropdown]:hover > .dropdown-menu {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
                transform: translateY(0);
            }
        }

        .kaniz-profile-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            color: #1f2937;
            font-size: 13px;
            font-weight: 600;
        }

        .kaniz-profile-item i {
            font-size: 18px;
            color: #6b7280;
        }

        .kaniz-profile-item:hover,
        .kaniz-profile-item:focus {
            background: #fff7ed;
            color: #ea580c;
        }

        .kaniz-profile-item:hover i,
        .kaniz-profile-item:focus i {
            color: #ea580c;
        }

        .kaniz-notification-menu {
            min-width: 320px;
            padding-top: 0;
            padding-bottom: 0;
        }

        .kaniz-notification-menu-title {
            padding: 14px 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        .kaniz-notification-list {
            max-height: 320px;
            overflow-y: auto;
        }

        .kaniz-notification-item {
            align-items: flex-start;
            white-space: normal;
        }

        .kaniz-notification-item span {
            line-height: 1.4;
            color: inherit;
        }

        .kaniz-notification-empty {
            padding: 18px 16px;
            color: #6b7280;
            font-size: 13px;
            text-align: center;
        }

        .kaniz-notification-footer {
            border-top: 1px solid #f3f4f6;
            border-radius: 0 0 14px 14px;
        }

        .kaniz-cart-box {
            position: relative;
        }

        .kaniz-cart-box > a {
            padding-right: 6px;
        }

        .kaniz-cart-box > a > span:first-child {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #fff;
            margin-right: 0 !important;
        }

        .kaniz-cart-box > a .cart-count {
            position: absolute;
            top: -4px;
            right: -6px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 999px;
            background: #ff5a00;
            color: #fff !important;
            font-size: 10px;
            font-weight: 700;
            line-height: 18px;
            text-align: center;
        }

        .kaniz-cart-box > a .nav-box-text {
            display: none !important;
        }

        .kaniz-cart-box > a > .d-none.d-xl-block {
            display: block !important;
            margin-left: 0 !important;
            font-size: 11px !important;
            font-weight: 600 !important;
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
            background: rgba(255, 247, 237, 0.88);
            color: #fb923c;
            font-size: 18px;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            padding: 0;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .header-global-search-image:hover {
            color: #ea580c;
            background: #ffedd5;
            transform: translateY(-50%) scale(1.04);
        }

        .kaniz-header-search .typed-search-box {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 10px);
            z-index: 3000;
            margin-top: 0;
            width: 100%;
            max-height: min(70vh, 560px);
            overflow-y: auto;
            padding: 10px;
            border: 1px solid rgba(255, 106, 0, 0.14);
            border-radius: 20px !important;
            background: rgba(255, 255, 255, 0.98) !important;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
            backdrop-filter: blur(16px);
        }

        .kaniz-header-search .typed-search-box .search-preloader {
            top: 18px;
        }

        .kaniz-header-search .typed-search-box .search-nothing {
            border-radius: 14px;
            background: #fff7ed;
            color: #c2410c;
            font-weight: 600;
        }

        .kaniz-header-search .typed-search-box a {
            display: block;
            margin-bottom: 6px;
            padding: 12px 14px !important;
            border-radius: 14px;
            border: 1px solid transparent;
            background: linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
            transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
        }

        .kaniz-header-search .typed-search-box a:last-child {
            margin-bottom: 0;
        }

        .kaniz-header-search .typed-search-box a:hover,
        .kaniz-header-search .typed-search-box a:focus {
            border-color: rgba(255, 106, 0, 0.16);
            background: #fff7ed;
            transform: translateY(-1px);
            text-decoration: none;
        }

        .kaniz-header-search .typed-search-box strong {
            display: block;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
        }

        .kaniz-header-search .typed-search-box .text-muted {
            color: #64748b !important;
        }

        @media (max-width: 1199.98px) {
            .kaniz-header-sticky {
                display: none;
            }

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
                margin-top: 12px;
            }

            .kaniz-header-actions {
                margin-left: auto;
            }

            .kaniz-action-link > span:last-child,
            .kaniz-cart-box .nav-box-text,
            .kaniz-cart-box .d-xl-block {
                display: none !important;
            }

            .kaniz-action-link,
            .kaniz-cart-box > a {
                min-width: 44px;
            }
        }

        @media (max-width: 767.98px) {
            .kaniz-logo-link img {
                max-width: 170px;
                max-height: 46px;
            }

            .kaniz-search-shell {
                flex-wrap: wrap;
                gap: 8px;
                min-height: 46px;
                padding: 8px;
                border-radius: 16px;
            }

            .kaniz-search-category {
                width: 100%;
                min-width: 0;
                min-height: 42px;
                justify-content: space-between;
                border-right: 0;
                border-bottom: 1px solid #e8e8e8;
                border-radius: 12px;
            }

            .kaniz-search-category-dropdown {
                width: 100%;
                flex-basis: 100%;
            }

            .kaniz-search-scope-menu {
                width: 100%;
                min-width: 0;
            }

            .kaniz-search-input-wrap {
                flex: 1 1 calc(100% - 108px);
                min-width: 0;
            }

            .kaniz-search-submit {
                min-width: 96px;
                min-height: 42px;
                padding: 0 18px;
                font-size: 14px;
                border-radius: 12px;
                align-self: stretch;
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

            .kaniz-header-search .typed-search-box {
                top: calc(100% + 8px);
                max-height: 60vh;
                border-radius: 16px;
            }
        }

        @media (max-width: 479.98px) {
            .kaniz-search-input-wrap {
                flex-basis: 100%;
            }

            .kaniz-search-submit {
                width: 100%;
            }
        }
    </style>
    <script>
        (function () {
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('header-global-search-form');
                var imageForm = document.getElementById('global-search-image-form');
                var imageFeedback = document.getElementById('global-search-image-feedback');
                var dropdownFilters = document.querySelectorAll('.kaniz-dropdown-filter');
                var csrfToken = document.querySelector('meta[name="csrf-token"]');
                var stickyHeader = document.getElementById('kaniz-header-sticky');
                var headerWrap = document.querySelector('.kaniz-header-wrap');
                var searchModules = [];
                var hoverDropdowns = document.querySelectorAll('[data-kaniz-hover-dropdown]');

                if (!form || !headerWrap) {
                    return;
                }

                document.querySelectorAll('[data-kaniz-search-form], #header-global-search-form').forEach(function (searchForm) {
                    var moduleSearchInput = searchForm.querySelector('[data-kaniz-search-input]') || searchForm.querySelector('#search');
                    var moduleScopeInput = searchForm.querySelector('[data-kaniz-search-scope]') || searchForm.querySelector('#header-global-search-scope');
                    var moduleScopeLabel = searchForm.querySelector('[data-kaniz-search-scope-label]') || searchForm.querySelector('#header-search-scope-label');
                    var moduleCountryInput = searchForm.querySelector('[data-kaniz-search-country]') || searchForm.querySelector('#header-global-search-country');
                    var moduleRoot = searchForm.closest('.kaniz-header-search');
                    var moduleTypedSearchBox = moduleRoot ? moduleRoot.querySelector('[data-kaniz-typed-search-box], .typed-search-box') : null;
                    var moduleSearchContent = moduleRoot ? moduleRoot.querySelector('[data-kaniz-search-content], #search-content') : null;
                    var moduleSearchNothing = moduleRoot ? moduleRoot.querySelector('[data-kaniz-search-nothing], .search-nothing') : null;
                    var moduleScopeItems = searchForm.querySelectorAll('.kaniz-search-scope-item');

                    if (!moduleSearchInput || !moduleScopeInput) {
                        return;
                    }

                    searchModules.push({
                        form: searchForm,
                        searchInput: moduleSearchInput,
                        scopeInput: moduleScopeInput,
                        scopeLabel: moduleScopeLabel,
                        countryInput: moduleCountryInput,
                        typedSearchBox: moduleTypedSearchBox,
                        searchContent: moduleSearchContent,
                        searchNothing: moduleSearchNothing,
                        scopeItems: moduleScopeItems,
                        searchTimer: null
                    });
                });

                if (!searchModules.length) {
                    return;
                }

                var hideSuggestions = function (module) {
                    if (module.typedSearchBox) {
                        module.typedSearchBox.classList.add('d-none');
                    }
                    if (module.searchContent) {
                        module.searchContent.innerHTML = '';
                    }
                    if (module.searchNothing) {
                        module.searchNothing.classList.add('d-none');
                    }
                };

                var showSuggestions = function (module) {
                    if (module.typedSearchBox) {
                        module.typedSearchBox.classList.remove('d-none');
                    }
                };

                var setActiveScope = function (module, scope, label) {
                    module.scopeInput.value = scope || 'products';
                    if (module.scopeLabel && label) {
                        module.scopeLabel.textContent = label;
                    }
                    module.scopeItems.forEach(function (item) {
                        item.classList.toggle('active', item.getAttribute('data-scope') === module.scopeInput.value);
                    });
                };

                var syncSearchModules = function (sourceModule) {
                    searchModules.forEach(function (module) {
                        if (module === sourceModule) {
                            return;
                        }

                        module.scopeInput.value = sourceModule.scopeInput.value;
                        if (module.scopeLabel && sourceModule.scopeLabel) {
                            module.scopeLabel.textContent = sourceModule.scopeLabel.textContent;
                        }
                        module.searchInput.value = sourceModule.searchInput.value;
                        if (module.countryInput && sourceModule.countryInput) {
                            module.countryInput.value = sourceModule.countryInput.value;
                        }
                        module.scopeItems.forEach(function (item) {
                            item.classList.toggle('active', item.getAttribute('data-scope') === module.scopeInput.value);
                        });
                    });
                };

                var closeDropdown = function (triggerElement) {
                    var dropdown = triggerElement ? triggerElement.closest('.dropdown') : null;
                    if (!dropdown) {
                        return;
                    }

                    dropdown.classList.remove('show');

                    var menu = dropdown.querySelector('.dropdown-menu');
                    if (menu) {
                        menu.classList.remove('show');
                    }

                    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dropdown) {
                        window.jQuery(dropdown).find('[data-toggle="dropdown"]').dropdown('hide');
                    }
                };

                var fetchSuggestions = function (module) {
                    window.clearTimeout(module.searchTimer);
                    var query = module.searchInput.value.trim();

                    if (query.length < 2) {
                        hideSuggestions(module);
                        return;
                    }

                    module.searchTimer = window.setTimeout(function () {
                        fetch('{{ route('global.search.autocomplete') }}?q=' + encodeURIComponent(query) + '&scope=' + encodeURIComponent(module.scopeInput.value || 'products'))
                            .then(function (response) { return response.json(); })
                            .then(function (data) {
                                var items = (data && data.suggestions) ? data.suggestions : [];

                                if (!items.length) {
                                    if (module.searchNothing) {
                                        module.searchNothing.textContent = '{{ translate('Nothing found') }}';
                                        module.searchNothing.classList.remove('d-none');
                                    }
                                    if (module.searchContent) {
                                        module.searchContent.innerHTML = '';
                                    }
                                    showSuggestions(module);
                                    return;
                                }

                                if (module.searchNothing) {
                                    module.searchNothing.classList.add('d-none');
                                }

                                if (module.searchContent) {
                                    module.searchContent.innerHTML = items.map(function (item) {
                                        var title = item.title || '';
                                        var subtitle = item.subtitle ? '<div class="text-muted fs-11 mt-1">' + item.subtitle + '</div>' : '';
                                        var href = item.url || ('{{ route('global.search') }}?q=' + encodeURIComponent(title) + '&scope=' + encodeURIComponent(module.scopeInput.value || 'products'));
                                        return '<a class="d-block px-3 py-2 text-reset hov-bg-soft-light" href="' + href + '"><strong>' + title + '</strong>' + subtitle + '</a>';
                                    }).join('');
                                }

                                showSuggestions(module);
                            })
                            .catch(function () {
                                hideSuggestions(module);
                            });
                    }, 220);
                };

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

                searchModules.forEach(function (module) {
                    setActiveScope(module, module.scopeInput.value || 'products', module.scopeLabel ? module.scopeLabel.textContent : '');

                    module.scopeItems.forEach(function (item) {
                        item.addEventListener('click', function (event) {
                            event.preventDefault();
                            setActiveScope(module, item.getAttribute('data-scope'), item.getAttribute('data-label'));
                            syncSearchModules(module);
                            closeDropdown(item);
                            if (module.searchInput.value.trim().length >= 2) {
                                fetchSuggestions(module);
                                return;
                            }
                            hideSuggestions(module);
                        });
                    });

                    module.searchInput.addEventListener('input', function () {
                        syncSearchModules(module);
                        fetchSuggestions(module);
                    });

                    module.form.addEventListener('submit', function (event) {
                        event.preventDefault();

                        var query = module.searchInput.value.trim();
                        var target = new URL('{{ route('global.search') }}', window.location.origin);

                        if (query !== '') {
                            target.searchParams.set('q', query);
                        }

                        target.searchParams.set('scope', module.scopeInput.value || 'products');
                        if (module.countryInput && module.countryInput.value) {
                            target.searchParams.set('country', module.countryInput.value);
                        }
                        window.location.href = target.toString();
                    });
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

                document.addEventListener('click', function (event) {
                    searchModules.forEach(function (module) {
                        if (module.typedSearchBox && !module.typedSearchBox.contains(event.target) && event.target !== module.searchInput) {
                            hideSuggestions(module);
                        }
                    });
                });

                hoverDropdowns.forEach(function (dropdown) {
                    var hideTimer = null;

                    dropdown.addEventListener('mouseenter', function () {
                        if (window.innerWidth < 1200) {
                            return;
                        }

                        window.clearTimeout(hideTimer);
                        dropdown.classList.add('show');
                        var menu = dropdown.querySelector('.dropdown-menu');
                        if (menu) {
                            menu.classList.add('show');
                        }
                    });

                    dropdown.addEventListener('mouseleave', function () {
                        if (window.innerWidth < 1200) {
                            return;
                        }

                        hideTimer = window.setTimeout(function () {
                            dropdown.classList.remove('show');
                            var menu = dropdown.querySelector('.dropdown-menu');
                            if (menu) {
                                menu.classList.remove('show');
                            }
                        }, 120);
                    });
                });

                var toggleStickyHeader = function () {
                    if (!stickyHeader || window.innerWidth < 1200) {
                        document.body.classList.remove('kaniz-sticky-header-visible');
                        return;
                    }

                    var threshold = headerWrap.offsetTop + headerWrap.offsetHeight;
                    document.body.classList.toggle('kaniz-sticky-header-visible', window.scrollY > threshold);
                };

                toggleStickyHeader();
                window.addEventListener('scroll', toggleStickyHeader, { passive: true });
                window.addEventListener('resize', toggleStickyHeader);
            });
        })();
    </script>
@endonce
