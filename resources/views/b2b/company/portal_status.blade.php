@extends('b2b.layouts.' . $portal)

@section('panel_content')
    @php
        $isSupplier = $portal === 'supplier';
        $heading = $isSupplier ? translate('Supplier Portal Status') : translate('Buyer Portal Status');
        $requiresPackage = $company && $supportsPortal && $company->verification_status === 'approved' && !($hasActivePackage ?? false);
        $primaryAction = $requiresPackage
            ? route('b2b.packages.index')
            : ($isSupplier ? route('supplier.onboarding') : route('buyer.onboarding'));
        $primaryLabel = $requiresPackage
            ? translate('Choose Package')
            : ($company && $supportsPortal ? translate('Continue Onboarding') : translate('Start Onboarding'));
    @endphp

    <div class="b2b-section-card">
        <span class="b2b-pill">{{ $heading }}</span>
        <h1 class="h3 mt-3 mb-3">{{ $heading }}</h1>

        @if (!$company)
            <p class="text-muted mb-4">{{ translate('No B2B company is linked to your account yet. Start onboarding to unlock the portal experience.') }}</p>
        @elseif (!$supportsPortal)
            <p class="text-muted mb-4">
                {{ translate('Your current active company type does not match this portal.') }}
                {{ translate('You can switch to another company from the sidebar if you already have access, or continue with a portal-specific onboarding request.') }}
            </p>
        @elseif ($company->verification_status === 'pending')
            <p class="text-muted mb-4">{{ translate('Your company profile is under verification. You can review and update the onboarding details while the approval is pending.') }}</p>
        @elseif ($company->verification_status === 'rejected')
            <p class="text-muted mb-4">{{ translate('Your company verification needs updates before portal access can be activated. Review the profile, correct the documents, and resubmit.') }}</p>
            @if ($company->verification_note)
                <div class="alert alert-warning">{{ $company->verification_note }}</div>
            @endif
        @elseif ($requiresPackage)
            <p class="text-muted mb-4">{{ translate('Your company is approved, but an active package is required before this portal can be used. Please purchase or activate the appropriate package first.') }}</p>
        @else
            <p class="text-muted mb-4">{{ translate('Portal access is not available yet for the current company. Continue with onboarding to align the role and verification requirements.') }}</p>
        @endif

        <div class="d-flex flex-wrap">
            <a href="{{ $primaryAction }}" class="btn btn-primary mr-2 mb-2">{{ $primaryLabel }}</a>
            <a href="{{ route('b2b.company.show') }}" class="btn btn-soft-secondary mb-2">{{ translate('Open Company Profile') }}</a>
        </div>
    </div>
@endsection
