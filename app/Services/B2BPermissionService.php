<?php

namespace App\Services;

use App\Models\B2BCompany;
use App\Models\B2BCompanyMember;
use App\Models\B2BCompanyRole;

class B2BPermissionService
{
    public const CUSTOM_ROLE_PREFIX = 'custom:';

    public const BUILT_IN_ROLE_KEYS = [
        'owner',
        'admin',
        'procurement_manager',
        'sales_manager',
        'finance_manager',
        'logistics_manager',
        'viewer',
    ];

    public const ROLE_LABELS = [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'procurement_manager' => 'Procurement Manager',
        'sales_manager' => 'Sales Manager',
        'finance_manager' => 'Finance Manager',
        'logistics_manager' => 'Logistics Manager',
        'viewer' => 'Viewer',
    ];

    public const PERMISSION_LABELS = [
        'manage_company' => 'Manage company settings',
        'invite_members' => 'Invite and manage team members',
        'create_rfq' => 'Create RFQs',
        'submit_quotation' => 'Submit quotations',
        'manage_supplier_profile' => 'Manage supplier profile',
        'manage_purchase_order' => 'Manage purchase orders',
        'manage_invoice' => 'Manage proforma invoices',
        'approve_payment' => 'Approve payments',
        'approve_settlement' => 'Approve settlements',
        'approve_refund' => 'Approve refunds',
        'approve_bank_transfer' => 'Approve bank transfers',
        'release_escrow' => 'Release escrow',
        'approve_milestones' => 'Approve milestones',
        'manage_trade_finance' => 'Manage trade finance',
        'manage_insurance' => 'Manage insurance',
        'submit_insurance_claim' => 'Submit insurance claims',
        'manage_freight' => 'Manage freight',
        'approve_freight_costs' => 'Approve freight costs',
        'participate_in_negotiation' => 'Participate in negotiations',
        'view_only' => 'View only',
    ];

    public function getPermissionOptions(): array
    {
        return self::PERMISSION_LABELS;
    }

    public function getBuiltInRoleDefinitions(): array
    {
        return [
            'owner' => [
                'label' => self::ROLE_LABELS['owner'],
                'permissions' => array_diff(array_keys(self::PERMISSION_LABELS), ['view_only']),
            ],
            'admin' => [
                'label' => self::ROLE_LABELS['admin'],
                'permissions' => array_diff(array_keys(self::PERMISSION_LABELS), ['view_only']),
            ],
            'procurement_manager' => [
                'label' => self::ROLE_LABELS['procurement_manager'],
                'permissions' => [
                    'create_rfq',
                    'manage_purchase_order',
                    'manage_trade_finance',
                    'manage_insurance',
                    'submit_insurance_claim',
                    'participate_in_negotiation',
                ],
            ],
            'sales_manager' => [
                'label' => self::ROLE_LABELS['sales_manager'],
                'permissions' => [
                    'submit_quotation',
                    'manage_supplier_profile',
                    'manage_purchase_order',
                    'manage_trade_finance',
                    'manage_insurance',
                    'manage_freight',
                    'participate_in_negotiation',
                ],
            ],
            'finance_manager' => [
                'label' => self::ROLE_LABELS['finance_manager'],
                'permissions' => [
                    'manage_invoice',
                    'approve_payment',
                    'approve_settlement',
                    'approve_refund',
                    'approve_bank_transfer',
                    'release_escrow',
                    'approve_milestones',
                    'manage_trade_finance',
                    'manage_insurance',
                    'submit_insurance_claim',
                    'approve_freight_costs',
                    'participate_in_negotiation',
                ],
            ],
            'logistics_manager' => [
                'label' => self::ROLE_LABELS['logistics_manager'],
                'permissions' => [
                    'manage_freight',
                    'approve_freight_costs',
                    'manage_insurance',
                    'submit_insurance_claim',
                    'participate_in_negotiation',
                ],
            ],
            'viewer' => [
                'label' => self::ROLE_LABELS['viewer'],
                'permissions' => ['view_only'],
            ],
        ];
    }

    public function getRolePermissionMatrix(?int $companyId = null): array
    {
        $matrix = [];

        if ($companyId) {
            $customRoles = B2BCompanyRole::query()
                ->where('b2b_company_id', $companyId)
                ->orderBy('name')
                ->get();

            foreach ($customRoles as $customRole) {
                $normalizedPermissions = $this->normalizePermissionPayload($customRole->permissions ?? []);
                $matrix[self::CUSTOM_ROLE_PREFIX . $customRole->id] = [
                    'key' => self::CUSTOM_ROLE_PREFIX . $customRole->id,
                    'type' => 'custom',
                    'label' => $customRole->name,
                    'permissions' => collect(array_keys(array_filter($normalizedPermissions)))
                        ->map(fn ($permission) => self::PERMISSION_LABELS[$permission] ?? $permission)
                        ->values()
                        ->all(),
                    'custom_role_id' => $customRole->id,
                ];
            }
        }

        return $matrix;
    }

    public function getAssignableRoles(int $companyId): array
    {
        $roles = collect();

        $customRoles = B2BCompanyRole::query()
            ->where('b2b_company_id', $companyId)
            ->orderBy('name')
            ->get()
            ->map(fn (B2BCompanyRole $role) => [
                'value' => self::CUSTOM_ROLE_PREFIX . $role->id,
                'label' => $role->name,
                'type' => 'custom',
                'custom_role_id' => $role->id,
            ]);

        return $roles->concat($customRoles)->values()->all();
    }

    public function resolveRoleSelection(int $companyId, string $selectedRole): array
    {
        if (str_starts_with($selectedRole, self::CUSTOM_ROLE_PREFIX)) {
            $customRoleId = (int) str_replace(self::CUSTOM_ROLE_PREFIX, '', $selectedRole);
            $customRole = B2BCompanyRole::query()
                ->where('b2b_company_id', $companyId)
                ->findOrFail($customRoleId);

            return [
                'role' => $customRole->slug,
                'custom_role_id' => $customRole->id,
                'label' => $customRole->name,
            ];
        }

        abort(422);
    }

    public function getMember($userId, $companyId)
    {
        $member = B2BCompanyMember::with('customRole')
            ->where('user_id', $userId)
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
                'custom_role_id' => null,
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

        return (bool) ($member && $member->status === 'active' && in_array($member->role, $roles, true));
    }

    public function hasPermission($userId, $companyId, string $permissionKey): bool
    {
        $member = $this->getMember($userId, $companyId);

        if (!$member || $member->status !== 'active') {
            return false;
        }

        $permissions = $this->getEffectivePermissionsForMember($member);

        return (bool) ($permissions[$permissionKey] ?? false);
    }

    public function canManageCompany($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'manage_company');
    }

    public function canInviteMembers($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'invite_members');
    }

    public function canCreateRfq($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'create_rfq');
    }

    public function canSubmitQuotation($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'submit_quotation');
    }

    public function canManageSupplierProfile($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'manage_supplier_profile');
    }

    public function canManagePurchaseOrder($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'manage_purchase_order');
    }

    public function canManageInvoice($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'manage_invoice');
    }

    public function canApprovePayment($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'approve_payment');
    }

    public function canApproveSettlement($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'approve_settlement');
    }

    public function canApproveRefund($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'approve_refund');
    }

    public function canApproveBankTransfer($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'approve_bank_transfer');
    }

    public function canReleaseEscrow($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'release_escrow');
    }

    public function canApproveMilestones($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'approve_milestones');
    }

    public function canManageTradeFinance($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'manage_trade_finance');
    }

    public function canManageInsurance($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'manage_insurance');
    }

    public function canSubmitInsuranceClaim($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'submit_insurance_claim');
    }

    public function canManageFreight($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'manage_freight');
    }

    public function canApproveFreightCosts($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'approve_freight_costs');
    }

    public function canViewOnly($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'view_only');
    }

    public function canAccessCompany($userId, $companyId): bool
    {
        $member = $this->getMember($userId, $companyId);

        return (bool) ($member && $member->status === 'active');
    }

    public function canParticipateInNegotiation($userId, $companyId): bool
    {
        return $this->hasPermission($userId, $companyId, 'participate_in_negotiation');
    }

    public function getPortalAbilityMatrix($userId, $companyId): array
    {
        return [
            'can_access_company' => $this->canAccessCompany($userId, $companyId),
            'can_manage_company' => $this->canManageCompany($userId, $companyId),
            'can_invite_members' => $this->canInviteMembers($userId, $companyId),
            'can_manage_supplier_profile' => $this->canManageSupplierProfile($userId, $companyId),
            'can_create_rfq' => $this->canCreateRfq($userId, $companyId),
            'can_submit_quotation' => $this->canSubmitQuotation($userId, $companyId),
            'can_manage_purchase_order' => $this->canManagePurchaseOrder($userId, $companyId),
            'can_manage_invoice' => $this->canManageInvoice($userId, $companyId),
            'can_manage_freight' => $this->canManageFreight($userId, $companyId),
            'can_approve_freight_costs' => $this->canApproveFreightCosts($userId, $companyId),
            'can_manage_insurance' => $this->canManageInsurance($userId, $companyId),
            'can_manage_trade_finance' => $this->canManageTradeFinance($userId, $companyId),
            'can_participate_in_negotiation' => $this->canParticipateInNegotiation($userId, $companyId),
        ];
    }

    protected function getEffectivePermissionsForMember(B2BCompanyMember $member): array
    {
        $permissions = array_fill_keys(array_keys(self::PERMISSION_LABELS), false);

        if ($member->role === 'owner') {
            return $this->normalizePermissionPayload($this->getBuiltInRoleDefinitions()['owner']['permissions']);
        }

        if ($member->customRole) {
            return $this->normalizePermissionPayload($member->customRole->permissions ?? []);
        }

        $rolePermissions = data_get($this->getBuiltInRoleDefinitions(), $member->role . '.permissions', []);

        foreach ($rolePermissions as $permission) {
            $permissions[$permission] = true;
        }

        return $permissions;
    }

    protected function normalizePermissionPayload(array $permissions): array
    {
        $normalized = array_fill_keys(array_keys(self::PERMISSION_LABELS), false);

        foreach ($permissions as $permission => $allowed) {
            if (is_int($permission)) {
                $permission = $allowed;
                $allowed = true;
            }

            if (array_key_exists($permission, $normalized)) {
                $normalized[$permission] = (bool) $allowed;
            }
        }

        return $normalized;
    }
}
