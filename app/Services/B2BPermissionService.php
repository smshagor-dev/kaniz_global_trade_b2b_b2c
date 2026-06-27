<?php

namespace App\Services;

use App\Models\B2BCompany;
use App\Models\B2BCompanyMember;

class B2BPermissionService
{
    public function getMember($userId, $companyId)
    {
        $member = B2BCompanyMember::where('user_id', $userId)
            ->where('b2b_company_id', $companyId)
            ->first();

        if ($member) {
            return $member;
        }

        $company = B2BCompany::find($companyId);
        if ($company && (int) $company->user_id === (int) $userId) {
            return new B2BCompanyMember([
                'b2b_company_id' => $companyId,
                'user_id' => $userId,
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => $company->created_at,
            ]);
        }

        return null;
    }

    public function isCompanyOwner($userId, $companyId): bool
    {
        $member = $this->getMember($userId, $companyId);

        return (bool) ($member && $member->role === 'owner' && $member->status === 'active');
    }

    public function hasRole($userId, $companyId, array $roles): bool
    {
        $member = $this->getMember($userId, $companyId);

        return (bool) ($member && $member->status === 'active' && in_array($member->role, $roles));
    }

    public function canManageCompany($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin']);
    }

    public function canInviteMembers($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin']);
    }

    public function canCreateRfq($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'procurement_manager']);
    }

    public function canSubmitQuotation($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'sales_manager']);
    }

    public function canManageSupplierProfile($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'sales_manager']);
    }

    public function canManagePurchaseOrder($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'procurement_manager', 'sales_manager']);
    }

    public function canManageInvoice($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager']);
    }

    public function canApprovePayment($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager']);
    }

    public function canApproveSettlement($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager']);
    }

    public function canApproveRefund($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager']);
    }

    public function canApproveBankTransfer($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager']);
    }

    public function canReleaseEscrow($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager']);
    }

    public function canApproveMilestones($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager']);
    }

    public function canManageTradeFinance($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager', 'procurement_manager', 'sales_manager']);
    }

    public function canManageInsurance($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager', 'procurement_manager', 'sales_manager', 'logistics_manager']);
    }

    public function canSubmitInsuranceClaim($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager', 'procurement_manager', 'logistics_manager']);
    }

    public function canManageFreight($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'logistics_manager', 'sales_manager']);
    }

    public function canApproveFreightCosts($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['owner', 'admin', 'finance_manager', 'logistics_manager']);
    }

    public function canViewOnly($userId, $companyId): bool
    {
        return $this->hasRole($userId, $companyId, ['viewer']);
    }

    public function canAccessCompany($userId, $companyId): bool
    {
        $member = $this->getMember($userId, $companyId);

        return (bool) ($member && $member->status === 'active');
    }

    public function canParticipateInNegotiation($userId, $companyId): bool
    {
        return $this->canAccessCompany($userId, $companyId) && !$this->canViewOnly($userId, $companyId);
    }
}
