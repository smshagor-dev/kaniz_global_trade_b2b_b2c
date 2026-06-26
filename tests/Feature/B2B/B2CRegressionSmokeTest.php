<?php

namespace Tests\Feature\B2B;

class B2CRegressionSmokeTest extends B2BFeatureTestCase
{
    public function test_home_cart_and_checkout_routes_do_not_500(): void
    {
        $responses = [
            $this->get(route('home')),
            $this->get(route('cart')),
            $this->get(route('checkout')),
        ];

        foreach ($responses as $response) {
            $this->assertNotSame(500, $response->getStatusCode());
        }
    }

    public function test_basic_product_detail_route_still_loads(): void
    {
        $seller = $this->createSellerUser();
        $category = $this->createCategory();
        $product = $this->createProduct($seller, $category, [
            'name' => 'Regression Product',
            'slug' => 'regression-product-' . random_int(1000, 9999),
            'wholesale_product' => 0,
        ]);

        $response = $this->get(route('product', $product->slug));

        $this->assertNotSame(500, $response->getStatusCode());
    }
}
