<?php

namespace App\Support;

use App\Models\B2BPackage;
use App\Models\B2BPremiumVerificationPackage;
use App\Models\B2BProductPromotionPackage;
use App\Models\SellerPackage;

class B2BPaymentResolver
{
    public static function isAiTradeDeskAccess(array $paymentData): bool
    {
        return !empty($paymentData['b2b_ai_trade_desk_access']);
    }

    public static function resolveSellerPackageAmount(array $paymentData): float
    {
        if (self::isAiTradeDeskAccess($paymentData)) {
            return (float) ($paymentData['b2b_ai_access_price'] ?? 0);
        }

        if (!empty($paymentData['b2b_package_id'])) {
            return (float) B2BPackage::findOrFail($paymentData['b2b_package_id'])->amount;
        }

        if (!empty($paymentData['b2b_product_promotion_package_id'])) {
            return (float) B2BProductPromotionPackage::findOrFail($paymentData['b2b_product_promotion_package_id'])->amount;
        }

        if (!empty($paymentData['b2b_premium_verification_package_id'])) {
            return (float) B2BPremiumVerificationPackage::findOrFail($paymentData['b2b_premium_verification_package_id'])->amount;
        }

        return (float) SellerPackage::findOrFail($paymentData['seller_package_id'])->amount;
    }

    public static function sellerPackagePayload(array $paymentData, ?int $defaultUserId = null): array
    {
        return [
            'seller_package_id' => $paymentData['seller_package_id'] ?? 0,
            'b2b_package_id' => $paymentData['b2b_package_id'] ?? null,
            'b2b_product_promotion_package_id' => $paymentData['b2b_product_promotion_package_id'] ?? null,
            'b2b_premium_verification_package_id' => $paymentData['b2b_premium_verification_package_id'] ?? null,
            'b2b_ai_trade_desk_access' => $paymentData['b2b_ai_trade_desk_access'] ?? null,
            'b2b_ai_access_price' => $paymentData['b2b_ai_access_price'] ?? null,
            'b2b_company_id' => $paymentData['b2b_company_id'] ?? null,
            'b2b_user_id' => $paymentData['b2b_user_id'] ?? $defaultUserId,
            'payment_method' => $paymentData['payment_method'] ?? null,
        ];
    }
}
