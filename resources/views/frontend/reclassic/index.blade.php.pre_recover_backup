@extends('frontend.layouts.app')

@section('content')
    <style>
        .home-slider {
            max-width: 100% !important;
        }
        #left-side-product-alert{
            display: none !important;
        }
        .home-banner-area .aiz-category-menu .sub-cat-menu {
            width: calc(100% - 270px);
            left: 270px;
        }
        /* #auction_products .slick-slider .slick-list .slick-slide, */
        #section_home_categories .slick-slider .slick-list .slick-slide {
            margin-bottom: -4px;
        }
        .home-category-banner .home-category-name{
            bottom: -50px;
        }
        @media (min-width: 992px){
            .home-side-panel{
                width: 230px;
            }
        }
        @media (max-width: 991px){
            .home-banner-area .container,
            .home-banner-area .layout-container{
                min-width: 0;
                padding-left: 15px !important;
                padding-right: 15px!important;
            }
        }
        @media (max-width: 767px){
            #flash_deal .flash-deals-baner{
                height: 203px !important;
            }
        }
        .home-side-panel{
            background: #fff;
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            box-sizing: border-box;
        }
        .home-side-card{
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #fff8f1 100%);
            border: 1px solid #f5e7d8;
            padding: 16px;
            box-sizing: border-box;
        }
        .home-side-card + .home-side-card{
            margin-top: 14px;
        }
        .home-side-btn{
            display: block;
            width: 100%;
            border-radius: 999px;
            text-align: center;
            font-weight: 700;
            padding: 10px 16px;
            text-decoration: none !important;
        }
        .home-side-btn-primary{
            background: linear-gradient(180deg, #ff7a14 0%, #ff5a00 100%);
            color: #fff !important;
            box-shadow: 0 8px 18px rgba(255, 106, 0, 0.24);
        }
        .home-side-btn-outline{
            margin-top: 10px;
            border: 2px solid #ff8a3d;
            color: #ff6a00 !important;
            background: #fff;
        }
        .home-side-assurance-icon{
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #fff1cc;
            color: #f59e0b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .home-side-link{
            color: #60a5fa !important;
            font-weight: 700;
            text-decoration: none !important;
        }
        .home-feature-strip{
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0;
            margin-top: 18px;
            padding: 16px 10px;
            background: #fff;
            border: 1px solid #f0f1f3;
            border-radius: 0;
            box-shadow: 0 3px 18px rgba(15, 23, 42, 0.06);
        }
        .home-feature-item{
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 0;
            padding: 10px 18px;
            position: relative;
            text-align: center;
        }
        .home-feature-item:not(:last-child)::after{
            content: "";
            position: absolute;
            top: 10px;
            right: 0;
            width: 1px;
            height: calc(100% - 20px);
            background: #f1f3f5;
        }
        .home-feature-icon{
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #222;
        }
        .home-feature-title{
            color: #1f1f1f;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 0;
        }
        .home-feature-text{
            color: #5f6368;
            font-size: 12px;
            line-height: 1.35;
        }
        .home-feature-copy{
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
        }
        .home-feature-icon svg{
            width: 40px;
            height: 40px;
            display: block;
        }
        .home-feature-icon .accent{
            stroke: #ff6a00;
        }
        .home-feature-icon .base{
            stroke: #23262b;
        }
        @media (max-width: 1199px){
            .home-feature-strip{
                grid-template-columns: repeat(3, minmax(0, 1fr));
                padding: 12px 8px;
            }
            .home-feature-item:nth-child(3)::after{
                display: none;
            }
        }
        @media (max-width: 767px){
            .home-feature-strip{
                grid-template-columns: repeat(1, minmax(0, 1fr));
                padding: 10px 0;
            }
            .home-feature-item{
                padding: 12px 16px;
            }
            .home-feature-item::after{
                display: none;
            }
        }
    </style>

    @php $lang = get_system_language()->code;  @endphp

    <!-- home banner area -->
    <div class="home-banner-area mb-3" style="">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="row gutters-12 position-relative">
                <!-- category menu -->
                <div class="position-static d-none d-xl-block col-auto">
                    @include('frontend.'.get_setting("homepage_select").'.partials.category_menu')
                </div>

                <div class="col-lg mt-4">
                    <!-- Sliders -->
                    @if (get_setting('home_slider_images', null, $lang) != null)
                        <div class="home-slider">
                            <div class="aiz-carousel overflow-hidden rounded-2" data-autoplay="true" data-infinite="true">
                                @php
                                    $decoded_slider_images = json_decode(get_setting('home_slider_images', null, $lang), true);
                                    $sliders = get_slider_images($decoded_slider_images);
                                    $home_slider_links = get_setting('home_slider_links', null, $lang);
                                @endphp
                                @foreach ($sliders as $key => $slider)
                                    <div class="carousel-box">
                                        <a class="d-block" href="{{ isset(json_decode($home_slider_links, true)[$key]) ? json_decode($home_slider_links, true)[$key] : '' }}">
                                            <img
                                                class="d-block mw-100 img-fit h-180px h-md-320px @if(count($featured_categories) == 0) h-lg-530px @else h-lg-370px @endif"
                                                src="{{ $slider ? my_asset($slider->file_name) : static_asset('assets/img/placeholder.jpg') }}"
                                                alt="{{ env('APP_NAME')}} promo"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';"
                                            >
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="home-feature-strip">
                        <div class="home-feature-item">
                            <span class="home-feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 32 32" fill="none">
                                    <path class="base" d="M16 4 25 9.2v11.1L16 25.6 7 20.3V9.2L16 4Z" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path class="accent" d="M16 4v10.5M25 9.2 16 14.5M16 14.5 7 9.2" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div class="home-feature-copy">
                                <div class="home-feature-title">{{ translate('Millions of Products') }}</div>
                                <div class="home-feature-text">{{ translate('to choose from') }}</div>
                            </div>
                        </div>
                        <div class="home-feature-item">
                            <span class="home-feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 32 32" fill="none">
                                    <path class="base" d="M16 5.2 24.5 8.6v7.8c0 5.1-3.4 8.5-8.5 10.4-5.1-1.9-8.5-5.3-8.5-10.4V8.6L16 5.2Z" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path class="accent" d="m12.5 16.3 2.4 2.5 4.8-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div class="home-feature-copy">
                                <div class="home-feature-title">{{ translate('Verified Suppliers') }}</div>
                                <div class="home-feature-text">{{ translate('around the world') }}</div>
                            </div>
                        </div>
                        <div class="home-feature-item">
                            <span class="home-feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 32 32" fill="none">
                                    <path class="base" d="M16 5.2 24.5 8.6v7.8c0 5.1-3.4 8.5-8.5 10.4-5.1-1.9-8.5-5.3-8.5-10.4V8.6L16 5.2Z" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path class="accent" d="m12.5 16.3 2.4 2.5 4.8-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div class="home-feature-copy">
                                <div class="home-feature-title">{{ translate('Secure Payments') }}</div>
                                <div class="home-feature-text">{{ translate('multiple options') }}</div>
                            </div>
                        </div>
                        <div class="home-feature-item">
                            <span class="home-feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 32 32" fill="none">
                                    <path class="base" d="M5.5 9.5h13v10h-13zM18.5 12h4.4l3.1 3.2v4.3h-7.5z" stroke-width="1.8" stroke-linejoin="round"/>
                                    <circle class="base" cx="10.2" cy="22.3" r="1.8" stroke-width="1.8"/>
                                    <circle class="accent" cx="22.7" cy="22.3" r="1.8" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <div class="home-feature-copy">
                                <div class="home-feature-title">{{ translate('On-time delivery') }}</div>
                                <div class="home-feature-text">{{ translate('worldwide shipping') }}</div>
                            </div>
                        </div>
                        <div class="home-feature-item">
                            <span class="home-feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 32 32" fill="none">
                                    <path class="base" d="M9 16a7 7 0 1 1 14 0" stroke-width="1.8" stroke-linecap="round"/>
                                    <path class="base" d="M9 16h-1.2A2.3 2.3 0 0 0 5.5 18.3v2.2a2.3 2.3 0 0 0 2.3 2.3H10V16Zm14 0h1.2a2.3 2.3 0 0 1 2.3 2.3v2.2a2.3 2.3 0 0 1-2.3 2.3H22V16Z" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path class="accent" d="M21.5 24.5c-1 .9-2.6 1.5-4.5 1.5h-2" stroke-width="1.8" stroke-linecap="round"/>
                                    <circle class="accent" cx="21.8" cy="24.3" r="1.3" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <div class="home-feature-copy">
                                <div class="home-feature-title">{{ translate('24/7 Support') }}</div>
                                <div class="home-feature-text">{{ translate("we're here to help") }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-auto mt-4">
                    <div class="home-side-panel">
                        <div class="home-side-card">
                            <div class="fs-20 text-dark mb-1">{{ translate('Welcome to') }}</div>
                            <div class="fs-26 fw-800 text-dark mb-3">{{ config('app.name') }}</div>
                            <a href="{{ route('user.login') }}" class="home-side-btn home-side-btn-primary">{{ translate('Sign in') }}</a>
                            <a href="{{ route('user.registration') }}" class="home-side-btn home-side-btn-outline">{{ translate('Join free') }}</a>
                        </div>

                        <div class="home-side-card">
                            <div class="d-flex align-items-center mb-2">
                                <span class="home-side-assurance-icon"><i class="las la-shield-alt"></i></span>
                                <span class="ml-2 fs-21 fw-700 text-dark">{{ translate('Trade Assurance') }}</span>
                            </div>
                            <p class="mb-3 fs-16 text-secondary" style="line-height: 1.55;">
                                {{ translate('Protects your orders on quality, on-time shipment and payment.') }}
                            </p>
                            <a href="{{ route('home') }}" class="home-side-link">{{ translate('Learn more') }} <i class="las la-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Banner section 1 -->
    @php $homeBanner1Images = get_setting('home_banner1_images', null, $lang);   @endphp
    @if ($homeBanner1Images != null)
        <div class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
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
                                    class="d-block text-reset rounded-2 overflow-hidden">
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

    <!-- Flash Deal -->
    @php
        $flash_deal = get_featured_flash_deal();
        $flash_deal_bg = get_setting('flash_deal_bg_color');
    @endphp
    @if ($flash_deal != null)
        <section class="mb-2 mb-md-3 mt-2 mt-md-3" id="flash_deal">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                <div class="rounded-2 overflow-hidden p-3 p-md-2rem @if(get_setting('flash_deal_section_outline') == 1) border @endif" style="background: {{ $flash_deal_bg != null ? $flash_deal_bg : '#fff9ed' }}; border-color: {{ get_setting('flash_deal_section_outline_color') }} !important;">
                    <!-- Top Section -->
                    <div class="d-flex flex-wrap align-items-baseline justify-content-center justify-content-sm-between mb-2 mb-md-3 position-relative">
                        <div class="d-flex flex-wrap align-items-center">
                            <!-- Title -->
                            <h3 class="fs-22 fs-md-20 fw-700 mb-2 mb-sm-0">
                                <span class="d-inline-block">{{ translate('Flash Sale') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="24" viewBox="0 0 16 24"
                                    class="ml-2">
                                    <path id="Path_28795" data-name="Path 28795"
                                        d="M30.953,13.695a.474.474,0,0,0-.424-.25h-4.9l3.917-7.81a.423.423,0,0,0-.028-.428.477.477,0,0,0-.4-.207H21.588a.473.473,0,0,0-.429.263L15.041,18.151a.423.423,0,0,0,.034.423.478.478,0,0,0,.4.2h4.593l-2.229,9.683a.438.438,0,0,0,.259.5.489.489,0,0,0,.571-.127L30.9,14.164a.425.425,0,0,0,.054-.469Z"
                                        transform="translate(-15 -5)" fill="#fcc201" />
                                </svg>
                            </h3>
                            <!-- Countdown -->
                            <div class="aiz-count-down align-items-center ml-2 mb-2 mb-lg-0" data-date="{{ date('Y/m/d H:i:s', $flash_deal->end_date) }}"></div>
                        </div>

                        <!-- Links -->
                        <div>
                            <div class="text-dark d-flex align-items-center mb-0">
                                <a href="{{ route('flash-deals') }}"
                                    class="fs-10 fs-md-12 fw-700 has-transition text-reset opacity-60 hov-opacity-100 hov-text-primary animate-underline-primary mr-3">{{ translate('View All Flash Sale') }}</a>
                                <span class=" border-left border-soft-light border-width-2 pl-3">
                                    <a href="{{ route('flash-deal-details', $flash_deal->slug) }}"
                                        class="fs-10 fs-md-12 fw-700 has-transition text-reset opacity-60 hov-opacity-100 hov-text-primary animate-underline-primary">{{ translate('View All Products from This Flash Sale') }}</a>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row gutters-16 align-items-center">
                        <!-- Flash Deals Baner -->
                        <div class="col-auto">
                            <a href="{{ route('flash-deal-details', $flash_deal->slug) }}">
                                <div class=" size-180px size-md-200px size-lg-280px rounded-2 overflow-hidden"
                                    style="background-image: url('{{ uploaded_asset($flash_deal->banner) }}'); background-size: cover; background-position: center center;">
                                </div>
                            </a>
                        </div>

                        <div class="col">
                            <!-- Flash Deals Products -->
                            @php
                                $flash_deal_products = get_flash_deal_products($flash_deal->id);
                            @endphp
                            <div class="pr-md-3">
                                <div class="aiz-carousel gutters-16 arrow-inactive-none arrow-x-0"
                                data-items="5" data-xxl-items="5" data-xl-items="4" data-lg-items="3" data-md-items="2.5"
                                data-sm-items="2.3" data-xs-items="1" data-arrows="true" data-dots="false">
                                @foreach ($flash_deal_products as $key => $flash_deal_product)
                                    <div class="carousel-box position-relative has-transition hov-animate-outline">
                                        @if ($flash_deal_product->product != null && $flash_deal_product->product->published != 0)
                                            @php
                                                $product_url = route('product', $flash_deal_product->product->slug);
                                                if ($flash_deal_product->product->auction_product == 1) {
                                                    $product_url = route('auction-product', $flash_deal_product->product->slug);
                                                }
                                            @endphp
                                            <div
                                                class="aiz-card-box h-180px h-md-200px h-lg-280px flash-deal-item position-relative text-center">
                                                <a href="{{ $product_url }}"
                                                    class="d-block overflow-hidden hov-scale-img"
                                                    title="{{ $flash_deal_product->product->getTranslation('name') }}">
                                                    <!-- Image -->
                                                    <img src="{{ get_image($flash_deal_product->product->thumbnail) }}"
                                                        class="lazyload h-100px h-md-120px h-lg-170px mw-100 mx-auto has-transition rounded-2"
                                                        alt="{{ $flash_deal_product->product->getTranslation('name') }}"
                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                    <!-- Product name -->
                                                    <h3 class="fw-400 fs-13 text-truncate-2 lh-1-4 mb-0 h-40px text-center pt-1 px-1 mt-1">
                                                        <a href="{{ $product_url }}" class="d-block text-reset hov-text-primary"
                                                            title="{{ $flash_deal_product->product->getTranslation('name') }}">{{ $flash_deal_product->product->getTranslation('name') }}</a>
                                                    </h3>
                                                    <!-- Price -->
                                                    <h4 class="fs-14 d-flex justify-content-center mt-3">
                                                        @if ($flash_deal_product->auction_product == 0)
                                                            <!-- Previous price -->
                                                            @if (home_base_price($flash_deal_product->product) != home_discounted_base_price($flash_deal_product->product))
                                                                <span class="disc-amount has-transition">
                                                                    <del class="fw-400 text-secondary mr-1">{{ home_base_price($flash_deal_product->product) }}</del>
                                                                </span>
                                                            @endif
                                                            <!-- price -->
                                                            <span class="fw-700 text-primary">{{ home_discounted_base_price($flash_deal_product->product) }}</span>
                                                        @endif
                                                    </h4>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Featured Products -->
    <div id="section_featured">

    </div>


    @if (addon_is_activated('preorder'))
        <!-- Banner Section 2 -->
        @php $homepreorder_banner_1Images = get_setting('home_preorder_banner_1_images', null, $lang);   @endphp
        @if ($homepreorder_banner_1Images != null)
            <div class="mb-2 mb-md-3 mt-2 mt-md-3">
                <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                    @php
                        $banner_2_imags = json_decode($homepreorder_banner_1Images);
                        $data_md = count($banner_2_imags) >= 2 ? 2 : 1;
                        $home_preorder_banner_1_links = get_setting('home_preorder_banner_1_links', null, $lang);
                    @endphp
                    <div class="rounded-2 overflow-hidden">
                        <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                        data-items="{{ count($banner_2_imags) }}" data-xxl-items="{{ count($banner_2_imags) }}"
                        data-xl-items="{{ count($banner_2_imags) }}" data-lg-items="{{ $data_md }}"
                        data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                        data-dots="false">
                        @foreach ($banner_2_imags as $key => $value)
                            <div class="carousel-box overflow-hidden hov-scale-img">
                                <a href="{{ isset(json_decode($home_preorder_banner_1_links, true)[$key]) ? json_decode($home_preorder_banner_1_links, true)[$key] : '' }}"
                                    class="d-block text-reset rounded-2 overflow-hidden">
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
    


        <!-- Featured Preorder Products -->
        <div id="section_featured_preorder_products">

        </div>
    @endif

    <!-- Banner Section 2 -->
    @php $homeBanner2Images = get_setting('home_banner2_images', null, $lang);   @endphp
    @if ($homeBanner2Images != null)
        <div class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                @php
                    $banner_2_imags = json_decode($homeBanner2Images);
                    $data_md = count($banner_2_imags) >= 2 ? 2 : 1;
                    $home_banner2_links = get_setting('home_banner2_links', null, $lang);
                @endphp
                <div class="rounded-2 overflow-hidden">
                    <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                    data-items="{{ count($banner_2_imags) }}" data-xxl-items="{{ count($banner_2_imags) }}"
                    data-xl-items="{{ count($banner_2_imags) }}" data-lg-items="{{ $data_md }}"
                    data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                    data-dots="false">
                    @foreach ($banner_2_imags as $key => $value)
                        <div class="carousel-box overflow-hidden hov-scale-img">
                            <a href="{{ isset(json_decode($home_banner2_links, true)[$key]) ? json_decode($home_banner2_links, true)[$key] : '' }}"
                                class="d-block text-reset rounded-2 overflow-hidden">
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

    <!-- Best Selling  -->
    <div id="section_best_selling">

    </div>

    <!-- New Products -->
    <div id="section_newest">

    </div>

    <!-- Banner Section 3 -->
    @php $homeBanner3Images = get_setting('home_banner3_images', null, $lang);   @endphp
    @if ($homeBanner3Images != null)
        <div class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
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
                                class="d-block text-reset rounded-2 overflow-hidden">
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
        <div class="" style="background-color: {{ get_setting('cupon_background_color', '#fff9ed') }}">
            <div class="container">
                <div class="position-relative py-5">
                    <div class="text-center text-xl-left position-relative z-5">
                        <div class="d-lg-flex">
                            <div class="mb-3 mb-lg-0">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="109.602" height="93.34" viewBox="0 0 109.602 93.34">
                                    <defs>
                                      <clipPath id="clip-path">
                                        <path id="Union_10" data-name="Union 10" d="M12263,13778v-15h64v-41h12v56Z" transform="translate(-11966 -8442.865)" fill="none" stroke="var(--{{ get_setting('cupon_text_color') }})" stroke-width="2"/>
                                      </clipPath>
                                    </defs>
                                    <g id="Group_25375" data-name="Group 25375" transform="translate(-274.201 -5254.611)">
                                      <g id="Mask_Group_23" data-name="Mask Group 23" transform="translate(-3652.459 1785.452) rotate(-45)" clip-path="url(#clip-path)">
                                        <g id="Group_24322" data-name="Group 24322" transform="translate(207 18.136)">
                                          <g id="Subtraction_167" data-name="Subtraction 167" transform="translate(-12177 -8458)" fill="none">
                                            <path d="M12335,13770h-56a8.009,8.009,0,0,1-8-8v-8a8,8,0,0,0,0-16v-8a8.009,8.009,0,0,1,8-8h56a8.009,8.009,0,0,1,8,8v8a8,8,0,0,0,0,16v8A8.009,8.009,0,0,1,12335,13770Z" stroke="none"/>
                                            <path d="M 12335.0009765625 13768.0009765625 C 12338.3095703125 13768.0009765625 12341.0009765625 13765.30859375 12341.0009765625 13762 L 12341.0009765625 13755.798828125 C 12336.4423828125 13754.8701171875 12333.0009765625 13750.8291015625 12333.0009765625 13746 C 12333.0009765625 13741.171875 12336.4423828125 13737.130859375 12341.0009765625 13736.201171875 L 12341.0009765625 13729.9990234375 C 12341.0009765625 13726.6904296875 12338.3095703125 13723.9990234375 12335.0009765625 13723.9990234375 L 12278.9990234375 13723.9990234375 C 12275.6904296875 13723.9990234375 12272.9990234375 13726.6904296875 12272.9990234375 13729.9990234375 L 12272.9990234375 13736.201171875 C 12277.5576171875 13737.1298828125 12280.9990234375 13741.1708984375 12280.9990234375 13746 C 12280.9990234375 13750.828125 12277.5576171875 13754.869140625 12272.9990234375 13755.798828125 L 12272.9990234375 13762 C 12272.9990234375 13765.30859375 12275.6904296875 13768.0009765625 12278.9990234375 13768.0009765625 L 12335.0009765625 13768.0009765625 M 12335.0009765625 13770.0009765625 L 12278.9990234375 13770.0009765625 C 12274.587890625 13770.0009765625 12270.9990234375 13766.412109375 12270.9990234375 13762 L 12270.9990234375 13754 C 12275.4111328125 13753.9990234375 12278.9990234375 13750.4111328125 12278.9990234375 13746 C 12278.9990234375 13741.5888671875 12275.41015625 13738 12270.9990234375 13738 L 12270.9990234375 13729.9990234375 C 12270.9990234375 13725.587890625 12274.587890625 13721.9990234375 12278.9990234375 13721.9990234375 L 12335.0009765625 13721.9990234375 C 12339.412109375 13721.9990234375 12343.0009765625 13725.587890625 12343.0009765625 13729.9990234375 L 12343.0009765625 13738 C 12338.5888671875 13738.0009765625 12335.0009765625 13741.5888671875 12335.0009765625 13746 C 12335.0009765625 13750.4111328125 12338.58984375 13754 12343.0009765625 13754 L 12343.0009765625 13762 C 12343.0009765625 13766.412109375 12339.412109375 13770.0009765625 12335.0009765625 13770.0009765625 Z" stroke="none" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                          </g>
                                        </g>
                                      </g>
                                      <g id="Group_24321" data-name="Group 24321" transform="translate(-3514.477 1653.317) rotate(-45)">
                                        <g id="Subtraction_167-2" data-name="Subtraction 167" transform="translate(-12177 -8458)" fill="none">
                                          <path d="M12335,13770h-56a8.009,8.009,0,0,1-8-8v-8a8,8,0,0,0,0-16v-8a8.009,8.009,0,0,1,8-8h56a8.009,8.009,0,0,1,8,8v8a8,8,0,0,0,0,16v8A8.009,8.009,0,0,1,12335,13770Z" stroke="none"/>
                                          <path d="M 12335.0009765625 13768.0009765625 C 12338.3095703125 13768.0009765625 12341.0009765625 13765.30859375 12341.0009765625 13762 L 12341.0009765625 13755.798828125 C 12336.4423828125 13754.8701171875 12333.0009765625 13750.8291015625 12333.0009765625 13746 C 12333.0009765625 13741.171875 12336.4423828125 13737.130859375 12341.0009765625 13736.201171875 L 12341.0009765625 13729.9990234375 C 12341.0009765625 13726.6904296875 12338.3095703125 13723.9990234375 12335.0009765625 13723.9990234375 L 12278.9990234375 13723.9990234375 C 12275.6904296875 13723.9990234375 12272.9990234375 13726.6904296875 12272.9990234375 13729.9990234375 L 12272.9990234375 13736.201171875 C 12277.5576171875 13737.1298828125 12280.9990234375 13741.1708984375 12280.9990234375 13746 C 12280.9990234375 13750.828125 12277.5576171875 13754.869140625 12272.9990234375 13755.798828125 L 12272.9990234375 13762 C 12272.9990234375 13765.30859375 12275.6904296875 13768.0009765625 12278.9990234375 13768.0009765625 L 12335.0009765625 13768.0009765625 M 12335.0009765625 13770.0009765625 L 12278.9990234375 13770.0009765625 C 12274.587890625 13770.0009765625 12270.9990234375 13766.412109375 12270.9990234375 13762 L 12270.9990234375 13754 C 12275.4111328125 13753.9990234375 12278.9990234375 13750.4111328125 12278.9990234375 13746 C 12278.9990234375 13741.5888671875 12275.41015625 13738 12270.9990234375 13738 L 12270.9990234375 13729.9990234375 C 12270.9990234375 13725.587890625 12274.587890625 13721.9990234375 12278.9990234375 13721.9990234375 L 12335.0009765625 13721.9990234375 C 12339.412109375 13721.9990234375 12343.0009765625 13725.587890625 12343.0009765625 13729.9990234375 L 12343.0009765625 13738 C 12338.5888671875 13738.0009765625 12335.0009765625 13741.5888671875 12335.0009765625 13746 C 12335.0009765625 13750.4111328125 12338.58984375 13754 12343.0009765625 13754 L 12343.0009765625 13762 C 12343.0009765625 13766.412109375 12339.412109375 13770.0009765625 12335.0009765625 13770.0009765625 Z" stroke="none" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                        </g>
                                        <g id="Group_24325" data-name="Group 24325">
                                          <rect id="Rectangle_18578" data-name="Rectangle 18578" width="8" height="2" transform="translate(120 5287)" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                          <rect id="Rectangle_18579" data-name="Rectangle 18579" width="8" height="2" transform="translate(132 5287)" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                          <rect id="Rectangle_18581" data-name="Rectangle 18581" width="8" height="2" transform="translate(144 5287)" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                          <rect id="Rectangle_18580" data-name="Rectangle 18580" width="8" height="2" transform="translate(108 5287)" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                        </g>
                                      </g>
                                    </g>
                                </svg>
                            </div>
                            <div class="ml-lg-3">
                                <h5 class="fs-36 fw-700 text-{{ get_setting('cupon_text_color') }} mb-3">{{ translate(get_setting('cupon_title')) }}</h5>
                                <h5 class="fs-20 fw-400 text-{{ get_setting('cupon_text_color') }}">{{ translate(get_setting('cupon_subtitle')) }}</h5>
                                <div class="mt-5 pt-5">
                                    <a href="{{ route('coupons.all') }}" class="btn btn-secondary rounded-2 fs-16 px-4"
                                        style="box-shadow: 0px 20px 30px rgba(0, 0, 0, 0.16);">{{ translate('View All Coupons') }}</a>
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
    <div id="section_home_categories">

    </div>


    @if (addon_is_activated('preorder'))
        <!-- Newest Preorder Products -->
        @include('preorder.frontend.home_page.newest_preorder')
    @endif


    <!-- Classified Product -->
    @if (get_setting('classified_product') == 1)
        @php
            $classified_products = get_home_page_classified_products(6);
            $classified_section_bg = get_setting('classified_section_bg_color');
        @endphp
        @if (count($classified_products) > 0)
            <section class="mb-2 mb-md-3 mt-2rem">
                <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                    <div class="p-3 p-md-2rem rounded-2 overflow-hidden @if(get_setting('classified_section_outline') == 1) border @endif"
                        style="background: {{ $classified_section_bg != null ? $classified_section_bg : '#fff9ed' }}; border-color: {{ get_setting('classified_section_outline_color') }} !important;">
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
                            <div class="mb-3 rounded-2 overflow-hidden hov-scale-img d-none d-md-block">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ uploaded_asset($classifiedBannerImage) }}"
                                    alt="{{ env('APP_NAME') }} promo" class="lazyload img-fit h-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </div>
                            <div class="mb-3 rounded-2 overflow-hidden hov-scale-img d-md-none">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ $classifiedBannerImageSmall != null ? uploaded_asset($classifiedBannerImageSmall) : uploaded_asset($classifiedBannerImage) }}"
                                    alt="{{ env('APP_NAME') }} promo" class="lazyload img-fit h-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </div>
                        @endif
                        <!-- Products Section -->
                        <div class="">
                            <div class="row gutters-16">
                                @foreach ($classified_products as $key => $classified_product)
                                    <div
                                        class="col-xxl-4 col-md-6 has-transition hov-shadow-out z-1">
                                        <div class="aiz-card-box py-2 has-transition">
                                            <div class="row hov-scale-img">
                                                <div class="col-4 col-md-5 mb-3 mb-md-0">
                                                    <a href="{{ route('customer.product', $classified_product->slug) }}"
                                                        class="d-block rounded-2 overflow-hidden h-auto h-md-150px text-center">
                                                        <img class="img-fluid lazyload mx-auto has-transition"
                                                            src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                            data-src="{{ isset($classified_product->thumbnail->file_name) ? my_asset($classified_product->thumbnail->file_name) : static_asset('assets/img/placeholder.jpg') }}"
                                                            alt="{{ $classified_product->getTranslation('name') }}"
                                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                    </a>
                                                </div>
                                                <div class="col py-2">
                                                    <h3
                                                        class="fw-400 fs-14 text-dark text-truncate-2 lh-1-4 mb-3 h-35px d-none d-sm-block">
                                                        <a href="{{ route('customer.product', $classified_product->slug) }}"
                                                            class="d-block text-reset hov-text-primary">{{ $classified_product->getTranslation('name') }}</a>
                                                    </h3>
                                                    <div class="d-md-flex d-lg-block justify-content-between">
                                                        <div class="fs-14 mb-3">
                                                            <span
                                                                class="text-secondary">{{ $classified_product->user ? $classified_product->user->name : '' }}</span><br>
                                                            <span
                                                                class="fw-700 text-primary">{{ single_price($classified_product->unit_price) }}</span>
                                                        </div>
                                                        @if ($classified_product->conditon == 'new')
                                                            <span
                                                                class="badge badge-md badge-inline badge-soft-info fs-13 fw-700 px-3 text-info"
                                                                style="border-radius: 20px;">{{ translate('New') }}</span>
                                                        @elseif($classified_product->conditon == 'used')
                                                            <span
                                                                class="badge badge-md badge-inline badge-soft-danger fs-13 fw-700 px-3 text-danger"
                                                                style="border-radius: 20px;">{{ translate('Used') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endif

    <!-- Top Sellers -->
    @if (get_setting('vendor_system_activation') == 1)
        @php
            $best_selers = get_best_sellers(6);
            $sellers_section_bg = get_setting('sellers_section_bg_color');
        @endphp
        @if (count($best_selers) > 0)
        <section class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                <div class="p-3 p-md-2rem rounded-2 @if(get_setting('sellers_section_outline') == 1) border @endif"
                    style="background: {{ $sellers_section_bg != null ? $sellers_section_bg : '#fff9ed' }}; border-color: {{ get_setting('sellers_section_outline_color') }} !important; padding-bottom: 1rem !important;">
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
                    <div class="row gutters-16">
                        @foreach ($best_selers as $key => $seller)
                        <div class="col-xl-4 col-md-6 py-3 py-md-4 has-transition hov-shadow-out z-1">
                            <div class="d-flex align-items-center">
                                <!-- Shop logo & Verification Status -->
                                <div class="position-relative">
                                    <a href="{{ route('shop.visit', $seller->slug) }}"
                                        class="d-block mx-auto size-100px size-lg-120px border overflow-hidden hov-scale-img"
                                        tabindex="0"
                                        style="border: 1px solid #e5e5e5; border-radius: 50%; box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.06);">
                                        <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                            data-src="{{ uploaded_asset($seller->logo) }}" alt="{{ $seller->name }}"
                                            class="img-fit h-100 lazyload has-transition"
                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                    </a>
                                    <div class="absolute-top-left z-1 ml-2 mt-1 rounded-content bg-white">
                                        @if ($seller->verification_status == 1)
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24.001" height="24"
                                                viewBox="0 0 24.001 24">
                                                <g id="Group_25929" data-name="Group 25929"
                                                    transform="translate(-480 -345)">
                                                    <circle id="Ellipse_637" data-name="Ellipse 637" cx="12"
                                                        cy="12" r="12" transform="translate(480 345)"
                                                        fill="#fff" />
                                                    <g id="Group_25927" data-name="Group 25927"
                                                        transform="translate(480 345)">
                                                        <path id="Union_5" data-name="Union 5"
                                                            d="M0,12A12,12,0,1,1,12,24,12,12,0,0,1,0,12Zm1.2,0A10.8,10.8,0,1,0,12,1.2,10.812,10.812,0,0,0,1.2,12Zm1.2,0A9.6,9.6,0,1,1,12,21.6,9.611,9.611,0,0,1,2.4,12Zm5.115-1.244a1.083,1.083,0,0,0,0,1.529l3.059,3.059a1.081,1.081,0,0,0,1.529,0l5.1-5.1a1.084,1.084,0,0,0,0-1.53,1.081,1.081,0,0,0-1.529,0L11.339,13.05,9.045,10.756a1.082,1.082,0,0,0-1.53,0Z"
                                                            transform="translate(0 0)" fill="#85b567" />
                                                    </g>
                                                </g>
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24.001" height="24"
                                                viewBox="0 0 24.001 24">
                                                <g id="Group_25929" data-name="Group 25929"
                                                    transform="translate(-480 -345)">
                                                    <circle id="Ellipse_637" data-name="Ellipse 637" cx="12"
                                                        cy="12" r="12" transform="translate(480 345)"
                                                        fill="#fff" />
                                                    <g id="Group_25927" data-name="Group 25927"
                                                        transform="translate(480 345)">
                                                        <path id="Union_5" data-name="Union 5"
                                                            d="M0,12A12,12,0,1,1,12,24,12,12,0,0,1,0,12Zm1.2,0A10.8,10.8,0,1,0,12,1.2,10.812,10.812,0,0,0,1.2,12Zm1.2,0A9.6,9.6,0,1,1,12,21.6,9.611,9.611,0,0,1,2.4,12Zm5.115-1.244a1.083,1.083,0,0,0,0,1.529l3.059,3.059a1.081,1.081,0,0,0,1.529,0l5.1-5.1a1.084,1.084,0,0,0,0-1.53,1.081,1.081,0,0,0-1.529,0L11.339,13.05,9.045,10.756a1.082,1.082,0,0,0-1.53,0Z"
                                                            transform="translate(0 0)" fill="red" />
                                                    </g>
                                                </g>
                                            </svg>
                                        @endif
                                    </div>
                                </div>

                                <div class="ml-2 ml-lg-4">
                                    <!-- Shop name -->
                                    <h2 class="fs-14 fw-700 text-dark text-truncate-2 mb-1">
                                        <a href="{{ route('shop.visit', $seller->slug) }}"
                                            class="text-reset hov-text-primary" tabindex="0">{{ $seller->name }}</a>
                                    </h2>
                                    <!-- Shop Rating -->
                                    <div class="rating rating-mr-1 text-dark mb-2">
                                        {{ renderStarRating($seller->rating) }}
                                        <span class="opacity-60 fs-14">({{ $seller->num_of_reviews }}
                                            {{ translate('Reviews') }})</span>
                                    </div>
                                    <!-- Visit Button -->
                                    <a href="{{ route('shop.visit', $seller->slug) }}" class="visite-btn">
                                        <span class="button-text">{{ ucfirst(translate('Visit Store')) }}</span>
                                        <span class="icon-arrow"></span>
                                    </a>
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

    <!-- Top Brands -->
    @if (get_setting('top_brands') != null)
        @php
            $brands_section_bg = get_setting('brands_section_bg_color');
        @endphp
        <section class="mb-2 mb-md-3 mt-2 mt-md-3 pb-2 pb-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                <div class="p-3 p-md-2rem rounded-2 @if(get_setting('brands_section_outline') == 1) border @endif"
                    style="background: {{ $brands_section_bg != null ? $brands_section_bg : '#f0f2f5' }}; border-color: {{ get_setting('brands_section_outline_color') }} !important; padding-bottom: 1rem !important;">
                    <!-- Top Section -->
                    <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                        <!-- Title -->
                        <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">{{ translate('Top Brands') }}</h3>
                        <!-- Links -->
                        <div class="d-flex">
                            <a class="text-blue fs-10 fs-md-12 fw-700 hov-text-primary animate-underline-primary"
                                href="{{ route('brands.all') }}">{{ translate('View All Brands') }}</a>
                        </div>
                    </div>
                    <!-- Brands Section -->
                    <div class="row gutters-16">
                        @php
                            $top_brands = json_decode(get_setting('top_brands'));
                            $brands = get_brands($top_brands);
                        @endphp
                        @foreach ($brands as $brand)
                            <div class="col-xl-3 col-lg-4 col-6 my-3">
                                <a href="{{ route('products.brand', $brand->slug) }}" class="d-block has-transition hov-shadow-out z-1 hov-scale-img rounded-2 overflow-hidden">
                                    <span class="d-flex flex-column flex-sm-row align-items-center">
                                        <span class="d-flex align-items-center bg-white size-80px p-2 rounded-2 overflow-hidden">
                                            <img src="{{ $brand->logo != null ? uploaded_asset($brand->logo) : static_asset('assets/img/placeholder.jpg') }}"
                                            class="lazyload w-100 has-transition"
                                            alt="{{ $brand->getTranslation('name') }}"
                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                        </span>
                                        <span class="text-center text-dark fs-12 fs-md-14 fw-700 mt-2 mt-sm-0 ml-sm-4">
                                            {{ $brand->getTranslation('name') }}
                                        </span>
                                    </span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

@endsection

