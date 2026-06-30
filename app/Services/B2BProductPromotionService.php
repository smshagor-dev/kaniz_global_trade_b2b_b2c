<?php

namespace App\Services;

use App\Models\B2BCompany;
use App\Models\B2BProductPromotion;
use App\Models\B2BProductPromotionPackage;
use App\Models\B2BProductPromotionRequest;
use App\Models\Product;
use RuntimeException;
use Illuminate\Support\Str;

class B2BProductPromotionService
{
    public function sponsoredPlan(): ?B2BProductPromotionPackage
    {
        return B2BProductPromotionPackage::active()
            ->orderBy('sort_order')
            ->orderBy('amount')
            ->first();
    }

    public function getActivePackageForCompany(B2BCompany $company): ?B2BProductPromotionPackage
    {
        if (!$company->productPromotionPackage) {
            return null;
        }

        if ($company->product_promotion_expires_at && $company->product_promotion_expires_at->isPast()) {
            return null;
        }

        return $company->productPromotionPackage->is_active ? $company->productPromotionPackage : null;
    }

    public function activePromotionsQuery(B2BCompany $company)
    {
        return $company->productPromotions()->active();
    }

    public function getActivePromotedProductsCount(B2BCompany $company): int
    {
        return $this->activePromotionsQuery($company)->count();
    }

    public function getRemainingPromotionSlots(B2BCompany $company): ?int
    {
        $package = $this->getActivePackageForCompany($company);

        if (!$package) {
            return 0;
        }

        if ((int) $package->product_limit === 0) {
            return null;
        }

        return max(0, $package->product_limit - $this->getActivePromotedProductsCount($company));
    }

    public function getPromotedProductIds(B2BCompany $company): array
    {
        return $this->activePromotionsQuery($company)->pluck('product_id')->all();
    }

    public function activeSponsoredProductsCount(): int
    {
        return B2BProductPromotion::active()->count();
    }

    public function sponsoredProductMonthlyRevenue(): float
    {
        return round(
            B2BProductPromotion::query()
                ->active()
                ->with('package')
                ->get()
                ->sum(function (B2BProductPromotion $promotion) {
                    $package = $promotion->package;

                    if (!$package) {
                        return 0;
                    }

                    $slotCount = max((int) $package->product_limit, 1);

                    return $package->monthlyEquivalent() / $slotCount;
                }),
            2
        );
    }

    public function revenueProjection(int $productCount): array
    {
        $plan = $this->sponsoredPlan();
        $slotCount = max((int) ($plan?->product_limit ?? 1), 1);
        $monthlyUnitPrice = $plan ? round($plan->monthlyEquivalent() / $slotCount, 2) : 0;

        return [
            'plan_name' => $plan?->name,
            'monthly_unit_price' => $monthlyUnitPrice,
            'product_count' => $productCount,
            'projected_monthly_revenue' => round($monthlyUnitPrice * $productCount, 2),
        ];
    }

    public function activatePackage(B2BCompany $company, B2BProductPromotionPackage $package): void
    {
        $startsAt = now();
        $expiresAt = $package->duration > 0 ? $startsAt->copy()->addDays($package->duration) : null;

        $company->update([
            'product_promotion_package_id' => $package->id,
            'product_promotion_started_at' => $startsAt,
            'product_promotion_expires_at' => $expiresAt,
        ]);

        $company->productPromotions()
            ->where('status', 'active')
            ->update([
                'b2b_product_promotion_package_id' => $package->id,
                'expires_at' => $expiresAt,
            ]);
    }

    public function createRequest(B2BCompany $company, B2BProductPromotionPackage $package, int $userId, array $payload = []): B2BProductPromotionRequest
    {
        return B2BProductPromotionRequest::create([
            'b2b_company_id' => $company->id,
            'b2b_product_promotion_package_id' => $package->id,
            'requested_by' => $userId,
            'amount' => $package->amount,
            'billing_cycle' => $package->duration >= 30 ? 'monthly' : 'custom',
            'status' => 'pending',
            'note' => $payload['note'] ?? null,
            'payment_reference' => $payload['payment_reference'] ?? null,
            'payment_notes' => $payload['payment_notes'] ?? null,
            'payment_submitted_at' => !empty($payload['payment_reference']) ? now() : null,
            'requested_at' => now(),
        ]);
    }

    public function recordAutomatedPurchase(
        B2BCompany $company,
        B2BProductPromotionPackage $package,
        int $userId,
        string $paymentMethod,
        ?string $paymentDetails = null
    ): B2BProductPromotionRequest {
        $paymentReference = strtoupper(Str::slug($paymentMethod ?: 'online_payment', '_'))
            . '-' . substr(sha1((string) $paymentDetails), 0, 16);

        return B2BProductPromotionRequest::updateOrCreate(
            [
                'b2b_company_id' => $company->id,
                'b2b_product_promotion_package_id' => $package->id,
                'payment_reference' => $paymentReference,
            ],
            [
                'requested_by' => $userId,
                'amount' => $package->amount,
                'billing_cycle' => $package->duration >= 30 ? 'monthly' : 'custom',
                'status' => 'approved',
                'note' => 'Automatic online payment completed.',
                'payment_notes' => $paymentDetails,
                'payment_submitted_at' => now(),
                'requested_at' => now(),
                'approved_at' => now(),
                'approved_by' => null,
                'rejection_note' => null,
            ]
        );
    }

    public function promoteProduct(B2BCompany $company, Product $product): B2BProductPromotion
    {
        $package = $this->getActivePackageForCompany($company);

        if (!$package) {
            throw new RuntimeException(translate('An active sponsored product package is required.'));
        }

        if ($product->user_id !== $company->user_id || !$product->wholesale_product) {
            throw new RuntimeException(translate('This product is not eligible for sponsored promotion.'));
        }

        if (!$product->approved || !$product->published) {
            throw new RuntimeException(translate('Only approved and published wholesale products can be sponsored.'));
        }

        $activePromotion = $company->productPromotions()
            ->active()
            ->where('product_id', $product->id)
            ->first();

        if ($activePromotion) {
            return $activePromotion;
        }

        $remaining = $this->getRemainingPromotionSlots($company);
        if (!is_null($remaining) && $remaining <= 0) {
            throw new RuntimeException(translate('Your sponsored product package limit has been reached.'));
        }

        return B2BProductPromotion::create([
            'b2b_company_id' => $company->id,
            'product_id' => $product->id,
            'b2b_product_promotion_package_id' => $package->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => $company->product_promotion_expires_at,
        ]);
    }

    public function unpromoteProduct(B2BCompany $company, Product $product): void
    {
        $company->productPromotions()
            ->active()
            ->where('product_id', $product->id)
            ->update([
                'status' => 'inactive',
                'ended_at' => now(),
            ]);
    }
}
