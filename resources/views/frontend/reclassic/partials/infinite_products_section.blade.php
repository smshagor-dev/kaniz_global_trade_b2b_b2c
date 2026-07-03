@foreach ($products as $product)
    <div class="home-infinite-product-item">
        @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1', ['product' => $product])
    </div>
@endforeach
