@extends('frontend.layouts.app')

@section('content')
    <style>
        .metro-search-hero { position: relative; background: linear-gradient(180deg, #f7f7fb 0%, #fff5f3 100%); }
        .metro-search-hero-inner { position: static; width: 100%; max-width: none; margin: 0; transform: none; padding: 20px 0 0; }
        .metro-search-tabs { display: flex; align-items: center; justify-content: center; gap: 34px; margin-bottom: 26px; flex-wrap: wrap; }
        .metro-search-tab-divider { width: 1px; height: 34px; background: #d4d4d8; }
        .metro-search-tab { border: 0; background: transparent; padding: 0 0 10px; color: #262626; font-size: 24px; font-weight: 800; line-height: 1; position: relative; cursor: pointer; }
        .metro-search-tab.active { color: #ff6a00; }
        .metro-search-tab.active::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 4px; border-radius: 999px; background: #ff6a00; }
        .metro-search-tab-ai { display: inline-flex; align-items: flex-start; gap: 2px; }
        .metro-search-tab-ai-badge { color: #ff6a00; font-size: 11px; font-weight: 800; line-height: 1; }
        .metro-search-shell { max-width: 100%; margin: 0 auto; border: 2px solid #ff6a00; border-radius: 24px; background: rgba(255,255,255,.98); box-shadow: 0 18px 48px -36px rgba(255,106,0,.4); padding: 18px 18px 14px; }
        .metro-search-row { display: flex; align-items: center; gap: 18px; position: relative; }
        .metro-search-input-wrap { flex: 1; position: relative; }
        .metro-search-input { border: 0; height: 46px; font-size: 16px; color: #0f172a; box-shadow: none !important; padding: 0; }
        .metro-search-input::placeholder { color: #737373; }
        .metro-search-button { min-width: 148px; height: 44px; border: 0; border-radius: 999px; background: linear-gradient(135deg, #ffb300 0%, #ff4d2d 100%); color: #fff; font-size: 14px; font-weight: 700; padding: 0 24px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .metro-search-suggestions { position: absolute; top: calc(100% + 14px); left: 0; right: 0; z-index: 20; background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; box-shadow: 0 18px 40px -30px rgba(15,23,42,.35); overflow: hidden; display: none; }
        .metro-search-suggestions.is-active { display: block; }
        .metro-search-suggestion { display: block; padding: 12px 16px; color: #0f172a; text-decoration: none; border-top: 1px solid #f1f5f9; }
        .metro-search-suggestion:first-child { border-top: 0; }
        .metro-search-suggestion:hover { background: #fff7ed; color: #ea580c; text-decoration: none; }
        .metro-search-bottom { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 18px; }
        .metro-search-image-link { display: inline-flex; align-items: center; gap: 8px; color: #262626; font-size: 14px; font-weight: 700; text-decoration: none; }
        .metro-search-image-link:hover { color: #ea580c; text-decoration: none; }
        .metro-search-chip-row { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 18px; }
        .metro-search-chip { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; border: 1px solid #e2e8f0; background: #fff; color: #334155; padding: 8px 12px; font-size: 12px; font-weight: 600; text-decoration: none; }
        .metro-search-chip:hover { border-color: #fdba74; color: #ea580c; text-decoration: none; }
        .metro-search-cta-banner { margin-top: 22px; padding: 28px 24px; border-radius: 24px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%); box-shadow: 0 20px 50px -35px rgba(15,23,42,.55); text-align: center; }
        .metro-search-cta-title { color: #fff; font-size: 34px; line-height: 1.08; font-weight: 800; letter-spacing: -.04em; margin-bottom: 18px; }
        .metro-search-cta-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
        .metro-search-cta-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 170px; height: 48px; border-radius: 999px; padding: 0 22px; font-size: 14px; font-weight: 700; text-decoration: none; transition: .2s ease; }
        .metro-search-cta-btn:hover { text-decoration: none; transform: translateY(-1px); }
        .metro-search-cta-btn-primary { background: linear-gradient(135deg, #ffb300 0%, #ff4d2d 100%); color: #fff; }
        .metro-search-cta-btn-secondary { border: 1px solid rgba(255,255,255,.22); background: rgba(255,255,255,.08); color: #fff; }
        .metro-mega-categories, .metro-market-card, .metro-service-card, .metro-country-card { background: #fff; box-shadow: 0 18px 50px -40px rgba(15,23,42,.22); }
        .metro-mega-categories { margin-top: 26px; border-radius: 28px; overflow: hidden; }
        .metro-mega-categories-head, .metro-market-header { display: flex; align-items: end; justify-content: space-between; gap: 16px; }
        .metro-mega-categories-head { padding: 26px 26px 18px; border-bottom: 1px solid #eef2f7; }
        .metro-mega-categories-kicker, .metro-market-kicker { color: #f97316; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .metro-mega-categories-title, .metro-market-title { color: #0f172a; font-size: 32px; line-height: 1.05; font-weight: 800; letter-spacing: -.04em; margin-top: 8px; margin-bottom: 0; }
        .metro-mega-categories-subtitle, .metro-market-subtitle { color: #64748b; font-size: 14px; margin-top: 8px; margin-bottom: 0; }
        .metro-mega-categories-link, .metro-market-link, .metro-service-link, .neg-profile-link { color: #f97316; font-size: 13px; font-weight: 700; text-decoration: none; }
        .metro-mega-categories-grid, .metro-market-grid { display: grid; gap: 18px; }
        .metro-mega-categories-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0; }
        .metro-market-section { margin-top: 28px; }
        .metro-market-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .metro-market-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .metro-mega-category-card, .metro-market-card-link { display: block; color: inherit; text-decoration: none; }
        .metro-mega-category-card { padding: 22px 20px; border-right: 1px solid #eef2f7; border-bottom: 1px solid #eef2f7; background: linear-gradient(180deg,#ffffff 0%,#fbfdff 100%); min-height: 100%; }
        .metro-mega-category-icon, .metro-supplier-logo, .metro-product-thumb, .metro-service-icon { background: #fff7ed; color: #f97316; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; }
        .metro-mega-category-icon { width: 58px; height: 58px; border-radius: 18px; margin-bottom: 16px; }
        .metro-supplier-card-top, .metro-product-card-top { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 16px; }
        .metro-supplier-logo, .metro-product-thumb { width: 72px; height: 72px; border-radius: 20px; flex-shrink: 0; }
        .metro-service-icon { width: 54px; height: 54px; border-radius: 18px; font-size: 24px; margin-bottom: 16px; }
        .metro-mega-category-icon img, .metro-supplier-logo img, .metro-product-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .metro-mega-category-name, .metro-market-card-title, .metro-service-title, .metro-country-name { color: #0f172a; font-weight: 800; }
        .metro-mega-category-name, .metro-market-card-title, .metro-service-title { font-size: 18px; line-height: 1.2; margin-bottom: 8px; }
        .metro-mega-category-meta, .metro-market-card-meta, .metro-service-copy, .metro-country-region { color: #64748b; font-size: 12px; line-height: 1.6; }
        .metro-mega-category-tags, .metro-market-badges, .metro-inline-stats, .metro-profile-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .metro-mega-category-tag, .metro-market-badge, .metro-inline-stat { border-radius: 999px; padding: 6px 10px; font-size: 11px; font-weight: 700; }
        .metro-mega-category-tag, .metro-inline-stat { background: #f8fafc; color: #475569; }
        .metro-market-badge { background: #fff7ed; color: #c2410c; }
        .metro-market-badge-soft { background: #f8fafc; color: #475569; }
        .metro-rfq-banner { border-radius: 28px; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%); box-shadow: 0 20px 50px -40px rgba(249,115,22,.45); padding: 24px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 18px; }
        .metro-rfq-banner-title { color: #0f172a; font-size: 28px; line-height: 1.1; font-weight: 800; margin: 0; }
        .metro-rfq-banner-subtitle { color: #64748b; font-size: 14px; margin: 8px 0 0; }
        .metro-rfq-banner-action { display: inline-flex; align-items: center; justify-content: center; min-width: 180px; height: 48px; border-radius: 999px; background: linear-gradient(135deg, #ffb300 0%, #ff4d2d 100%); color: #fff; padding: 0 22px; font-size: 14px; font-weight: 700; text-decoration: none; }
        .metro-country-card, .metro-service-card, .metro-market-card { border-radius: 24px; padding: 22px; }
        .metro-country-count { color: #0f172a; font-size: 28px; line-height: 1; font-weight: 800; margin-bottom: 8px; }
        @media (max-width: 575px){
            .metro-search-hero-inner { padding: 20px 14px 20px; }
            .metro-search-tabs { gap: 18px; justify-content: flex-start; margin-bottom: 18px; }
            .metro-search-tab { font-size: 18px; }
            .metro-search-row { flex-wrap: wrap; }
            .metro-search-button, .metro-search-cta-btn, .metro-rfq-banner-action { width: 100%; min-width: 0; }
            .metro-search-bottom, .metro-market-header, .metro-rfq-banner, .metro-mega-categories-head { flex-direction: column; align-items: flex-start; }
            .metro-mega-categories-title, .metro-market-title, .metro-search-cta-title, .metro-rfq-banner-title { font-size: 24px; }
            .metro-mega-categories-grid, .metro-market-grid-3, .metro-market-grid-4 { grid-template-columns: 1fr; }
            .metro-mega-category-card { border-right: 0; }
        }
    </style>

    @php
        $lang = get_system_language()->code;
        $searchHotCategories = $hot_categories->take(8);
        $imageSearchEnabled = get_setting('enable_global_search_image', '1') === '1';
        $megaCategories = $featured_categories->take(8);
        $featuredSuppliers = $tradeServicesData['featured_suppliers_list'] ?? collect();
        $latestRfqs = $tradeServicesData['latest_rfqs'] ?? collect();
    @endphp

    <div class="metro-search-hero mb-3">
        <div class="container">
            <div class="metro-search-hero-inner">
                <div class="metro-search-tabs">
                    <button type="button" class="metro-search-tab active" data-scope="ai_mode"><span class="metro-search-tab-ai"><span>AI Mode</span><span class="metro-search-tab-ai-badge">AI</span></span></button>
                    <span class="metro-search-tab-divider d-none d-md-block"></span>
                    <button type="button" class="metro-search-tab" data-scope="products">{{ translate('Products') }}</button>
                    <button type="button" class="metro-search-tab" data-scope="manufacturers">{{ translate('Manufacturers') }}</button>
                    <button type="button" class="metro-search-tab" data-scope="worldwide">{{ translate('Worldwide') }}</button>
                </div>

                <div class="metro-search-shell">
                    <form method="GET" action="{{ route('global.search') }}" id="metro-search-form">
                        <input type="hidden" name="scope" id="metro-search-scope" value="ai_mode">
                        <div class="metro-search-row">
                            <div class="metro-search-input-wrap">
                                <input type="text" class="form-control metro-search-input" name="q" id="metro-home-search-input" placeholder="{{ translate('women’s intimates') }}" autocomplete="off">
                                <div id="metro-home-search-suggestions" class="metro-search-suggestions"></div>
                            </div>
                            <button type="submit" class="metro-search-button"><i class="las la-search"></i><span>{{ translate('Search') }}</span></button>
                        </div>
                        <div class="metro-search-bottom">
                            @if ($imageSearchEnabled)
                                <a href="javascript:void(0)" class="metro-search-image-link" data-toggle="modal" data-target="#globalSearchImageModal"><i class="las la-camera-retro"></i><span>{{ translate('Image Search') }}</span></a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="metro-search-cta-banner">
                    <div class="metro-search-cta-title">{{ translate('Source products from verified global suppliers') }}</div>
                    <div class="metro-search-cta-actions">
                        <a href="{{ route('b2b.rfqs.create') }}" class="metro-search-cta-btn metro-search-cta-btn-primary">{{ translate('Request Quote') }}</a>
                        <a href="{{ route('b2b.portal.become-supplier') }}" class="metro-search-cta-btn metro-search-cta-btn-secondary">{{ translate('Become Supplier') }}</a>
                    </div>
                </div>

                <div class="metro-search-chip-row">
                    @foreach ($searchHotCategories as $category)
                        <a href="{{ route('products.category', $category->slug) }}" class="metro-search-chip"><i class="las la-fire text-warning"></i><span>{{ $category->getTranslation('name') }}</span></a>
                    @endforeach
                </div>

                @if ($megaCategories->count() > 0)
                    <div class="metro-mega-categories">
                        <div class="metro-mega-categories-head">
                            <div>
                                <div class="metro-mega-categories-kicker">{{ translate('Mega Categories') }}</div>
                                <h2 class="metro-mega-categories-title">{{ translate('Popular B2B + B2C categories') }}</h2>
                                <p class="metro-mega-categories-subtitle">{{ translate('Browse high-demand marketplace categories used by wholesale buyers, retail shoppers, and global suppliers.') }}</p>
                            </div>
                            <a href="{{ route('categories.all') }}" class="metro-mega-categories-link">{{ translate('View all categories') }}</a>
                        </div>
                        <div class="metro-mega-categories-grid">
                            @foreach ($megaCategories as $category)
                                @php
                                    $categoryName = $category->getTranslation('name');
                                    $categoryProductsCount = $category->products()->count();
                                    $categoryChildren = $category->childrenCategories()->take(3)->get();
                                @endphp
                                <a href="{{ route('products.category', $category->slug) }}" class="metro-mega-category-card">
                                    <span class="metro-mega-category-icon">
                                        @if ($category->cover_image)
                                            <img src="{{ uploaded_asset($category->cover_image) }}" alt="{{ $categoryName }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                        @else
                                            <i class="las la-cubes"></i>
                                        @endif
                                    </span>
                                    <div class="metro-mega-category-name">{{ $categoryName }}</div>
                                    <div class="metro-mega-category-meta">{{ $categoryProductsCount }} {{ translate('products') }}</div>
                                    @if ($categoryChildren->count() > 0)
                                        <div class="metro-mega-category-tags">
                                            @foreach ($categoryChildren as $childCategory)
                                                <span class="metro-mega-category-tag">{{ $childCategory->getTranslation('name') }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($featuredSuppliers->count() > 0)
                    <section class="metro-market-section">
                        <div class="metro-market-header">
                            <div>
                                <div class="metro-market-kicker">{{ translate('Featured Suppliers') }}</div>
                                <h2 class="metro-market-title">{{ translate('Verified supplier cards') }}</h2>
                                <p class="metro-market-subtitle">{{ translate('Meet trusted global suppliers with public profiles, product categories, and response quality signals.') }}</p>
                            </div>
                            <a href="{{ route('b2b.suppliers.index') }}" class="metro-market-link">{{ translate('Browse supplier directory') }}</a>
                        </div>
                        <div class="metro-market-grid metro-market-grid-3">
                            @foreach ($featuredSuppliers->take(3) as $supplier)
                                <a href="{{ route('b2b.suppliers.show', $supplier->public_slug) }}" class="metro-market-card metro-market-card-link">
                                    <div class="metro-supplier-card-top">
                                        <span class="metro-supplier-logo">@if ($supplier->logo)<img src="{{ uploaded_asset($supplier->logo) }}" alt="{{ $supplier->company_name }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">@else<i class="las la-industry"></i>@endif</span>
                                        <div>
                                            <div class="metro-market-card-title">{{ $supplier->company_name }}</div>
                                            <div class="metro-market-card-meta">{{ ucfirst($supplier->company_type) }} · {{ $supplier->country ?: translate('Global') }}</div>
                                            <div class="metro-market-badges">
                                                @if ($supplier->verified_supplier_badge)<span class="metro-market-badge">{{ translate('Verified Supplier') }}</span>@endif
                                                @if ($supplier->premium_verified)<span class="metro-market-badge metro-market-badge-soft">{{ translate('Premium Verified') }}</span>@endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="metro-market-card-meta">{{ \Illuminate\Support\Str::limit($supplier->business_scope ?: $supplier->description, 120) ?: translate('View categories, response quality, and company profile details.') }}</div>
                                    <div class="metro-inline-stats">
                                        <span class="metro-inline-stat">{{ translate('Profile Score') }}: {{ (int) ($supplier->profile_score ?? 0) }}</span>
                                        <span class="metro-inline-stat">{{ translate('Response') }}: {{ $supplier->response_rate ? rtrim(rtrim(number_format((float) $supplier->response_rate, 2), '0'), '.') . '%' : '-' }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="metro-market-section">
                    <div class="metro-rfq-banner">
                        <div>
                            <h2 class="metro-rfq-banner-title">{{ translate('Post your requirement, get quotes fast') }}</h2>
                            <p class="metro-rfq-banner-subtitle">{{ translate('Create an RFQ, target verified suppliers, and speed up sourcing with direct quote workflows.') }}</p>
                        </div>
                        <a href="{{ route('b2b.rfqs.create') }}" class="metro-rfq-banner-action">{{ translate('Post RFQ Now') }}</a>
                    </div>
                    @if ($latestRfqs->count() > 0)
                        <div class="metro-market-grid metro-market-grid-4">
                            @foreach ($latestRfqs as $rfq)
                                <a href="{{ route('b2b.rfqs.show', $rfq->id) }}" class="metro-market-card metro-market-card-link">
                                    <div class="metro-market-card-title">{{ \Illuminate\Support\Str::limit($rfq->title, 60) }}</div>
                                    <div class="metro-market-card-meta">{{ $rfq->category?->getTranslation('name') ?: translate('General sourcing') }}</div>
                                    <div class="metro-inline-stats">
                                        <span class="metro-inline-stat">{{ translate('Qty') }}: {{ $rfq->quantity ?: '-' }} {{ $rfq->unit ?: '' }}</span>
                                        <span class="metro-inline-stat">{{ ucfirst($rfq->status) }}</span>
                                    </div>
                                    <div class="metro-market-card-meta mt-3">{{ $rfq->destination_country ? translate('Destination') . ': ' . $rfq->destination_country : translate('Open for global supplier quotes') }}</div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

            </div>
        </div>
    </div>

    @if ($imageSearchEnabled)
        @include('frontend.enterprise_search.partials.image_search_modal')
    @endif

    <!-- Today's deal -->
    @php
        $todays_deal_section_bg = get_setting('todays_deal_section_bg_color');
    @endphp
    <div id="todays_deal" class="mb-2rem mt-2 mt-md-3" @if(get_setting('todays_deal_section_bg') == 1) style="background: {{ $todays_deal_section_bg }};" @endif>

    </div>

    <!-- Featured Categories -->
    @if (count($featured_categories) > 0)
        <section class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="container">
                <div class="bg-white">
                    <!-- Top Section -->
                    <div class="d-flex mt-2 mt-md-3 mb-2 mb-md-3 align-items-baseline justify-content-between">
                        <!-- Title -->
                        <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">
                            <span class="">{{ translate('Featured Categories') }}</span>
                        </h3>
                    </div>
                </div>
                <!-- Categories -->
                <div class="bg-white px-sm-3">
                    <div class="aiz-carousel sm-gutters-17" data-items="4" data-xxl-items="4" data-xl-items="3.5"
                        data-lg-items="3" data-md-items="2" data-sm-items="2" data-xs-items="1" data-arrows="true"
                        data-dots="false" data-autoplay="false" data-infinite="true">
                        @foreach ($featured_categories as $key => $category)
                            @php
                                $category_name = $category->getTranslation('name');
                            @endphp
                            <div class="carousel-box position-relative p-0 has-transition border-right border-top border-bottom @if ($key == 0) border-left @endif">
                                <div class="h-200px h-sm-250px h-md-340px">
                                    <div class="h-100 w-100 w-xl-auto position-relative hov-scale-img overflow-hidden">
                                        <div class="position-absolute h-100 w-100 overflow-hidden">
                                            <img src="{{ isset($category->coverImage->file_name) ? my_asset($category->coverImage->file_name) : static_asset('assets/img/placeholder.jpg') }}"
                                                alt="{{ $category_name }}"
                                                class="img-fit h-100 has-transition"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                        </div>
                                        <div class="pb-4 px-4 absolute-bottom-left has-transition h-50 w-100 d-flex flex-column align-items-center justify-content-end"
                                            style="background: linear-gradient(to top, rgba(0,0,0,0.5) 50%,rgba(0,0,0,0) 100%) !important;">
                                            <div class="w-100">
                                                <a class="fs-16 fw-700 text-white animate-underline-white home-category-name d-flex align-items-center hov-column-gap-1"
                                                    href="{{ route('products.category', $category->slug) }}"
                                                    style="width: max-content;">
                                                    {{ $category_name }}&nbsp;
                                                    <i class="las la-angle-right"></i>
                                                </a>
                                                <div class="d-flex flex-wrap h-50px overflow-hidden mt-2">
                                                    @foreach ($category->childrenCategories->take(6) as $key => $child_category)
                                                    <a href="{{ route('products.category', $child_category->slug) }}" class="fs-13 fw-300 text-soft-light hov-text-white pr-3 pt-1">
                                                        {{ $child_category->getTranslation('name') }}
                                                    </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Banner section 1 -->
    @php $homeBanner1Images = get_setting('home_banner1_images', null, $lang);   @endphp
    @if ($homeBanner1Images != null)
        <div class="pb-2 pb-md-3 pt-2 pt-md-3" style="background: #f5f5fa;">
            <div class="container mb-2 mb-md-3">
                @php
                    $banner_1_imags = json_decode($homeBanner1Images);
                    $data_md = count($banner_1_imags) >= 2 ? 2 : 1;
                    $home_banner1_links = get_setting('home_banner1_links', null, $lang);
                @endphp
                <div class="w-100">
                    <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                        data-items="{{ count($banner_1_imags) }}" data-xxl-items="{{ count($banner_1_imags) }}"
                        data-xl-items="{{ count($banner_1_imags) }}" data-lg-items="{{ $data_md }}"
                        data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                        data-dots="false">
                        @foreach ($banner_1_imags as $key => $value)
                            <div class="carousel-box overflow-hidden hov-scale-img">
                                <a href="{{ isset(json_decode($home_banner1_links, true)[$key]) ? json_decode($home_banner1_links, true)[$key] : '' }}"
                                    class="d-block text-reset overflow-hidden">
                                    <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                        data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                        class="img-fluid lazyload w-100 has-transition"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (addon_is_activated('preorder'))

        <!-- Preorder Banner 1 -->
        @php $homepreorder_banner_1Images = get_setting('home_preorder_banner_1_images', null, $lang);   @endphp
        @if ($homepreorder_banner_1Images != null)
            <div class="mb-2 mb-md-3 mt-2 mt-md-3">
                <div class="container">
                    @php
                        $banner_2_imags = json_decode($homepreorder_banner_1Images);
                        $data_md = count($banner_2_imags) >= 2 ? 2 : 1;
                        $home_preorder_banner_1_links = get_setting('home_preorder_banner_1_links', null, $lang);
                    @endphp
                    <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                        data-items="{{ count($banner_2_imags) }}" data-xxl-items="{{ count($banner_2_imags) }}"
                        data-xl-items="{{ count($banner_2_imags) }}" data-lg-items="{{ $data_md }}"
                        data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                        data-dots="false">
                        @foreach ($banner_2_imags as $key => $value)
                            <div class="carousel-box overflow-hidden hov-scale-img">
                                <a href="{{ isset(json_decode($home_preorder_banner_1_links, true)[$key]) ? json_decode($home_preorder_banner_1_links, true)[$key] : '' }}"
                                    class="d-block text-reset overflow-hidden">
                                    <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                        data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                        class="img-fluid lazyload w-100 has-transition"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif


        <!-- Featured Preorder Products -->
        <div id="section_featured_preorder_products">

        </div>
    @endif

    <!-- Banner Section 2 -->
    @php $homeBanner2Images = get_setting('home_banner2_images', null, $lang);   @endphp
    @if ($homeBanner2Images != null)
        <div class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="container">
                @php
                    $banner_2_imags = json_decode($homeBanner2Images);
                    $data_md = count($banner_2_imags) >= 2 ? 2 : 1;
                    $home_banner2_links = get_setting('home_banner2_links', null, $lang);
                @endphp
                <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                    data-items="{{ count($banner_2_imags) }}" data-xxl-items="{{ count($banner_2_imags) }}"
                    data-xl-items="{{ count($banner_2_imags) }}" data-lg-items="{{ $data_md }}"
                    data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                    data-dots="false">
                    @foreach ($banner_2_imags as $key => $value)
                        <div class="carousel-box overflow-hidden hov-scale-img">
                            <a href="{{ isset(json_decode($home_banner2_links, true)[$key]) ? json_decode($home_banner2_links, true)[$key] : '' }}"
                                class="d-block text-reset overflow-hidden">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                    class="img-fluid lazyload w-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- New Products -->
    <div id="section_newest">

    </div>

    <!-- Banner Section 3 -->
    @php $homeBanner3Images = get_setting('home_banner3_images', null, $lang);   @endphp
    @if ($homeBanner3Images != null)
        <div class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="container">
                @php
                    $banner_3_imags = json_decode($homeBanner3Images);
                    $data_md = count($banner_3_imags) >= 2 ? 2 : 1;
                    $home_banner3_links = get_setting('home_banner3_links', null, $lang);
                @endphp
                <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                    data-items="{{ count($banner_3_imags) }}" data-xxl-items="{{ count($banner_3_imags) }}"
                    data-xl-items="{{ count($banner_3_imags) }}" data-lg-items="{{ $data_md }}"
                    data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                    data-dots="false">
                    @foreach ($banner_3_imags as $key => $value)
                        <div class="carousel-box overflow-hidden hov-scale-img">
                            <a href="{{ isset(json_decode($home_banner3_links, true)[$key]) ? json_decode($home_banner3_links, true)[$key] : '' }}"
                                class="d-block text-reset overflow-hidden">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                    class="img-fluid lazyload w-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Auction Product -->
    @if (addon_is_activated('auction'))
        <div id="auction_products">

        </div>
    @endif

    <!-- Cupon -->
    @if (get_setting('coupon_system') == 1)
        <div class=" mt-2 mt-md-3"
            style="background-color: {{ get_setting('cupon_background_color', '#292933') }}">
            <div class="container">
                <div class="position-relative py-5">
                    <div class="text-center text-xl-left position-relative z-5">
                        <div class="d-lg-flex">
                            <div class="mb-3 mb-lg-0">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    width="109.602" height="93.34" viewBox="0 0 109.602 93.34">
                                    <defs>
                                        <clipPath id="clip-pathcup">
                                            <path id="Union_10" data-name="Union 10" d="M12263,13778v-15h64v-41h12v56Z"
                                                transform="translate(-11966 -8442.865)" fill="none" stroke="#fff"
                                                stroke-width="2" />
                                        </clipPath>
                                    </defs>
                                    <g id="Group_24326" data-name="Group 24326"
                                        transform="translate(-274.201 -5254.611)">
                                        <g id="Mask_Group_23" data-name="Mask Group 23"
                                            transform="translate(-3652.459 1785.452) rotate(-45)"
                                            clip-path="url(#clip-pathcup)">
                                            <g id="Group_24322" data-name="Group 24322"
                                                transform="translate(207 18.136)">
                                                <g id="Subtraction_167" data-name="Subtraction 167"
                                                    transform="translate(-12177 -8458)" fill="none">
                                                    <path
                                                        d="M12335,13770h-56a8.009,8.009,0,0,1-8-8v-8a8,8,0,0,0,0-16v-8a8.009,8.009,0,0,1,8-8h56a8.009,8.009,0,0,1,8,8v8a8,8,0,0,0,0,16v8A8.009,8.009,0,0,1,12335,13770Z"
                                                        stroke="none" />
                                                    <path
                                                        d="M 12335.0009765625 13768.0009765625 C 12338.3095703125 13768.0009765625 12341.0009765625 13765.30859375 12341.0009765625 13762 L 12341.0009765625 13755.798828125 C 12336.4423828125 13754.8701171875 12333.0009765625 13750.8291015625 12333.0009765625 13746 C 12333.0009765625 13741.171875 12336.4423828125 13737.130859375 12341.0009765625 13736.201171875 L 12341.0009765625 13729.9990234375 C 12341.0009765625 13726.6904296875 12338.3095703125 13723.9990234375 12335.0009765625 13723.9990234375 L 12278.9990234375 13723.9990234375 C 12275.6904296875 13723.9990234375 12272.9990234375 13726.6904296875 12272.9990234375 13729.9990234375 L 12272.9990234375 13736.201171875 C 12277.5576171875 13737.1298828125 12280.9990234375 13741.1708984375 12280.9990234375 13746 C 12280.9990234375 13750.828125 12277.5576171875 13754.869140625 12272.9990234375 13755.798828125 L 12272.9990234375 13762 C 12272.9990234375 13765.30859375 12275.6904296875 13768.0009765625 12278.9990234375 13768.0009765625 L 12335.0009765625 13768.0009765625 M 12335.0009765625 13770.0009765625 L 12278.9990234375 13770.0009765625 C 12274.587890625 13770.0009765625 12270.9990234375 13766.412109375 12270.9990234375 13762 L 12270.9990234375 13754 C 12275.4111328125 13753.9990234375 12278.9990234375 13750.4111328125 12278.9990234375 13746 C 12278.9990234375 13741.5888671875 12275.41015625 13738 12270.9990234375 13738 L 12270.9990234375 13729.9990234375 C 12270.9990234375 13725.587890625 12274.587890625 13721.9990234375 12278.9990234375 13721.9990234375 L 12335.0009765625 13721.9990234375 C 12339.412109375 13721.9990234375 12343.0009765625 13725.587890625 12343.0009765625 13729.9990234375 L 12343.0009765625 13738 C 12338.5888671875 13738.0009765625 12335.0009765625 13741.5888671875 12335.0009765625 13746 C 12335.0009765625 13750.4111328125 12338.58984375 13754 12343.0009765625 13754 L 12343.0009765625 13762 C 12343.0009765625 13766.412109375 12339.412109375 13770.0009765625 12335.0009765625 13770.0009765625 Z"
                                                        stroke="none" fill="#fff" />
                                                </g>
                                            </g>
                                        </g>
                                        <g id="Group_24321" data-name="Group 24321"
                                            transform="translate(-3514.477 1653.317) rotate(-45)">
                                            <g id="Subtraction_167-2" data-name="Subtraction 167"
                                                transform="translate(-12177 -8458)" fill="none">
                                                <path
                                                    d="M12335,13770h-56a8.009,8.009,0,0,1-8-8v-8a8,8,0,0,0,0-16v-8a8.009,8.009,0,0,1,8-8h56a8.009,8.009,0,0,1,8,8v8a8,8,0,0,0,0,16v8A8.009,8.009,0,0,1,12335,13770Z"
                                                    stroke="none" />
                                                <path
                                                    d="M 12335.0009765625 13768.0009765625 C 12338.3095703125 13768.0009765625 12341.0009765625 13765.30859375 12341.0009765625 13762 L 12341.0009765625 13755.798828125 C 12336.4423828125 13754.8701171875 12333.0009765625 13750.8291015625 12333.0009765625 13746 C 12333.0009765625 13741.171875 12336.4423828125 13737.130859375 12341.0009765625 13736.201171875 L 12341.0009765625 13729.9990234375 C 12341.0009765625 13726.6904296875 12338.3095703125 13723.9990234375 12335.0009765625 13723.9990234375 L 12278.9990234375 13723.9990234375 C 12275.6904296875 13723.9990234375 12272.9990234375 13726.6904296875 12272.9990234375 13729.9990234375 L 12272.9990234375 13736.201171875 C 12277.5576171875 13737.1298828125 12280.9990234375 13741.1708984375 12280.9990234375 13746 C 12280.9990234375 13750.828125 12277.5576171875 13754.869140625 12272.9990234375 13755.798828125 L 12272.9990234375 13762 C 12272.9990234375 13765.30859375 12275.6904296875 13768.0009765625 12278.9990234375 13768.0009765625 L 12335.0009765625 13768.0009765625 M 12335.0009765625 13770.0009765625 L 12278.9990234375 13770.0009765625 C 12274.587890625 13770.0009765625 12270.9990234375 13766.412109375 12270.9990234375 13762 L 12270.9990234375 13754 C 12275.4111328125 13753.9990234375 12278.9990234375 13750.4111328125 12278.9990234375 13746 C 12278.9990234375 13741.5888671875 12275.41015625 13738 12270.9990234375 13738 L 12270.9990234375 13729.9990234375 C 12270.9990234375 13725.587890625 12274.587890625 13721.9990234375 12278.9990234375 13721.9990234375 L 12335.0009765625 13721.9990234375 C 12339.412109375 13721.9990234375 12343.0009765625 13725.587890625 12343.0009765625 13729.9990234375 L 12343.0009765625 13738 C 12338.5888671875 13738.0009765625 12335.0009765625 13741.5888671875 12335.0009765625 13746 C 12335.0009765625 13750.4111328125 12338.58984375 13754 12343.0009765625 13754 L 12343.0009765625 13762 C 12343.0009765625 13766.412109375 12339.412109375 13770.0009765625 12335.0009765625 13770.0009765625 Z"
                                                    stroke="none" fill="#fff" />
                                            </g>
                                            <g id="Group_24325" data-name="Group 24325">
                                                <rect id="Rectangle_18578" data-name="Rectangle 18578" width="8"
                                                    height="2" transform="translate(120 5287)" fill="#fff" />
                                                <rect id="Rectangle_18579" data-name="Rectangle 18579" width="8"
                                                    height="2" transform="translate(132 5287)" fill="#fff" />
                                                <rect id="Rectangle_18581" data-name="Rectangle 18581" width="8"
                                                    height="2" transform="translate(144 5287)" fill="#fff" />
                                                <rect id="Rectangle_18580" data-name="Rectangle 18580" width="8"
                                                    height="2" transform="translate(108 5287)" fill="#fff" />
                                            </g>
                                        </g>
                                    </g>
                                </svg>
                            </div>
                            <div class="ml-lg-3">
                                <h5 class="fs-36 fw-400 text-white mb-3">{{ translate(get_setting('cupon_title')) }}</h5>
                                <h5 class="fs-20 fw-400 text-gray">{{ translate(get_setting('cupon_subtitle')) }}</h5>
                                <div class="mt-5 pt-5">
                                    <a href="{{ route('coupons.all') }}"
                                        class="btn text-white hov-bg-white hov-text-dark border border-width-2 fs-16 px-5"
                                        style="border-radius: 28px;background: rgba(255, 255, 255, 0.2);box-shadow: 0px 20px 30px rgba(0, 0, 0, 0.16);">{{ translate('View All Coupons') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="position-absolute right-0 bottom-0 h-100">
                        <img class="img-fit h-100" src="{{ uploaded_asset(get_setting('coupon_background_image', null, $lang)) }}"
                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/coupon.svg') }}';"
                            alt="{{ env('APP_NAME') }} promo">
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Category wise Products -->
    <div id="section_home_categories" style="background: #f5f5fa;">

    </div>

    @if (addon_is_activated('preorder'))
        <!-- Newest Preorder Products -->
        @include('preorder.frontend.home_page.newest_preorder')
    @endif

    <!-- Classified Product -->
    @if (get_setting('classified_product') == 1)
        @php
            $classified_products = get_home_page_classified_products(6);
        @endphp
        @if (count($classified_products) > 0)
            <section class="mb-2 mb-md-3 mt-3 mt-md-5">
                <div class="container">
                    <!-- Top Section -->
                    <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                        <!-- Title -->
                        <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">
                            <span class="">{{ translate('Classified Ads') }}</span>
                        </h3>
                        <!-- Links -->
                        <div class="d-flex">
                            <a class="text-blue fs-10 fs-md-12 fw-700 hov-text-primary animate-underline-primary"
                                href="{{ route('customer.products') }}">{{ translate('View All Products') }}</a>
                        </div>
                    </div>
                    <!-- Banner -->
                    @php
                        $classifiedBannerImage = get_setting('classified_banner_image', null, $lang);
                        $classifiedBannerImageSmall = get_setting('classified_banner_image_small', null, $lang);
                    @endphp
                    @if ($classifiedBannerImage != null || $classifiedBannerImageSmall != null)
                        <div class="mb-3 overflow-hidden hov-scale-img d-none d-md-block">
                            <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                data-src="{{ uploaded_asset($classifiedBannerImage) }}"
                                alt="{{ env('APP_NAME') }} promo" class="lazyload img-fit h-100 has-transition"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                        </div>
                        <div class="mb-3 overflow-hidden hov-scale-img d-md-none">
                            <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                data-src="{{ $classifiedBannerImageSmall != null ? uploaded_asset($classifiedBannerImageSmall) : uploaded_asset($classifiedBannerImage) }}"
                                alt="{{ env('APP_NAME') }} promo" class="lazyload img-fit h-100 has-transition"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                        </div>
                    @endif
                    <!-- Products Section -->
                    <div class="bg-white pt-3">
                        <div class="row no-gutters border-top border-left">
                            @foreach ($classified_products as $key => $classified_product)
                                <div
                                    class="col-xl-4 col-md-6 border-right border-bottom has-transition hov-shadow-out z-1">
                                    <div class="aiz-card-box p-2 has-transition bg-white">
                                        <div class="row hov-scale-img">
                                            <div class="col-4 col-md-5 mb-3 mb-md-0">
                                                <a href="{{ route('customer.product', $classified_product->slug) }}"
                                                    class="d-block overflow-hidden h-auto h-md-150px text-center">
                                                    <img class="img-fluid lazyload mx-auto has-transition"
                                                        src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                        data-src="{{ isset($classified_product->thumbnail->file_name) ? my_asset($classified_product->thumbnail->file_name) : static_asset('assets/img/placeholder.jpg') }}"
                                                        alt="{{ $classified_product->getTranslation('name') }}"
                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                </a>
                                            </div>
                                            <div class="col">
                                                <h3
                                                    class="fw-400 fs-14 text-dark text-truncate-2 lh-1-4 mb-3 h-35px d-none d-sm-block">
                                                    <a href="{{ route('customer.product', $classified_product->slug) }}"
                                                        class="d-block text-reset hov-text-primary">{{ $classified_product->getTranslation('name') }}</a>
                                                </h3>
                                                <div class="fs-14 mb-3">
                                                    <span
                                                        class="text-secondary">{{ $classified_product->user ? $classified_product->user->name : '' }}</span><br>
                                                    <span
                                                        class="fw-700 text-primary">{{ single_price($classified_product->unit_price) }}</span>
                                                </div>
                                                @if ($classified_product->conditon == 'new')
                                                    <span
                                                        class="badge badge-inline badge-soft-info fs-13 fw-700 px-3 py-2 text-info"
                                                        style="border-radius: 20px;">{{ translate('New') }}</span>
                                                @elseif($classified_product->conditon == 'used')
                                                    <span
                                                        class="badge badge-inline badge-soft-secondary-base fs-13 fw-700 px-3 py-2 text-danger"
                                                        style="border-radius: 20px;">{{ translate('Used') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endif

    <!-- Top Sellers -->
    @if (get_setting('vendor_system_activation') == 1)
        @php
            $best_selers = get_best_sellers(10);
        @endphp
        @if (count($best_selers) > 0)
        <section class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="container">
                <!-- Top Section -->
                <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                    <!-- Title -->
                    <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">
                        <span class="pb-3">{{ translate('Top Sellers') }}</span>
                    </h3>
                    <!-- Links -->
                    <div class="d-flex">
                        <a class="text-blue fs-10 fs-md-12 fw-700 hov-text-primary animate-underline-primary"
                            href="{{ route('sellers') }}">{{ translate('View All Sellers') }}</a>
                    </div>
                </div>
                <!-- Sellers Section -->
                <div class="aiz-carousel arrow-x-0 arrow-inactive-none" data-items="5" data-xxl-items="5"
                    data-xl-items="4" data-lg-items="3.4" data-md-items="2.5" data-sm-items="2" data-xs-items="1.4"
                    data-arrows="true" data-dots="false">
                    @foreach ($best_selers as $key => $seller)
                        @if ($seller->user != null)
                            <div
                                class="carousel-box h-100 position-relative text-center border-right border-top border-bottom @if ($key == 0) border-left @endif has-transition hov-animate-outline">
                                <div class="position-relative px-3" style="padding-top: 2rem; padding-bottom:2rem;">
                                    <!-- Shop logo & Verification Status -->
                                    <div class="mx-auto size-100px size-md-120px">
                                        <a href="{{ route('shop.visit', $seller->slug) }}"
                                            class="d-flex mx-auto justify-content-center align-item-center size-100px size-md-120px border overflow-hidden hov-scale-img"
                                            tabindex="0"
                                            style="border: 1px solid #e5e5e5; border-radius: 50%; box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.06);">
                                            <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                                data-src="{{ uploaded_asset($seller->logo) }}" alt="{{ $seller->name }}"
                                                class="img-fit lazyload has-transition"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                        </a>
                                    </div>
                                    <!-- Shop name -->
                                    <h2 class="fs-14 fw-700 text-dark text-truncate-2 h-40px mt-3 mt-md-4 mb-0 mb-md-3">
                                        <a href="{{ route('shop.visit', $seller->slug) }}"
                                            class="text-reset hov-text-primary" tabindex="0">{{ $seller->name }}</a>
                                    </h2>
                                    <!-- Shop Rating -->
                                    <div class="rating rating-mr-2 text-dark mb-3">
                                        {{ renderStarRating($seller->rating) }}
                                        <span class="opacity-60 fs-14">({{ $seller->num_of_reviews }}
                                            {{ translate('Reviews') }})</span>
                                    </div>
                                    <!-- Visit Button -->
                                    <a href="{{ route('shop.visit', $seller->slug) }}" class="btn-visit">
                                        <span class="circle" aria-hidden="true">
                                            <span class="icon arrow"></span>
                                        </span>
                                        <span class="button-text">{{ translate('Visit Store') }}</span>
                                    </a>
                                    @if ($seller->verification_status == 1)
                                        <span class="absolute-top-right mr-2rem">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="31.999" height="48.001" viewBox="0 0 31.999 48.001">
                                                <g id="Group_25062" data-name="Group 25062" transform="translate(-532 -1033.999)">
                                                <path id="Union_3" data-name="Union 3" d="M1937,12304h16v14Zm-16,0h16l-16,14Zm0,0v-34h32v34Z" transform="translate(-1389 -11236)" fill="#85b567"/>
                                                <path id="Union_5" data-name="Union 5" d="M1921,12280a10,10,0,1,1,10,10A10,10,0,0,1,1921,12280Zm1,0a9,9,0,1,0,9-9A9.011,9.011,0,0,0,1922,12280Zm1,0a8,8,0,1,1,8,8A8.009,8.009,0,0,1,1923,12280Zm4.26-1.033a.891.891,0,0,0-.262.636.877.877,0,0,0,.262.632l2.551,2.551a.9.9,0,0,0,.635.266.894.894,0,0,0,.639-.266l4.247-4.244a.9.9,0,0,0-.639-1.542.893.893,0,0,0-.635.266l-3.612,3.608-1.912-1.906a.89.89,0,0,0-1.274,0Z" transform="translate(-1383 -11226)" fill="#fff"/>
                                                </g>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
        @endif
    @endif

@endsection

@section('script')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('metro-home-search-input');
        const suggestions = document.getElementById('metro-home-search-suggestions');
        const scopeInput = document.getElementById('metro-search-scope');
        const scopeTabs = document.querySelectorAll('.metro-search-tab[data-scope]');
        const searchForm = document.getElementById('metro-search-form');
        const imageForm = document.getElementById('global-search-image-form');
        const imageFeedback = document.getElementById('global-search-image-feedback');
        let searchTimer = null;

        if (!searchInput || !suggestions || !scopeInput) {
            return;
        }

        const hideSuggestions = function () {
            suggestions.innerHTML = '';
            suggestions.classList.remove('is-active');
        };

        const activeScope = function () {
            return scopeInput.value || 'ai_mode';
        };

        scopeTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                scopeInput.value = tab.dataset.scope || 'ai_mode';
                scopeTabs.forEach(function (item) {
                    item.classList.toggle('active', item === tab);
                });
                hideSuggestions();
            });
        });

        searchInput.addEventListener('input', function () {
            window.clearTimeout(searchTimer);
            const value = searchInput.value.trim();

            if (value.length < 2) {
                hideSuggestions();
                return;
            }

            searchTimer = window.setTimeout(function () {
                fetch('{{ route('global.search.autocomplete') }}?q=' + encodeURIComponent(value) + '&scope=' + encodeURIComponent(activeScope()))
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        const items = (data && data.suggestions) ? data.suggestions : [];

                        if (!items.length) {
                            hideSuggestions();
                            return;
                        }

                        suggestions.innerHTML = items.map(function (item) {
                            const title = item.title || '';
                            const subtitle = item.subtitle ? '<div class="text-muted fs-11 mt-1">' + item.subtitle + '</div>' : '';
                            const href = item.url || ('{{ route('global.search') }}?q=' + encodeURIComponent(title) + '&scope=' + encodeURIComponent(activeScope()));
                            return '<a class="metro-search-suggestion" href="' + href + '"><strong>' + title + '</strong>' + subtitle + '</a>';
                        }).join('');
                        suggestions.classList.add('is-active');
                    })
                    .catch(function () {
                        hideSuggestions();
                    });
            }, 220);
        });

        document.addEventListener('click', function (event) {
            if (!suggestions.contains(event.target) && event.target !== searchInput) {
                hideSuggestions();
            }
        });

        if (imageForm && imageFeedback && searchForm) {
            imageForm.addEventListener('submit', function (event) {
                event.preventDefault();
                imageFeedback.className = 'mt-3';
                imageFeedback.innerHTML = '<div class="alert alert-info mb-0">{{ translate('Analyzing image...') }}</div>';

                fetch(imageForm.action, {
                    method: 'POST',
                    body: new FormData(imageForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                }).then(function (payload) {
                    if (!payload.ok) {
                        throw new Error(payload.data.message || '{{ translate('Image search failed.') }}');
                    }

                    if (payload.data.query) {
                        searchInput.value = payload.data.query;
                        scopeInput.value = 'products';
                        searchForm.submit();
                    }
                }).catch(function (error) {
                    imageFeedback.className = 'mt-3';
                    imageFeedback.innerHTML = '<div class="alert alert-danger mb-0">' + error.message + '</div>';
                });
            });
        }
    });
</script>
@endsection

