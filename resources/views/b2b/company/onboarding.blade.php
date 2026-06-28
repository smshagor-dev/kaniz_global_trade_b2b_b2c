@extends('b2b.layouts.' . $portal)

@section('panel_content')
    @php
        $isSupplier = $portal === 'supplier';
        $actionRoute = $mode === 'edit' ? route('b2b.company.update') : route('b2b.company.store');
        $title = $isSupplier ? translate('Supplier Onboarding') : translate('Buyer Onboarding');
        $subtitle = $isSupplier
            ? translate('Launch a dedicated supplier workspace for factory profile, certifications, export markets, and trade operations without touching the legacy seller flow.')
            : translate('Set up a procurement-ready buyer workspace for sourcing preferences, target markets, compliance documents, and trade operations.');
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
        <div>
            <span class="b2b-pill">{{ $isSupplier ? translate('Supplier Portal') : translate('Buyer Portal') }}</span>
            <h1 class="h3 mt-3 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    @include('b2b.company._form', [
        'company' => $company,
        'title' => $title,
        'action' => $actionRoute,
        'submitText' => $mode === 'edit' ? translate('Update and Continue') : translate('Create and Continue'),
        'verificationRequirements' => $verificationRequirements,
        'lockedCompanyType' => $lockedCompanyType,
        'allowedCompanyTypes' => $allowedCompanyTypes,
        'afterSubmitRoute' => $portalRedirectRoute,
        'introText' => $subtitle,
    ])
@endsection
