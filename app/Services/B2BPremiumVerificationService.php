<?php

namespace App\Services;

use App\Models\B2BCompany;
use App\Models\B2BPremiumVerificationPackage;
use App\Models\B2BPremiumVerificationRequest;

class B2BPremiumVerificationService
{
    public function getActivePackageForCompany(B2BCompany $company): ?B2BPremiumVerificationPackage
    {
        if (!$company->premiumVerificationPackage || !$company->premium_verified) {
            return null;
        }

        return $company->premiumVerificationPackage->is_active ? $company->premiumVerificationPackage : null;
    }

    public function activatePackage(B2BCompany $company, B2BPremiumVerificationPackage $package): void
    {
        $company->update([
            'premium_verification_package_id' => $package->id,
            'premium_verified' => true,
            'premium_verified_at' => now(),
            'verified_supplier_badge' => $company->isSupplierSide() ? true : $company->verified_supplier_badge,
        ]);
    }

    public function createRequest(B2BCompany $company, B2BPremiumVerificationPackage $package, int $userId, array $payload = []): B2BPremiumVerificationRequest
    {
        return B2BPremiumVerificationRequest::create([
            'b2b_company_id' => $company->id,
            'b2b_premium_verification_package_id' => $package->id,
            'requested_by' => $userId,
            'amount' => $package->amount,
            'status' => 'pending',
            'note' => $payload['note'] ?? null,
            'payment_reference' => $payload['payment_reference'] ?? null,
            'payment_notes' => $payload['payment_notes'] ?? null,
            'payment_submitted_at' => !empty($payload['payment_reference']) ? now() : null,
            'requested_at' => now(),
        ]);
    }

    public function revenueProjection(int $companyCount): array
    {
        $plan = B2BPremiumVerificationPackage::active()
            ->orderBy('sort_order')
            ->orderBy('amount')
            ->first();

        $price = (float) ($plan?->amount ?? 0);

        return [
            'plan_name' => $plan?->name,
            'price' => $price,
            'company_count' => $companyCount,
            'projected_revenue' => round($price * $companyCount, 2),
        ];
    }
}
