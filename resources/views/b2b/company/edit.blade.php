@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Edit B2B Company') }}</h1>
            </div>
        </div>
    </div>

    @if ($company->verification_status !== 'approved')
        <div class="alert alert-warning">
            {{ translate('Your B2B company profile is not approved yet. You will need approval before creating wholesale products.') }}
        </div>
    @endif

    @include('b2b.company._form', [
        'company' => $company,
        'title' => translate('Company Profile'),
        'action' => route('b2b.company.update'),
        'submitText' => translate('Update Company Profile'),
        'verificationRequirements' => $verificationRequirements,
    ])
@endsection
