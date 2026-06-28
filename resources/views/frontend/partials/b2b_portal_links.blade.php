@php
    $showUtilityLinks = $showUtilityLinks ?? false;
    $linkClass = $linkClass ?? 'fs-12';
    $itemClass = $itemClass ?? '';
    $itemStyle = $itemStyle ?? 'color: inherit;';
    $becomeSellerUrl = route(get_setting('seller_registration_verify') === '1' ? 'shop-reg.verification' : 'shops.create');
@endphp

@if (get_setting('vendor_system_activation') == 1)
    <a href="{{ $becomeSellerUrl }}" class="{{ $itemClass }}" style="{{ $itemStyle }}">{{ translate('Become a Seller') }}</a>
@endif
<a href="{{ route('b2b.portal.become-supplier') }}" class="{{ $itemClass }}" style="{{ $itemStyle }}">{{ translate('Become a Supplier') }}</a>
<a href="{{ route('buyer.portal') }}" class="{{ $itemClass }}" style="{{ $itemStyle }}">{{ translate('Become a Buyer') }}</a>
@if ($showUtilityLinks)
    <a href="{{ route('b2b.suppliers.index') }}" class="{{ $itemClass }}" style="{{ $itemStyle }}">{{ translate('Suppliers') }}</a>
    <a href="{{ route('buyer.portal') }}" class="{{ $itemClass }}" style="{{ $itemStyle }}">{{ translate('RFQ') }}</a>
@endif
