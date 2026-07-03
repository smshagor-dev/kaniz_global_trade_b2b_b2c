@php
    $cart_added = [];
    $productCardPrice = product_card_price_label($product);
    $productCardReview = product_card_review_summary($product);
    $productCardCountry = product_card_country($product);
    $productCardMoq = max(1, (int) ($product->min_qty ?: 1));
    $productSupplierSummary = getProductSupplierSummary($product);
    $productSupplierBadges = getProductSupplierBadge($product);
    $productRfqUrl = getProductRfqUrl($product);
    $productContactSupplierUrl = getProductContactSupplierUrl($product);
@endphp
@once
    <style>
        .kgt-product-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease;
        }
        .kgt-product-card:hover {
            border-color: #d7dce3;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.10);
            transform: translateY(-2px);
        }
        .kgt-product-media {
            background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
            aspect-ratio: 1 / 1;
        }
        .kgt-product-media img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .kgt-badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .kgt-badge {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }
        .kgt-badge-verified {
            background: #ecfdf3;
            color: #047857;
        }
        .kgt-badge-gold {
            background: #fff7e6;
            color: #b45309;
        }
        .kgt-badge-b2b {
            background: #eff6ff;
            color: #1d4ed8;
        }
        .kgt-supplier-meta,
        .kgt-meta-line {
            min-width: 0;
        }
        .kgt-supplier-name {
            min-width: 0;
        }
        .kgt-supplier-name a,
        .kgt-supplier-name span {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: bottom;
        }
        .kgt-country-flag {
            display: inline-flex;
            align-items: center;
            margin-right: 6px;
        }
        .kgt-country-flag .iti__flag {
            transform: scale(.9);
            transform-origin: left center;
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);
        }
        .kgt-action-row {
            display: flex;
            gap: 8px;
        }
        .kgt-rfq-btn,
        .kgt-contact-btn {
            flex: 1 1 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 0 10px;
            border-radius: 10px;
            border: 1px solid #dbe3ef;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }
        .kgt-rfq-btn {
            background: #fff7ed;
            color: #c2410c;
            border-color: #fed7aa;
        }
        .kgt-contact-btn {
            background: #f8fafc;
            color: #334155;
        }
        .kgt-contact-btn.disabled,
        .kgt-contact-btn[aria-disabled="true"],
        .kgt-rfq-btn.disabled,
        .kgt-rfq-btn[aria-disabled="true"] {
            opacity: .55;
            pointer-events: none;
        }
        @media (max-width: 575.98px) {
            .kgt-action-row {
                flex-direction: column;
            }
        }
    </style>
@endonce
<div class="aiz-card-box kgt-product-card h-auto bg-white py-3 hov-scale-img">
    <div class="position-relative img-fit overflow-hidden kgt-product-media">
        @php
            $product_url = route('product', $product->slug);
            if ($product->auction_product == 1) {
                $product_url = route('auction-product', $product->slug);
            }
        @endphp
        <!-- Image -->
        <a href="{{ $product_url }}" class="d-block h-100 position-relative image-hover-effect">
            <img
                class="lazyload mx-auto img-fit has-transition product-main-image"
                src="{{ get_image($product->thumbnail) }}"
                alt="{{ $product->getTranslation('name') }}"
                title="{{ $product->getTranslation('name') }}"
                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
            <img
                class="lazyload mx-auto img-fit has-transition product-hover-image position-absolute"
                src="{{ get_first_product_image($product->thumbnail, $product->photos) }}"
                alt="{{ $product->getTranslation('name') }}"
                title="{{ $product->getTranslation('name') }}"
                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
        </a>
        @php
            $badgeIndex = 0;
        @endphp
        <!-- Discount percentage tag -->
        @if (discount_in_percentage($product) > 0)
            <span class="absolute-top-left rounded rounded-4 bg-primary ml-1 mt-1 fs-11 fw-700 text-white w-35px text-center"
                style="padding-top:2px;padding-bottom:2px; top:{{ 25 * $badgeIndex }}px;">-{{ discount_in_percentage($product) }}%</span>
            @php $badgeIndex++; @endphp
        @endif
        <!-- Wholesale tag -->
        @if ($product->wholesale_product)
            <span class="absolute-top-left rounded rounded-4 fs-11 text-white fw-700 px-2 lh-1-8 ml-1 mt-1"
                style="background-color: #455a64; @if (discount_in_percentage($product) > 0) top:{{ 25 * $badgeIndex }}px; @endif">
                {{ translate('Wholesale') }}
            </span>
            @php $badgeIndex++; @endphp
        @endif
        <!-- Custom Label -->
        @php
            $customLabels = get_custom_labels($product->custom_label_id);
        @endphp
        @if ($customLabels)
            @foreach ($customLabels as $key => $customLabel)
                <span class="absolute-top-left rounded rounded-4 fs-11 fw-700 px-2 lh-1-8 ml-1 mt-1"
                    style="background-color:{{ $customLabel->background_color }};
                        color:{{ $customLabel->text_color }};
                        top:{{ 25 * $badgeIndex }}px;">
                    {{ $customLabel->text }}
                </span>
                @php $badgeIndex++; @endphp
            @endforeach
        @endif
        @if ($product->auction_product == 0)
            <!-- Desktop Icons (Top Right) -->
            <div class="d-none d-sm-block absolute-top-right aiz-p-hov-icon">
                <!-- Wishlist Icon -->
                <a href="javascript:void(0)" class="hov-svg-white" onclick="addToWishList({{ $product->id }})"
                    data-toggle="tooltip" data-title="{{ translate('Add to wishlist') }}" data-placement="left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="14.4" viewBox="0 0 16 14.4">
                        <g transform="translate(-3.05 -4.178)">
                            <path
                                d="M11.3,5.507l-.247.246L10.8,5.506A4.538,4.538,0,1,0,4.38,11.919l.247.247,6.422,6.412,6.422-6.412.247-.247A4.538,4.538,0,1,0,11.3,5.507Z"
                                transform="translate(0 0)" fill="#919199" />
                        </g>
                    </svg>
                </a>

                <!-- Compare Icon -->
                <a href="javascript:void(0)" class="hov-svg-white" onclick="addToCompare({{ $product->id }})"
                    data-toggle="tooltip" data-title="{{ translate('Add to compare') }}" data-placement="left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                        <path
                            d="M18.037,5.547v.8a.8.8,0,0,1-.8.8H7.221a.4.4,0,0,0-.4.4V9.216a.642.642,0,0,1-1.1.454L2.456,6.4a.643.643,0,0,1,0-.909L5.723,2.227a.642.642,0,0,1,1.1.454V4.342a.4.4,0,0,0,.4.4H17.234a.8.8,0,0,1,.8.8Zm-3.685,4.86a.642.642,0,0,0-1.1.454v1.661a.4.4,0,0,1-.4.4H2.84a.8.8,0,0,0-.8.8v.8a.8.8,0,0,0,.8.8H12.854a.4.4,0,0,1,.4.4V17.4a.642.642,0,0,0,1.1.454l3.267-3.268a.643.643,0,0,0,0-.909Z"
                            transform="translate(-2.037 -2.038)" fill="#919199" />
                    </svg>
                </a>
            </div>

            <!-- Mobile Icons (Bottom) -->
            <div class="d-sm-none position-absolute aiz-p-hov-icon-mobile"
                style="bottom: -10px; left: 50%; transform: translateX(-50%); z-index: 10;">
                <div class="d-inline-flex px-2 py-1 shadow-sm">
                    <!-- Cart Icon -->
                    <a href="javascript:void(0)" class="hov-svg-white d-inline-block mb-2"
                        onclick="showAddToCartModal({{ $product->id }})" data-toggle="tooltip"
                        data-title="{{ translate('Add to Cart') }}" data-placement="top">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                            <path
                                d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"
                                fill="#919199" />
                        </svg>
                    </a>

                    <!-- Compare Icon -->
                    <a href="javascript:void(0)" class="hov-svg-white d-inline-block mb-2"
                        onclick="addToCompare({{ $product->id }})" data-toggle="tooltip"
                        data-title="{{ translate('Add to compare') }}" data-placement="top">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                            <path
                                d="M18.037,5.547v.8a.8.8,0,0,1-.8.8H7.221a.4.4,0,0,0-.4.4V9.216a.642.642,0,0,1-1.1.454L2.456,6.4a.643.643,0,0,1,0-.909L5.723,2.227a.642.642,0,0,1,1.1.454V4.342a.4.4,0,0,0,.4.4H17.234a.8.8,0,0,1,.8.8Zm-3.685,4.86a.642.642,0,0,0-1.1.454v1.661a.4.4,0,0,1-.4.4H2.84a.8.8,0,0,0-.8.8v.8a.8.8,0,0,0,.8.8H12.854a.4.4,0,0,1,.4.4V17.4a.642.642,0,0,0,1.1.454l3.267-3.268a.643.643,0,0,0,0-.909Z"
                                transform="translate(-2.037 -2.038)" fill="#919199" />
                        </svg>
                    </a>

                    <!-- Wishlist Icon -->
                    <a href="javascript:void(0)" class="hov-svg-white d-inline-block"
                        onclick="addToWishList({{ $product->id }})" data-toggle="tooltip"
                        data-title="{{ translate('Add to wishlist') }}" data-placement="top">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="14.4" viewBox="0 0 16 14.4">
                            <g transform="translate(-3.05 -4.178)">
                                <path
                                    d="M11.3,5.507l-.247.246L10.8,5.506A4.538,4.538,0,1,0,4.38,11.919l.247.247,6.422,6.412,6.422-6.412.247-.247A4.538,4.538,0,1,0,11.3,5.507Z"
                                    transform="translate(0 0)" fill="#919199" />
                            </g>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Original Add to Cart (Desktop only) -->
            @php
                $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors;
                $attributes = is_string($product->attributes) ? json_decode($product->attributes, true) : $product->attributes;
            @endphp

            @if ( (is_array($colors) && count($colors) > 0) || (is_array($attributes) && count($attributes) > 0) )
                <a class="cart-btn absolute-bottom-left w-100 h-35px aiz-p-hov-icon text-white fs-13 fw-700 d-none d-sm-flex flex-column justify-content-center align-items-center @if (in_array($product->id, $cart_added)) active @endif"
                    href="javascript:void(0)" onclick="showAddToCartRightCanvas({{ $product->id }})">
                    <span class="cart-btn-text">
                        {{ translate('Select Option') }}
                    </span>
                    <span><i class="las la-sliders-h" style="font-size: 1.4rem;"></i></span>
                </a>
            @else
                <a class="cart-btn absolute-bottom-left w-100 h-35px aiz-p-hov-icon text-white fs-13 fw-700 d-none d-sm-flex flex-column justify-content-center align-items-center @if (in_array($product->id, $cart_added)) active @endif"
                    href="javascript:void(0)" @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCartSingleProduct({{ $product->id }})" @else onclick="showLoginModal()" @endif>
                    <span class="cart-btn-text">
                        {{ translate('Add to Cart') }}
                    </span>
                    <span><i class="las la-2x la-shopping-cart"></i></span>
                </a> 
            @endif
        @endif

        @if (
            $product->auction_product == 1 &&
                $product->auction_start_date <= strtotime('now') &&
                $product->auction_end_date >= strtotime('now'))
            <!-- Place Bid -->
            @php
                $carts = get_user_cart();
                if (count($carts) > 0) {
                    $cart_added = $carts->pluck('product_id')->toArray();
                }
                $highest_bid = $product->bids->max('amount');
                $min_bid_amount = $highest_bid != null ? $highest_bid + 1 : $product->starting_bid;
                $gst_rate = gst_applicable_product_rate($product->id);
            @endphp
            <a class="cart-btn absolute-bottom-left w-100 h-35px aiz-p-hov-icon text-white fs-13 fw-700 d-flex flex-column justify-content-center align-items-center @if (in_array($product->id, $cart_added)) active @endif"
                href="javascript:void(0)" onclick="bid_single_modal({{ $product->id }}, {{ $min_bid_amount }}, {{ $gst_rate }})">
                <span class="cart-btn-text">{{ translate('Place Bid') }}</span>
                <span><i class="las la-2x la-gavel"></i></span>
            </a>
        @endif
    </div>

    <div class="p-2 p-md-3 text-left">
        <h3 class="fw-600 fs-13 text-truncate-2 lh-1-4 mb-2 h-35px">
            <a href="{{ $product_url }}" class="d-block text-reset hov-text-primary"
                title="{{ $product->getTranslation('name') }}">{{ $product->getTranslation('name') }}</a>
        </h3>
        @if (count($productSupplierBadges) > 0)
            <div class="kgt-badge-list mb-2">
                @foreach ($productSupplierBadges as $supplierBadge)
                    <span class="kgt-badge kgt-badge-{{ $supplierBadge['variant'] }}">
                        {{ $supplierBadge['label'] }}
                    </span>
                @endforeach
            </div>
        @endif
        @if ($product->auction_product == 0 && home_base_price($product) != home_discounted_base_price($product))
            <div class="disc-amount has-transition mb-1">
                <del class="fw-400 fs-11 text-secondary">{{ home_base_price($product) }}</del>
            </div>
        @endif
        <div class="fw-700 text-primary fs-15 mb-1">{{ $productCardPrice }}</div>
        <div class="fs-11 text-secondary mb-1 kgt-meta-line">
            {{ translate('MOQ') }}: {{ $productCardMoq }} {{ translate('pcs') }}
        </div>
        @if (!empty($productSupplierSummary['name']))
            <div class="fs-11 text-secondary mb-1 kgt-supplier-meta">
                <span class="fw-700 text-dark">{{ translate('Sold by') }}:</span>
                <span class="kgt-supplier-name">
                    @if (!empty($productSupplierSummary['url']))
                        <a href="{{ $productSupplierSummary['url'] }}" class="text-reset hov-text-primary">
                            {{ $productSupplierSummary['name'] }}
                        </a>
                    @else
                        <span>{{ $productSupplierSummary['name'] }}</span>
                    @endif
                </span>
            </div>
        @endif
        <div class="d-flex align-items-center flex-wrap justify-content-between mb-1">
            <div class="d-flex align-items-center text-secondary fs-11 mr-2">
                @if (!empty($productSupplierSummary['country_flag_iso']))
                    <span class="kgt-country-flag"><span class="iti__flag iti__{{ $productSupplierSummary['country_flag_iso'] }}"></span></span>
                @endif
                <span>{{ $productCardCountry }}</span>
            </div>
            <div class="d-flex align-items-center flex-nowrap">
                <span class="rating rating-sm mr-1">@php renderStarRating($productCardReview['average']); @endphp</span>
                <span class="fs-11 text-secondary">{{ $productCardReview['average'] }} ({{ $productCardReview['count'] }})</span>
            </div>
        </div>
        @if ($product->auction_product == 0 && ((int) $product->wholesale_product === 1 || !empty($productSupplierSummary['name'])))
            <div class="kgt-action-row mt-2">
                @if ((int) $product->wholesale_product === 1)
                    <a href="{{ $productRfqUrl ?: 'javascript:void(0)' }}"
                        class="kgt-rfq-btn {{ $productRfqUrl ? '' : 'disabled' }}"
                        @if (!$productRfqUrl) aria-disabled="true" @endif>
                        {{ translate('Request Quote') }}
                    </a>
                @endif
                <a href="{{ $productContactSupplierUrl ?: 'javascript:void(0)' }}"
                    class="kgt-contact-btn {{ $productContactSupplierUrl ? '' : 'disabled' }}"
                    @if (!$productContactSupplierUrl) aria-disabled="true" @endif>
                    {{ translate('Contact Supplier') }}
                </a>
            </div>
        @endif
    </div>
</div>
