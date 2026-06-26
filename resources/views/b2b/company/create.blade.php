@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Create B2B Company') }}</h1>
            </div>
        </div>
    </div>

    @include('b2b.company._form', [
        'title' => translate('Company Profile'),
        'action' => route('b2b.company.store'),
        'submitText' => translate('Submit Company Profile'),
        'verificationRequirements' => $verificationRequirements,
    ])
@endsection
