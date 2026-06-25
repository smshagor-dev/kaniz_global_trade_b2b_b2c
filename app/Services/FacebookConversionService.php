<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Http;

class FacebookConversionService
{
    private function getBaseConfig()
    {
        $pixelId = env('FACEBOOK_PIXEL_ID');
        $accessToken = env('FACEBOOK_PIXEL_API');

        return [
            'url' => "https://graph.facebook.com/v18.0/{$pixelId}/events",
            'access_token' => $accessToken
        ];
    }

    private function buildUserData()
    {
        $user = auth()->user();

        return [
            "em" => $user && $user->email ? hash('sha256', $user->email) : null,
            "ph" => $user && $user->phone ? hash('sha256', $user->phone) : null,
            "client_ip_address" => request()->ip(),
            "client_user_agent" => request()->userAgent(),
            "fbp" => request()->cookie('_fbp'),
            "fbc" => request()->cookie('_fbc'),
        ];
    }

    private function sendToFacebook($eventName, $customData = [], $eventId = null)
    {
        $config = $this->getBaseConfig();

        $payload = [
            "data" => [
                [
                    "event_name" => $eventName,
                    "event_time" => time(),
                    "event_id"   => $eventId,
                    "action_source" => "website",
                    "user_data" => $this->buildUserData(),
                    "custom_data" => $customData
                ]
            ]
        ];

        Http::post($config['url'], $payload + [
            'access_token' => $config['access_token']
        ]);
    }

    // ------------------------------------------
    // Existing Public Methods (unchanged name)
    // ------------------------------------------

    public function sendPurchase($combinedOrder)
    {
        $contentIds = [];

        foreach ($combinedOrder->orders as $order) {
            foreach ($order->orderDetails as $detail) {
                $contentIds[] = (string) $detail->product_id;
            }
        }

        $eventId = 'purchase_' . $combinedOrder->id;

        $this->sendToFacebook("Purchase", [
            "currency" => Currency::findOrFail(get_setting('system_default_currency'))->code,
            "value" => $combinedOrder->grand_total,
            "content_ids" => $contentIds,
            "content_type" => "product"
        ], $eventId);
    }

    public function sendAddToCart($product, $price, $eventId = null)
    {
        $this->sendToFacebook("AddToCart", [
            "currency" => Currency::findOrFail(get_setting('system_default_currency'))->code,
            "value" => $price,
            "content_ids" => [(string) $product->id],
            "content_type" => "product"
        ], $eventId);
    }

    public function sendAddToWishlist($productId, $eventId = null)
    {
        $this->sendToFacebook("AddToWishlist", [
            "content_ids" => [(string) $productId],
            "content_type" => "product"
        ], $eventId);
    }

    public function sendViewContent($product, $eventId = null)
    {
        if (!$eventId) {
            $eventId = 'view_' . $product->id . '_' . time();
        }

        $this->sendToFacebook("ViewContent", [
            "content_ids" => [(string) $product->id],
            "content_name" => $product->getTranslation('name'),
            "content_type" => "product",
            "value" => home_discounted_price($product),
            "currency" => Currency::findOrFail(get_setting('system_default_currency'))->code,
        ], $eventId);
    }
}