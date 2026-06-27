<?php

namespace App\Services;

use App\Models\B2BCompany;
use App\Models\B2BPackage;
use App\Models\B2BPackageRequest;

class B2BPackageService
{
    public function getPackageRoleForCompany(B2BCompany $company): string
    {
        return $company->isSupplierSide() ? 'supplier' : 'buyer';
    }

    public function featuredHomepagePlan(): ?B2BPackage
    {
        return B2BPackage::featuredSupplierHomepage()
            ->orderBy('sort_order')
            ->orderBy('amount')
            ->first();
    }

    public function getActivePackageForCompany(B2BCompany $company): ?B2BPackage
    {
        if (!$company->b2bPackage) {
            return null;
        }

        if ($company->package_expires_at && $company->package_expires_at->isPast()) {
            return null;
        }

        if ($company->b2bPackage->package_for !== $this->getPackageRoleForCompany($company)) {
            return null;
        }

        if ($company->b2bPackage->isSupplierFeaturedPackage()) {
            return null;
        }

        return $company->b2bPackage;
    }

    public function getActiveFeaturedPackageForCompany(B2BCompany $company): ?B2BPackage
    {
        if (!$company->featuredB2bPackage) {
            return null;
        }

        if ($company->featured_package_expires_at && $company->featured_package_expires_at->isPast()) {
            return null;
        }

        if (!$company->isSupplierSide()) {
            return null;
        }

        if (!$company->featuredB2bPackage->isSupplierFeaturedPackage()) {
            return null;
        }

        return $company->featuredB2bPackage;
    }

    public function canCreateRfq(B2BCompany $company): bool
    {
        $package = $this->getActivePackageForCompany($company);

        return $package && ($package->rfq_limit === 0 || $company->rfqs()->count() < $package->rfq_limit);
    }

    public function canReplyToRfq(B2BCompany $company): bool
    {
        $package = $this->getActivePackageForCompany($company);

        return $package && ($package->quotation_limit === 0 || $company->supplierQuotations()->count() < $package->quotation_limit);
    }

    public function canCreateWholesaleProduct(B2BCompany $company): bool
    {
        $package = $this->getActivePackageForCompany($company);

        return $package && ($package->product_limit === 0 || $company->wholesaleProducts()->count() < $package->product_limit);
    }

    public function canInviteMoreMembers(B2BCompany $company): bool
    {
        $package = $this->getActivePackageForCompany($company);

        return $package && $company->members()->whereIn('status', ['active', 'invited', 'suspended'])->count() < $package->member_limit;
    }

    public function hasAiAccess(B2BCompany $company): bool
    {
        $package = $this->getActivePackageForCompany($company);

        return (bool) ($package?->ai_access);
    }

    public function hasAiToolAccess(B2BCompany $company, string $field): bool
    {
        $package = $this->getActivePackageForCompany($company);

        if (!$package || !$package->ai_access) {
            return false;
        }

        return (bool) data_get($package, $field, false);
    }

    public function aiRevenueProjection(int $companyCount, ?string $packageFor = null): array
    {
        $plan = B2BPackage::query()
            ->active()
            ->membership()
            ->when($packageFor, fn ($query) => $query->where('package_for', $packageFor))
            ->where('ai_access', true)
            ->orderBy('amount')
            ->orderBy('sort_order')
            ->first();

        $monthlyPrice = $plan?->monthlyEquivalent() ?? 49.0;

        return [
            'plan_name' => $plan?->name ?? 'AI Tools Subscription',
            'monthly_price' => $monthlyPrice,
            'company_count' => $companyCount,
            'projected_monthly_revenue' => round($monthlyPrice * $companyCount, 2),
        ];
    }

    public function activatePackage(B2BCompany $company, B2BPackage $package): void
    {
        $startsAt = now();
        $expiresAt = $package->duration > 0 ? $startsAt->copy()->addDays($package->duration) : null;

        if ($package->isSupplierFeaturedPackage()) {
            $company->update([
                'featured_b2b_package_id' => $package->id,
                'featured_package_started_at' => $startsAt,
                'featured_package_expires_at' => $expiresAt,
                'featured_supplier' => $company->isSupplierSide() ? ($company->featured_supplier || $package->featured_profile) : $company->featured_supplier,
                'verified_supplier_badge' => $company->isSupplierSide() ? ($company->verified_supplier_badge || $package->verified_badge) : $company->verified_supplier_badge,
            ]);

            return;
        }

        $company->update([
            'b2b_package_id' => $package->id,
            'package_started_at' => $startsAt,
            'package_expires_at' => $expiresAt,
        ]);
    }

    public function createRequest(B2BCompany $company, B2BPackage $package, int $userId, array $payload = []): B2BPackageRequest
    {
        return B2BPackageRequest::create([
            'b2b_company_id' => $company->id,
            'b2b_package_id' => $package->id,
            'request_type' => $package->isSupplierFeaturedPackage() ? 'supplier_featured' : 'membership',
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

    public function featuredSupplierMonthlyRevenue(): float
    {
        return round(
            B2BCompany::query()
                ->homepageFeaturedSuppliers()
                ->with('featuredB2bPackage')
                ->get()
                ->sum(fn (B2BCompany $company) => $company->featuredB2bPackage?->monthlyEquivalent() ?? 0),
            2
        );
    }

    public function featuredSupplierRevenueProjection(int $companyCount): array
    {
        $plan = $this->featuredHomepagePlan();
        $monthlyPrice = $plan?->monthlyEquivalent() ?? 0;

        return [
            'plan_name' => $plan?->name,
            'monthly_price' => $monthlyPrice,
            'company_count' => $companyCount,
            'projected_monthly_revenue' => round($monthlyPrice * $companyCount, 2),
        ];
    }
}
