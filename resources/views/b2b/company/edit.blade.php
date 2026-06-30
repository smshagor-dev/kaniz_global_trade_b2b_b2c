@extends('b2b.layouts.app')

@section('panel_content')
    <div class="card overflow-hidden mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #123458, #1f5f8b 55%, #4f8fbf);">
        <div class="card-body p-4 p-lg-5 text-white">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge badge-inline badge-warning mb-3">{{ translate('B2B COMPANY WORKSPACE') }}</span>
                    <h1 class="fs-28 fw-700 mb-2">{{ translate('Edit B2B Company') }}</h1>
                    <p class="mb-0 opacity-80">{{ translate('Keep your business profile polished, compliant, and ready for buyers, suppliers, and internal approval review.') }}</p>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="bg-white text-dark rounded p-4">
                        <div class="text-muted fs-12">{{ translate('Company') }}</div>
                        <div class="fw-700 fs-18 mb-2">{{ $company->company_name }}</div>
                        <div class="fs-13">{{ translate('Type') }}: {{ ucfirst($company->company_type) }}</div>
                        <div class="fs-13">{{ translate('Verification') }}: {{ ucfirst($company->verification_status) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($company->verification_status !== 'approved')
        <div class="alert alert-warning shadow-none border">
            {{ translate('Your B2B company profile is not approved yet. You will need approval before creating wholesale products.') }}
        </div>
    @endif

    @include('b2b.company._form', [
        'company' => $company,
        'title' => translate('Company Profile'),
        'action' => route('b2b.company.update'),
        'submitText' => translate('Update Company Profile'),
        'verificationRequirements' => $verificationRequirements,
        'sectionButtonText' => translate('Save This Section'),
    ])
@endsection
