@php
    $email = $email ?? null;
    $phone = $phone ?? null;
@endphp

<div class="aiz-main-wrapper d-flex flex-column justify-content-center align-items-center" style="min-height: 100vh; background: #f5f5f5; padding: 20px;">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-center">
            <div class="col-lg-6">
                <div class="p-4 p-lg-5 bg-white border rounded shadow-sm position-relative overflow-hidden">
                    <div class="position-relative">
                        @php
                            $brandLogo = get_setting('header_logo')
                                ? uploaded_asset(get_setting('header_logo'))
                                : uploaded_asset(get_setting('site_icon'));
                        @endphp
                        <div class="text-center mb-4">
                            <div class="mb-3 d-flex justify-content-center">
                                <img src="{{ $brandLogo }}" alt="{{ get_setting('site_name') }}" style="max-height: 56px; width: auto; max-width: 220px;">
                            </div>
                            <h1 class="fs-24 fw-700 text-dark mb-2">{{ translate('Register Your Shop') }}</h1>
                            <p class="text-muted mb-0">{{ translate('Create your seller account in three simple steps.') }}</p>
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
                            <div class="d-flex align-items-center flex-wrap w-100 seller-stepper" style="gap: 10px;">
                                <div class="seller-step-item is-active" data-step-indicator="1">
                                    <span class="seller-step-count">1</span>
                                    <span class="seller-step-label">{{ translate('Personal Info') }}</span>
                                </div>
                                <div class="seller-step-line"></div>
                                <div class="seller-step-item" data-step-indicator="2">
                                    <span class="seller-step-count">2</span>
                                    <span class="seller-step-label">{{ translate('Shop Info') }}</span>
                                </div>
                                <div class="seller-step-line"></div>
                                <div class="seller-step-item" data-step-indicator="3">
                                    <span class="seller-step-count">3</span>
                                    <span class="seller-step-label">{{ translate('Verification Docs') }}</span>
                                </div>
                            </div>
                        </div>

                        <form id="reg-form" class="form-default" role="form" action="{{ route('shops.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="seller-form-step" data-step="1">
                                <div class="mb-3">
                                    <div class="bg-soft-primary rounded px-3 py-2 border-left border-primary border-width-2">
                                        <div class="fs-14 fw-600 text-primary">{{ translate('Personal Info') }}</div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Your Name') }}</label>
                                    <input type="text" class="form-control rounded-0{{ $errors->has('name') ? ' is-invalid' : '' }}" value="{{ old('name') }}" placeholder="{{ translate('Full Name') }}" name="name" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Your Email') }}</label>
                                    <input type="email" class="form-control rounded-0{{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ $email ?? old('email') }}" placeholder="{{ translate('Email Address') }}" name="email" required {{ $email ? 'readonly' : '' }}>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Your Phone') }}</label>
                                    <input type="tel" class="form-control rounded-0{{ $errors->has('phone') ? ' is-invalid' : '' }}" value="{{ $phone ?? old('phone') }}" placeholder="{{ translate('Phone Number') }}" name="phone" required {{ $phone ? 'readonly' : '' }}>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Password') }}</label>
                                    <input id="password" type="password" class="form-control rounded-0{{ $errors->has('password') ? ' is-invalid' : '' }}" placeholder="{{ translate('Create Password') }}" name="password" required>
                                    <small class="text-muted d-block mt-2">{{ translate('Password must contain at least 6 digits') }}</small>
                                </div>

                                <div class="form-group mb-4">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Confirm Password') }}</label>
                                    <input id="password_confirmation" type="password" class="form-control rounded-0" placeholder="{{ translate('Confirm Password') }}" name="password_confirmation" required>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary rounded-0 px-4" data-step-next>{{ translate('Continue to Shop Info') }}</button>
                                </div>
                            </div>

                            <div class="seller-form-step d-none" data-step="2">
                                <div class="mb-3">
                                    <div class="bg-soft-primary rounded px-3 py-2 border-left border-primary border-width-2">
                                        <div class="fs-14 fw-600 text-primary">{{ translate('Shop Info') }}</div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Shop Name') }}</label>
                                    <input type="text" class="form-control rounded-0{{ $errors->has('shop_name') ? ' is-invalid' : '' }}" value="{{ old('shop_name') }}" placeholder="{{ translate('Shop Name') }}" name="shop_name" required>
                                </div>

                                <div class="form-group mb-4">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Address') }}</label>
                                    <input type="text" class="form-control rounded-0{{ $errors->has('address') ? ' is-invalid' : '' }}" value="{{ old('address') }}" placeholder="{{ translate('Full Address') }}" name="address" required>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-soft-secondary rounded-0 px-4" data-step-prev>{{ translate('Back') }}</button>
                                    <button type="button" class="btn btn-primary rounded-0 px-4" data-step-next>{{ translate('Continue to Documents') }}</button>
                                </div>
                            </div>

                            <div class="seller-form-step d-none" data-step="3">
                                <div class="mb-3">
                                    <div class="bg-soft-primary rounded px-3 py-2 border-left border-primary border-width-2">
                                        <div class="fs-14 fw-600 text-primary">{{ translate('Initial Verification Documents') }}</div>
                                    </div>
                                </div>

                                <div class="alert alert-soft-info border mb-4">
                                    <div class="fw-600 mb-1">{{ translate('Verification Submission') }}</div>
                                    <p class="mb-0 fs-13">{{ translate('Submit your core business documents now so the seller verification team can start the review immediately.') }}</p>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Registration / Tax Number') }}</label>
                                    <input type="text" class="form-control rounded-0{{ $errors->has('certificate_number') ? ' is-invalid' : '' }}" value="{{ old('certificate_number') }}" placeholder="{{ translate('VAT / TIN / BIN / Trade License Number') }}" name="certificate_number" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Trade License / Registration Document') }} <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control rounded-0{{ $errors->has('certificate') ? ' is-invalid' : '' }}" name="certificate" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted d-block mt-2">{{ translate('Upload the main company registration, trade license, or tax certificate.') }}</small>
                                </div>

                                <div class="form-group mb-4">
                                    <label class="fs-12 fw-700 text-dark mb-2">{{ translate('Owner / Representative ID Card') }} <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control rounded-0{{ $errors->has('id_card') ? ' is-invalid' : '' }}" name="id_card" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted d-block mt-2">{{ translate('Upload one government ID of the primary contact or business owner.') }}</small>
                                </div>

                                @if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_seller_register') == 1)
                                    @if ($errors->has('g-recaptcha-response'))
                                        <span class="border invalid-feedback rounded p-2 mb-3 bg-danger text-white d-block" role="alert">
                                            <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                                        </span>
                                    @endif
                                @endif

                                <div class="d-flex justify-content-between flex-wrap" style="gap: 10px;">
                                    <button type="button" class="btn btn-soft-secondary rounded-0 px-4" data-step-prev>{{ translate('Back') }}</button>
                                    <button type="submit" class="btn btn-primary rounded-0 px-4 fw-700">{{ translate('Register Your Shop') }}</button>
                                </div>
                            </div>
                        </form>

                        <div class="text-center pt-3 mt-4 border-top">
                            <p class="fs-12 text-muted mb-0">
                                {{ translate('Already have an account?') }}
                                <a href="{{ route('seller.login') }}" class="ml-2 fs-14 fw-700 hov-text-primary">{{ translate('Log In') }}</a>
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

@if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_seller_register') == 1)
    <script src="https://www.google.com/recaptcha/api.js?render={{ env('CAPTCHA_KEY') }}"></script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const steps = Array.from(document.querySelectorAll('.seller-form-step'));
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

        @if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_seller_register') == 1)
        document.getElementById('reg-form').addEventListener('submit', function(e) {
            e.preventDefault();
            grecaptcha.ready(function() {
                grecaptcha.execute(`{{ env('CAPTCHA_KEY') }}`, {action: 'selller_registration'}).then(function(token) {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'hidden');
                    input.setAttribute('name', 'g-recaptcha-response');
                    input.setAttribute('value', token);
                    e.target.appendChild(input);
                    e.target.submit();
                });
            });
        });
        @endif

        showStep(currentStep);
    });
</script>

<style>
    .seller-stepper {
        row-gap: 12px;
    }

    .seller-step-item {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #6c757d;
        font-size: 13px;
        font-weight: 600;
    }

    .seller-step-count {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1px solid #d6dbe1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
    }

    .seller-step-item.is-active,
    .seller-step-item.is-complete {
        color: #0d6efd;
    }

    .seller-step-item.is-active .seller-step-count,
    .seller-step-item.is-complete .seller-step-count {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }

    .seller-step-line {
        flex: 1 1 40px;
        min-width: 24px;
        height: 1px;
        background: #d6dbe1;
    }
</style>
