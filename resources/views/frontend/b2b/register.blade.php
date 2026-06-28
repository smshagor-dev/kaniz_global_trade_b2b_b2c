@extends('auth.layouts.authentication')

@section('content')
@php
    $isSupplier = $portal === 'supplier';
    $brandLogo = get_setting('header_logo')
        ? uploaded_asset(get_setting('header_logo'))
        : uploaded_asset(get_setting('site_icon'));
    $title = $isSupplier ? translate('Register as a Supplier') : translate('Register as a Buyer');
    $subtitle = $isSupplier
        ? translate('Create your supplier account and company profile to access the B2B supplier workspace.')
        : translate('Create your buyer account and company profile to access the B2B buyer workspace.');
    $submitText = $isSupplier ? translate('Register Your Supplier Account') : translate('Register Your Buyer Account');
    $loginRoute = $isSupplier ? route('supplier.login') : route('buyer.login');
    $verificationSubtitle = $isSupplier
        ? translate('Upload the core business documents needed to start supplier verification.')
        : translate('Upload the core business documents needed to start buyer verification.');
@endphp

<div class="aiz-main-wrapper d-flex flex-column justify-content-center align-items-center" style="min-height: 100vh; background: #f5f5f5; padding: 20px;">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-center">
            <div class="col-lg-6">
                <div class="p-4 p-lg-5 bg-white border rounded shadow-sm position-relative overflow-hidden">
                    <div class="position-relative">
                        <div class="text-center mb-4">
                            <div class="mb-3 d-flex justify-content-center">
                                <img src="{{ $brandLogo }}" alt="{{ get_setting('site_name') }}" style="max-height: 56px; width: auto; max-width: 220px;">
                            </div>
                            <h1 class="fs-24 fw-700 text-dark mb-2">{{ $title }}</h1>
                            <p class="text-muted mb-0">{{ $subtitle }}</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
                            <div class="d-flex align-items-center flex-wrap w-100 b2b-stepper" style="gap: 10px;">
                                <div class="b2b-step-item is-active" data-step-indicator="1">
                                    <span class="b2b-step-count">1</span>
                                    <span class="b2b-step-label">{{ translate('Personal Info') }}</span>
                                </div>
                                <div class="b2b-step-line"></div>
                                <div class="b2b-step-item" data-step-indicator="2">
                                    <span class="b2b-step-count">2</span>
                                    <span class="b2b-step-label">{{ translate('Company Info') }}</span>
                                </div>
                                <div class="b2b-step-line"></div>
                                <div class="b2b-step-item" data-step-indicator="3">
                                    <span class="b2b-step-count">3</span>
                                    <span class="b2b-step-label">{{ translate('Verification Docs') }}</span>
                                </div>
                            </div>
                        </div>

                        <form id="b2b-register-form" class="form-default" action="{{ $action }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="portal" value="{{ $portal }}">

                            <div class="b2b-form-step" data-step="1">
                                <div class="mb-3">
                                    <div class="bg-soft-primary rounded px-3 py-2 border-left border-primary border-width-2">
                                        <div class="fs-14 fw-600 text-primary">{{ translate('Personal Info') }}</div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Your Name') }}</label>
                                    <input type="text" class="form-control rounded-0" name="name" value="{{ old('name') }}" placeholder="{{ translate('Full Name') }}" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Your Email') }}</label>
                                    <input type="email" class="form-control rounded-0" name="email" value="{{ old('email') }}" placeholder="{{ translate('Email Address') }}" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Your Phone') }}</label>
                                    <input type="text" class="form-control rounded-0" name="phone" value="{{ old('phone') }}" placeholder="{{ translate('Phone Number') }}" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Password') }}</label>
                                    <input id="password" type="password" class="form-control rounded-0" name="password" placeholder="{{ translate('Create Password') }}" required>
                                    <small class="text-muted d-block mt-2">{{ translate('Password must contain at least 6 digits') }}</small>
                                </div>

                                <div class="form-group mb-4">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Confirm Password') }}</label>
                                    <input id="password_confirmation" type="password" class="form-control rounded-0" name="password_confirmation" placeholder="{{ translate('Confirm Password') }}" required>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary rounded-0 px-4" data-step-next>{{ translate('Continue to Company Info') }}</button>
                                </div>
                            </div>

                            <div class="b2b-form-step d-none" data-step="2">
                                <div class="mb-3">
                                    <div class="bg-soft-primary rounded px-3 py-2 border-left border-primary border-width-2">
                                        <div class="fs-14 fw-600 text-primary">{{ translate('Company Info') }}</div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Company Name') }}</label>
                                    <input type="text" class="form-control rounded-0" name="company_name" value="{{ old('company_name') }}" placeholder="{{ translate('Company Name') }}" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Company Type') }}</label>
                                    <input type="hidden" name="company_type" value="{{ $companyType }}">
                                    <input type="text" class="form-control rounded-0 bg-soft-secondary" value="{{ $companyTypeLabel }}" disabled>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Business Email') }}</label>
                                    <input type="email" class="form-control rounded-0" name="business_email" value="{{ old('business_email', old('email')) }}" placeholder="{{ translate('Business Email') }}" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Country') }}</label>
                                    <input type="text" class="form-control rounded-0" name="country" value="{{ old('country') }}" placeholder="{{ translate('Country') }}" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('City') }}</label>
                                    <input type="text" class="form-control rounded-0" name="city" value="{{ old('city') }}" placeholder="{{ translate('City') }}">
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Address') }}</label>
                                    <input type="text" class="form-control rounded-0" name="address" value="{{ old('address') }}" placeholder="{{ translate('Address') }}">
                                </div>

                                <div class="form-group mb-4">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Website') }}</label>
                                    <input type="url" class="form-control rounded-0" name="website" value="{{ old('website') }}" placeholder="https://example.com">
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-soft-secondary rounded-0 px-4" data-step-prev>{{ translate('Back') }}</button>
                                    <button type="button" class="btn btn-primary rounded-0 px-4" data-step-next>{{ translate('Continue to Documents') }}</button>
                                </div>
                            </div>

                            <div class="b2b-form-step d-none" data-step="3">
                                <div class="mb-3">
                                    <div class="bg-soft-primary rounded px-3 py-2 border-left border-primary border-width-2">
                                        <div class="fs-14 fw-600 text-primary">{{ translate('Initial Company Documents') }}</div>
                                    </div>
                                </div>

                                <div class="alert alert-soft-info border mb-4">
                                    <div class="fw-600 mb-1">{{ translate('Verification Submission') }}</div>
                                    <p class="mb-0 fs-13">{{ $verificationSubtitle }}</p>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Company Logo') }} <span class="text-muted">{{ translate('(Optional)') }}</span></label>
                                    <input type="file" class="form-control rounded-0" name="logo" accept=".jpg,.jpeg,.png">
                                    <small class="text-muted d-block mt-2">{{ translate('Upload your company or brand logo if available.') }}</small>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Trade License / Registration Document') }} <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control rounded-0" name="trade_license_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted d-block mt-2">{{ translate('Upload the main government registration or trade license document.') }}</small>
                                </div>

                                <div class="form-group mb-4">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Tax / VAT / Business ID Document') }} <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control rounded-0" name="tax_document_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted d-block mt-2">{{ translate('Upload your tax certificate, VAT registration, or equivalent company ID document.') }}</small>
                                </div>

                                <div class="d-flex justify-content-between flex-wrap" style="gap: 10px;">
                                    <button type="button" class="btn btn-soft-secondary rounded-0 px-4" data-step-prev>{{ translate('Back') }}</button>
                                    <button type="submit" class="btn btn-primary rounded-0 px-4 fw-700">
                                        {{ $submitText }}
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="text-center pt-3 mt-4 border-top">
                            <p class="fs-12 text-muted mb-0">
                                {{ translate('Already have an account?') }}
                                <a href="{{ $loginRoute }}" class="ml-2 fs-14 fw-700 hov-text-primary">{{ translate('Login Now') }}</a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <div class="d-flex justify-content-center align-items-center flex-wrap" style="gap: 15px;">
                        <a href="{{ route('home') }}" class="text-dark fs-14 fw-600 d-inline-flex align-items-center bg-white border rounded px-4 py-2 text-decoration-none">
                            <i class="las la-home fs-18 mr-2 text-primary"></i>
                            {{ translate('Back to Home') }}
                        </a>
                        <a href="{{ url()->previous() }}" class="text-dark fs-14 fw-600 d-inline-flex align-items-center bg-white border rounded px-4 py-2 text-decoration-none">
                            <i class="las la-arrow-left fs-18 mr-2 text-primary"></i>
                            {{ translate('Back to Previous Page') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const steps = Array.from(document.querySelectorAll('.b2b-form-step'));
        const indicators = Array.from(document.querySelectorAll('[data-step-indicator]'));
        let currentStep = 1;

        const showStep = (stepNumber) => {
            currentStep = stepNumber;

            steps.forEach((step) => {
                step.classList.toggle('d-none', Number(step.dataset.step) !== stepNumber);
            });

            indicators.forEach((indicator) => {
                const indicatorStep = Number(indicator.dataset.stepIndicator);
                indicator.classList.toggle('is-active', indicatorStep === stepNumber);
                indicator.classList.toggle('is-complete', indicatorStep < stepNumber);
            });
        };

        document.querySelectorAll('[data-step-next]').forEach((button) => {
            button.addEventListener('click', function () {
                showStep(Math.min(currentStep + 1, steps.length));
            });
        });

        document.querySelectorAll('[data-step-prev]').forEach((button) => {
            button.addEventListener('click', function () {
                showStep(Math.max(currentStep - 1, 1));
            });
        });

        showStep(currentStep);
    });
</script>
@endsection

<style>
    .b2b-stepper {
        row-gap: 12px;
    }

    .b2b-step-item {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #6c757d;
        font-size: 13px;
        font-weight: 600;
    }

    .b2b-step-count {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1px solid #d6dbe1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
    }

    .b2b-step-item.is-active,
    .b2b-step-item.is-complete {
        color: #0d6efd;
    }

    .b2b-step-item.is-active .b2b-step-count,
    .b2b-step-item.is-complete .b2b-step-count {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }

    .b2b-step-line {
        flex: 1 1 40px;
        min-width: 24px;
        height: 1px;
        background: #d6dbe1;
    }
</style>
