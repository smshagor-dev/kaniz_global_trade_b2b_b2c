@if (!empty($company) && $company->verified_supplier_badge)
    <span class="badge badge-inline badge-success">{{ translate('Verified Supplier') }}</span>
@endif
@if (!empty($company) && $company->premium_verified)
    <span class="badge badge-inline badge-soft-success">{{ translate('Premium Verified') }}</span>
@endif
