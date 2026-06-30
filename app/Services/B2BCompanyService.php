<?php

namespace App\Services;

use App\Models\B2BCompany;
use App\Models\B2BCompanyMember;
use App\Services\Fraud\FraudRestrictionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class B2BCompanyService
{
    public function __construct(
        protected B2BPermissionService $b2bPermissionService,
        protected B2BPackageService $b2bPackageService,
        protected FraudRestrictionService $fraudRestrictionService
    )
    {
    }

    protected array $supplierTypes = B2BCompany::SUPPLIER_TYPES;
    protected array $buyerTypes = B2BCompany::BUYER_TYPES;

    public function getOwnedCompanyByUser($userId)
    {
        return B2BCompany::where('user_id', $userId)->first();
    }

    protected function getActiveMembershipCompaniesByUser($userId): Collection
    {
        return B2BCompanyMember::with('company')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'procurement_manager' THEN 2 WHEN 'sales_manager' THEN 3 WHEN 'finance_manager' THEN 4 WHEN 'logistics_manager' THEN 5 WHEN 'viewer' THEN 6 ELSE 7 END")
            ->orderBy('joined_at')
            ->orderBy('id')
            ->get()
            ->pluck('company')
            ->filter()
            ->unique('id')
            ->values();
    }

    public function isRestrictedTeamMember($userId): bool
    {
        return !$this->getOwnedCompanyByUser($userId)
            && $this->getActiveMembershipCompaniesByUser($userId)->isNotEmpty();
    }

    public function getAvailableCompaniesByUser($userId)
    {
        $companies = $this->getActiveMembershipCompaniesByUser($userId);

        if ($this->isRestrictedTeamMember($userId)) {
            $activeCompanyId = $this->getActiveCompanyId();
            if ($activeCompanyId) {
                $activeCompany = $companies->first(fn ($company) => (int) $company->id === (int) $activeCompanyId);
                if ($activeCompany) {
                    return collect([$activeCompany]);
                }
            }

            return $companies->take(1)->values();
        }

        if ($companies->isNotEmpty()) {
            return $companies;
        }

        $ownedCompany = $this->getOwnedCompanyByUser($userId);

        return $ownedCompany ? collect([$ownedCompany]) : collect();
    }

    public function getCompanyByUser($userId)
    {
        $activeCompanyId = $this->getActiveCompanyId();
        if ($activeCompanyId && $this->b2bPermissionService->canAccessCompany($userId, $activeCompanyId)) {
            return B2BCompany::find($activeCompanyId);
        }

        if ($activeCompanyId) {
            $this->clearActiveCompany();
        }

        $company = $this->getAvailableCompaniesByUser($userId)->first();

        if ($company) {
            $this->setActiveCompanyId($company->id);
            return $company;
        }

        $ownedCompany = $this->getOwnedCompanyByUser($userId);
        if ($ownedCompany && $this->b2bPermissionService->canAccessCompany($userId, $ownedCompany->id)) {
            $this->setActiveCompanyId($ownedCompany->id);
            return $ownedCompany;
        }

        return null;
    }

    public function getSwitchableCompaniesByUser($userId): Collection
    {
        return $this->getAvailableCompaniesByUser($userId)->values();
    }

    public function hasMultipleCompanies($userId): bool
    {
        return $this->getSwitchableCompaniesByUser($userId)->count() > 1;
    }

    public function setActiveCompanyForUser($userId, $companyId, bool $bypassRestriction = false): bool
    {
        if (!$this->b2bPermissionService->canAccessCompany($userId, $companyId)) {
            return false;
        }

        if (!$bypassRestriction && $this->isRestrictedTeamMember($userId)) {
            $availableCompany = $this->getAvailableCompaniesByUser($userId)
                ->first(fn ($company) => (int) $company->id === (int) $companyId);

            if (!$availableCompany) {
                return false;
            }
        }

        $this->setActiveCompanyId($companyId);

        return true;
    }

    public function clearActiveCompany(): void
    {
        Session::forget('active_b2b_company_id');
    }

    protected function getActiveCompanyId(): ?int
    {
        $companyId = Session::get('active_b2b_company_id');

        return $companyId ? (int) $companyId : null;
    }

    protected function setActiveCompanyId(int $companyId): void
    {
        Session::put('active_b2b_company_id', $companyId);
    }

    public function isApprovedSupplier($userId, $companyId = null): bool
    {
        $company = $companyId ? B2BCompany::find($companyId) : $this->getCompanyByUser($userId);

        if (
            !$company ||
            $company->verification_status !== 'approved' ||
            !$this->b2bPermissionService->canAccessCompany($userId, $company->id)
        ) {
            return false;
        }

        return in_array($company->company_type, $this->supplierTypes);
    }

    public function isApprovedBuyer($userId, $companyId = null): bool
    {
        $company = $companyId ? B2BCompany::find($companyId) : $this->getCompanyByUser($userId);

        return (bool) (
            $company &&
            $company->verification_status === 'approved' &&
            in_array($company->company_type, $this->buyerTypes, true) &&
            $this->b2bPermissionService->canAccessCompany($userId, $company->id)
        );
    }

    public function canCreateWholesaleProduct($userId, $companyId = null): bool
    {
        $company = $companyId ? B2BCompany::find($companyId) : $this->getCompanyByUser($userId);

        return (bool) (
            $company &&
            $this->isApprovedSupplier($userId, $company->id) &&
            $this->b2bPermissionService->hasRole($userId, $company->id, ['owner', 'admin', 'sales_manager']) &&
            $this->b2bPackageService->canCreateWholesaleProduct($company)
        );
    }

    public function hasActiveBuyerPackage($userId, $companyId = null): bool
    {
        $company = $companyId ? B2BCompany::find($companyId) : $this->getCompanyByUser($userId);

        return (bool) (
            $company &&
            $this->isApprovedBuyer($userId, $company->id) &&
            $this->b2bPackageService->getActivePackageForCompany($company)
        );
    }

    public function hasActiveSupplierPackage($userId, $companyId = null): bool
    {
        $company = $companyId ? B2BCompany::find($companyId) : $this->getCompanyByUser($userId);

        return (bool) (
            $company &&
            $this->isApprovedSupplier($userId, $company->id) &&
            $this->b2bPackageService->getActivePackageForCompany($company)
        );
    }

    public function hasActivePackage($userId, $companyId = null): bool
    {
        $company = $companyId ? B2BCompany::find($companyId) : $this->getCompanyByUser($userId);

        return (bool) (
            $company &&
            (
                $this->hasActiveBuyerPackage($userId, $company->id) ||
                $this->hasActiveSupplierPackage($userId, $company->id)
            )
        );
    }

    public function canCreateRfq($userId, $companyId = null): bool
    {
        $company = $companyId ? B2BCompany::find($companyId) : $this->getCompanyByUser($userId);

        return (bool) (
            $company &&
            $this->isApprovedBuyer($userId, $company->id) &&
            $this->b2bPermissionService->canCreateRfq($userId, $company->id) &&
            $this->b2bPackageService->canCreateRfq($company) &&
            $this->fraudRestrictionService->canCreateRfq($userId)
        );
    }

    public function canReplyToRfq($userId, $companyId = null): bool
    {
        $company = $companyId ? B2BCompany::find($companyId) : $this->getCompanyByUser($userId);

        return (bool) (
            $company &&
            $this->isApprovedSupplier($userId, $company->id) &&
            $this->b2bPermissionService->canSubmitQuotation($userId, $company->id) &&
            $this->b2bPackageService->canReplyToRfq($company) &&
            $this->fraudRestrictionService->canReplyToRfq($userId)
        );
    }

    public function getSupplierTypes(): array
    {
        return $this->supplierTypes;
    }

    public function getPortalByUser($userId): ?string
    {
        $company = $this->getCompanyByUser($userId) ?: $this->getOwnedCompanyByUser($userId);

        if (!$company) {
            return null;
        }

        if ($company->isSupplierSide()) {
            return 'supplier';
        }

        if ($company->isBuyerSide()) {
            return 'buyer';
        }

        return null;
    }

    public function isSupplierPortalUser($userId): bool
    {
        return $this->getPortalByUser($userId) === 'supplier';
    }

    public function isBuyerPortalUser($userId): bool
    {
        return $this->getPortalByUser($userId) === 'buyer';
    }

    public function getPortalHomeUrl($userId): ?string
    {
        $portal = $this->getPortalByUser($userId);

        if (!$portal) {
            return null;
        }

        return $this->getPortalUrl($userId, $portal);
    }

    public function getPortalUrl($userId, string $portal): string
    {
        $company = $this->getCompanyByUser($userId) ?: $this->getOwnedCompanyByUser($userId);

        if (!$company) {
            return route($portal . '.onboarding');
        }

        $isApproved = $portal === 'supplier'
            ? ($company->verification_status === 'approved' && $company->isSupplierSide())
            : ($company->verification_status === 'approved' && $company->isBuyerSide());

        if ($isApproved) {
            return route($portal . '.dashboard');
        }

        return route('b2b.portal.status', ['portal' => $portal]);
    }
}
