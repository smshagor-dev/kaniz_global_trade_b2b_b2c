@extends('auth.layouts.authentication')

@section('content')
<!-- aiz-main-wrapper -->
<div class="aiz-main-wrapper d-flex flex-column justify-content-center align-items-center" style="min-height: 100vh; background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%), url('https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80'); background-size: cover; background-position: center; background-attachment: fixed; padding: 20px;">
    <div class="container">
        <div style="display: -ms-flexbox; display: flex;-ms-flex-wrap: wrap; flex-wrap: wrap; justify-content: center; align-items: center; margin-right: -40px; margin-left: -40px;">
            <!-- Left Content Area (For balance) -->
            <div class="col-lg-6 d-none d-lg-block">
                <div class="text-center px-5">
                    <h2 class="display-4 fw-800 text-primary mb-4" style="font-family: 'Playfair Display', serif;">{{ translate('Welcome to') }} {{ get_setting('site_name') }}</h2>
                    <p class="fs-5 text-muted mb-4">{{ translate('Discover amazing products and exclusive deals in ') }} {{ get_setting('site_motto') }}</p>
                    <div class="mt-5">
                        <div class="row">
                            <div class="col-4 text-center">
                                <div class="water-color-icon mb-3" style="background: rgba(52, 152, 219, 0.1); padding: 20px; border-radius: 50%; display: inline-block;">
                                    <i class="las la-shipping-fast la-3x text-primary"></i>
                                </div>
                                <h6 class="fw-700">{{ translate('Free Shipping') }}</h6>
                            </div>
                            <div class="col-4 text-center">
                                <div class="water-color-icon mb-3" style="background: rgba(46, 204, 113, 0.1); padding: 20px; border-radius: 50%; display: inline-block;">
                                    <i class="las la-shield-alt la-3x text-success"></i>
                                </div>
                                <h6 class="fw-700">{{ translate('Secure Payment') }}</h6>
                            </div>
                            <div class="col-4 text-center">
                                <div class="water-color-icon mb-3" style="background: rgba(155, 89, 182, 0.1); padding: 20px; border-radius: 50%; display: inline-block;">
                                    <i class="las la-headset la-3x text-purple"></i>
                                </div>
                                <h6 class="fw-700">{{ translate('24/7 Support') }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="col-lg-5">
                <div class="water-color-card p-4 p-lg-5" style="
                        background: rgba(255, 255, 255, 0.95);
                        border-radius: 20px;
                        box-shadow: 
                            0 8px 32px rgba(31, 38, 135, 0.15),
                            inset 0 0 0 1px rgba(255, 255, 255, 0.3);
                        backdrop-filter: blur(10px);
                        border: 1px solid rgba(255, 255, 255, 0.5);
                        position: relative;
                        overflow: hidden;
                    ">
                    <!-- Water color effect background elements -->
                    <div class="water-color-bg" style="
                            position: absolute;
                            top: -50px;
                            right: -50px;
                            width: 200px;
                            height: 200px;
                            background: linear-gradient(45deg, rgba(52, 152, 219, 0.15), rgba(155, 89, 182, 0.15));
                            border-radius: 50%;
                            z-index: 0;
                        "></div>
                    <div class="water-color-bg" style="
                            position: absolute;
                            bottom: -50px;
                            left: -50px;
                            width: 150px;
                            height: 150px;
                            background: linear-gradient(45deg, rgba(46, 204, 113, 0.1), rgba(241, 196, 15, 0.1));
                            border-radius: 50%;
                            z-index: 0;
                        "></div>

                    <div class="position-relative" style="z-index: 1;">
                        <!-- Site Icon -->
                        <div class="text-center mb-4">
                            <div class="water-color-circle mb-3" style="
                                    width: 48px;
                                    height: 48px;
                                    background: linear-gradient(135deg, rgba(52, 152, 219, 0.2), rgba(155, 89, 182, 0.2));
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    margin: 0 auto;
                                    border: 2px solid rgba(255, 255, 255, 0.8);
                                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                                ">
                                <img src="{{ uploaded_asset(get_setting('site_icon')) }}" alt="{{ translate('Site Icon')}}" class="img-fit" style="height: 50px;">
                            </div>

                            <!-- Titles -->
                            <h1 class="fs-24 fw-800 text-dark mb-2" style="
                                    background: linear-gradient(45deg, rgb(230, 46, 4), rgb(255, 153, 0));
                                    -webkit-background-clip: text;
                                    -webkit-text-fill-color: transparent;
                                    text-transform: uppercase;
                                    letter-spacing: 1px;
                                ">{{ translate('Welcome Back !')}}</h1>
                            <p class="text-muted mb-4">{{ translate('Login to your account')}}</p>
                        </div>

                        <!-- Login form -->
                        <div class="pt-2">
                            <form class="form-default loginForm" id="user-login-form" role="form" action="{{ route('login') }}" method="POST">
                                @csrf

                                <!-- Email or Phone -->
                                @if (addon_is_activated('otp_system'))
                                <div class="form-group phone-form-group mb-3">
                                    <label for="phone" class="fs-12 fw-700 text-dark mb-2">{{ translate('Phone') }}</label>
                                    <div class="water-color-input" style="
                                                background: rgba(255, 255, 255, 0.9);
                                                border-radius: 12px;
                                                border: 1px solid rgba(230, 46, 4, 0.3);
                                                overflow: hidden;
                                                transition: all 0.3s ease;
                                            ">
                                        <input type="tel" phone-number id="phone-code" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}"
                                            value="{{ old('phone') }}" placeholder="{{ translate('Enter phone number') }}"
                                            name="phone" autocomplete="off" style="
                                                    border: none;
                                                    background: transparent;
                                                    padding: 14px 20px;
                                                ">
                                    </div>
                                </div>

                                <input type="hidden" name="country_code" value="">

                                <div class="form-group email-form-group mb-3 d-none">
                                    <label for="email" class="fs-12 fw-700 text-dark mb-2">{{ translate('Email') }}</label>
                                    <div class="water-color-input" style="
                                                background: rgba(255, 255, 255, 0.9);
                                                border-radius: 12px;
                                                border: 1px solid rgba(230, 46, 4, 0.3);
                                                overflow: hidden;
                                            ">
                                        <input type="email" class="form-control {{ $errors->has('email') ? ' is-invalid' : '' }}"
                                            value="{{ old('email') }}" placeholder="{{  translate('johndoe@example.com') }}"
                                            name="email" id="email" autocomplete="off" style="
                                                    border: none;
                                                    background: transparent;
                                                    padding: 14px 20px;
                                                ">
                                    </div>
                                    @if ($errors->has('email'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                    @endif
                                </div>

                                <div class="form-group text-right mb-3">
                                    <button class="btn btn-link p-0 text-primary fs-12 fw-500" type="button" onclick="toggleEmailPhone(this)" style="
                                                background: rgba(52, 152, 219, 0.1);
                                                padding: 6px 12px;
                                                border-radius: 20px;
                                                border: none;
                                            "><i>*{{ translate('Use Email Instead') }}</i></button>
                                </div>
                                @else
                                <div class="form-group mb-4">
                                    <label for="email" class="fs-12 fw-700 text-dark mb-2">{{ translate('Email') }}</label>
                                    <div class="water-color-input" style="
                                                background: rgba(255, 255, 255, 0.9);
                                                border-radius: 12px;
                                                border: 1px solid rgba(230, 46, 4, 0.3);
                                                overflow: hidden;
                                                transition: all 0.3s ease;
                                            ">
                                        <input type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                                            value="{{ old('email') }}" placeholder="{{  translate('johndoe@example.com') }}"
                                            name="email" id="email" autocomplete="off" style="
                                                    border: none;
                                                    background: transparent;
                                                    padding: 14px 20px;
                                                ">
                                    </div>
                                    @if ($errors->has('email'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                @endif

                                <div class="password-login-block">
                                    <!-- password -->
                                    <div class="form-group mb-4">
                                        <label for="password" class="fs-12 fw-700 text-dark mb-2">{{ translate('Password') }}</label>
                                        <div class="water-color-input position-relative" style="
                                                background: rgba(255, 255, 255, 0.9);
                                                border-radius: 12px;
                                                border: 1px solid rgba(52, 152, 219, 0.3);
                                                overflow: hidden;
                                                transition: all 0.3s ease;
                                            ">
                                            <input type="password" class="form-control {{ $errors->has('password') ? ' is-invalid' : '' }}"
                                                placeholder="{{ translate('Enter password') }}" name="password" id="password" style="
                                                    border: none;
                                                    background: transparent;
                                                    padding: 14px 20px;
                                                ">
                                            <i class="password-toggle las la-eye position-absolute" style="
                                                    right: 15px;
                                                    top: 50%;
                                                    transform: translateY(-50%);
                                                    cursor: pointer;
                                                    color: #666;
                                                " onclick="togglePassword()"></i>
                                        </div>
                                    </div>

                                    <!-- Recaptcha -->
                                    @if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_customer_login') == 1)

                                    @if ($errors->has('g-recaptcha-response'))
                                    <span class="border invalid-feedback rounded p-2 mb-3 bg-danger text-white" role="alert" style="display: block;">
                                        <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                                    </span>
                                    @endif
                                    @endif

                                    <div class="row mb-2">
                                        <!-- Remember Me -->
                                        <div class="col-5">
                                            <label class="aiz-checkbox">
                                                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                                <span class="has-transition fs-12 fw-400 text-gray-dark hov-text-primary">{{ translate('Remember Me') }}</span>
                                                <span class="aiz-square-check"></span>
                                            </label>
                                        </div>
                                        <!-- Forgot password -->
                                        <div class="col-7 text-right">
                                            @if(get_setting('login_with_otp'))
                                            <a href="javascript:void(0);" class="text-reset fs-12 fw-400 text-gray-dark hov-text-primary toggle-login-with-otp" onclick="toggleLoginPassOTP(this)">{{ translate('Login With OTP') }} / </a>
                                            @endif
                                            <a href="{{ route('password.request') }}" class="text-reset fs-12 fw-400 text-gray-dark hov-text-primary"><u>{{ translate('Forgot password?')}}</u></a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="mb-4">
                                    <button type="submit" class="btn btn-primary btn-block fw-700 fs-14" style="
                                            background: linear-gradient(45deg, rgb(230, 46, 4), rgb(255, 153, 0));
                                            border: none;
                                            border-radius: 12px;
                                            padding: 15px;
                                            transition: all 0.3s ease;
                                            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
                                            position: relative;
                                            overflow: hidden;
                                        ">
                                        <span style="position: relative; z-index: 2;">{{ translate('Login') }}</span>
                                        <div class="water-effect" style="
                                                position: absolute;
                                                top: 50%;
                                                left: 50%;
                                                width: 0;
                                                height: 0;
                                                border-radius: 50%;
                                                background: rgba(255, 255, 255, 0.3);
                                                transform: translate(-50%, -50%);
                                                transition: width 0.6s, height 0.6s;
                                            "></div>
                                    </button>
                                </div>
                            </form>

                            <!-- DEMO MODE -->
                            @if (env("DEMO_MODE") == "On")
                            <div class="mb-4">
                                <div class="water-color-alert" style="
                                        background: linear-gradient(45deg, rgb(230, 46, 4), rgb(255, 153, 0));
                                        border-radius: 12px;
                                        padding: 15px;
                                        border: 1px solid rgba(52, 152, 219, 0.3);
                                    ">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fs-12 fw-600">{{ translate('Customer Account')}}</span>
                                        <button class="btn btn-sm" onclick="autoFillCustomer()" style="
                                            background: rgba(52, 152, 219, 0.2);
                                            border: 1px solid rgba(52, 152, 219, 0.4);
                                            border-radius: 8px;
                                            color: #3498db;
                                            padding: 6px 15px;
                                            transition: all 0.3s ease;
                                        ">{{ translate('Copy credentials') }}</button>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Social Login -->
                            @if(get_setting('google_login') == 1 || get_setting('facebook_login') == 1 || get_setting('twitter_login') == 1 || get_setting('apple_login') == 1)
                            <div class="text-center mb-3 position-relative">
                                <hr style="border-color: rgba(0,0,0,0.1);">
                                <span class="bg-white fs-12 text-gray px-3" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">{{ translate('Or Login With')}}</span>
                            </div>
                            <ul class="list-inline social colored text-center mb-4">
                                @if (get_setting('facebook_login') == 1)
                                <li class="list-inline-item mx-1">
                                    <a href="{{ route('social.login', ['provider' => 'facebook']) }}" class="water-color-social facebook" style="
                                        display: inline-flex;
                                        align-items: center;
                                        justify-content: center;
                                        width: 45px;
                                        height: 45px;
                                        border-radius: 50%;
                                        background: linear-gradient(45deg, rgba(59, 89, 152, 0.1), rgba(59, 89, 152, 0.2));
                                        color: #3b5998;
                                        border: 1px solid rgba(59, 89, 152, 0.3);
                                        transition: all 0.3s ease;
                                    ">
                                        <i class="lab la-facebook-f" style="border-radius:50%;"></i>
                                    </a>
                                </li>
                                @endif
                                @if (get_setting('twitter_login') == 1)
                                <li class="list-inline-item mx-1">
                                    <a href="{{ route('social.login', ['provider' => 'twitter']) }}" class="water-color-social x-twitter" style="
                                        display: inline-flex;
                                        align-items: center;
                                        justify-content: center;
                                        width: 45px;
                                        height: 45px;
                                        border-radius: 50%;
                                        background: linear-gradient(45deg, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.2));
                                        color: #000;
                                        border: 1px solid rgba(0, 0, 0, 0.3);
                                        transition: all 0.3s ease;
                                    ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z" />
                                        </svg>
                                    </a>
                                </li>
                                @endif
                                @if(get_setting('google_login') == 1)
                                <li class="list-inline-item mx-1">
                                    <a href="{{ route('social.login', ['provider' => 'google']) }}" class="water-color-social google" style="
                                        display: inline-flex;
                                        align-items: center;
                                        justify-content: center;
                                        width: 45px;
                                        height: 45px;
                                        border-radius: 50%;
                                        background: linear-gradient(45deg, rgba(221, 75, 57, 0.1), rgba(221, 75, 57, 0.2));
                                        color: #dd4b39;
                                        border: 1px solid rgba(221, 75, 57, 0.3);
                                        transition: all 0.3s ease;
                                    ">
                                        <i class="lab la-google" style="border-radius:50%;"></i>
                                    </a>
                                </li>
                                @endif
                                @if (get_setting('apple_login') == 1)
                                <li class="list-inline-item mx-1">
                                    <a href="{{ route('social.login', ['provider' => 'apple']) }}" class="water-color-social apple" style="
                                        display: inline-flex;
                                        align-items: center;
                                        justify-content: center;
                                        width: 45px;
                                        height: 45px;
                                        border-radius: 50%;
                                        background: linear-gradient(45deg, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.2));
                                        color: #000;
                                        border: 1px solid rgba(0, 0, 0, 0.3);
                                        transition: all 0.3s ease;
                                    ">
                                        <i class="lab la-apple" style="border-radius:50%;"></i>
                                    </a>
                                </li>
                                @endif
                            </ul>
                            @endif
                        </div>

                        <!-- Register Now -->
                        <div class="text-center pt-3 border-top" style="border-color: rgba(0,0,0,0.1) !important;">
                            <p class="fs-12 text-gray mb-0">
                                {{ translate('Dont have an account?')}}
                                <a href="{{ route('user.registration') }}" class="ml-2 fs-14 fw-700" style="
                                    background: linear-gradient(45deg, rgb(230, 46, 4), rgb(255, 153, 0));
                                    -webkit-background-clip: text;
                                    -webkit-text-fill-color: transparent;
                                    text-decoration: none;
                                    position: relative;
                                ">
                                    {{ translate('Register Now')}}
                                    <span class="water-underline" style="
                                        position: absolute;
                                        bottom: -2px;
                                        left: 0;
                                        width: 100%;
                                        height: 2px;
                                        background: linear-gradient(45deg, rgb(230, 46, 4), rgb(255, 153, 0));
                                        border-radius: 2px;
                                        transform: scaleX(0);
                                        transition: transform 0.3s ease;
                                    "></span>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Go Back -->
                <div class="text-center mt-4">
                    <div class="d-flex justify-content-center align-items-center flex-wrap" style="gap: 15px;">
                        <a href="/" class="text-dark fs-14 fw-600 d-inline-flex align-items-center" style="
                            background: rgba(255, 255, 255, 0.9);
                            padding: 10px 20px;
                            border-radius: 25px;
                            text-decoration: none;
                            border: 1px solid rgba(52, 152, 219, 0.3);
                            transition: all 0.3s ease;
                        ">
                            <i class="las la-home fs-18 mr-2 text-primary"></i>
                            {{ translate('Back to Home')}}
                        </a>
                        <a href="{{ url()->previous() }}" class="text-dark fs-14 fw-600 d-inline-flex align-items-center" style="
                            background: rgba(255, 255, 255, 0.9);
                            padding: 10px 20px;
                            border-radius: 25px;
                            text-decoration: none;
                            border: 1px solid rgba(52, 152, 219, 0.3);
                            transition: all 0.3s ease;
                        ">
                            <i class="las la-arrow-left fs-18 mr-2 text-primary"></i>
                            {{ translate('Back to Previous Page')}}
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
    // Simple function to toggle password visibility
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.querySelector('.password-toggle');
        
        if (passwordInput && eyeIcon) {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('la-eye');
                eyeIcon.classList.add('la-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('la-eye-slash');
                eyeIcon.classList.add('la-eye');
            }
        }
    }

    function autoFillCustomer() {
        $('#email').val('customer@example.com');
        $('#password').val('123456');
        // Add water effect to inputs
        $('.water-color-input').css({
            'border-color': 'rgba(46, 204, 113, 0.5)',
            'box-shadow': '0 0 0 3px rgba(46, 204, 113, 0.2)'
        });
        setTimeout(() => {
            $('.water-color-input').css({
                'border-color': 'rgba(52, 152, 219, 0.3)',
                'box-shadow': 'none'
            });
        }, 1000);
    }

    // Toggle password visibility
    document.addEventListener('DOMContentLoaded', function() {
        // Also add click event listener to the eye icon
        const eyeIcon = document.querySelector('.password-toggle');
        if (eyeIcon) {
            eyeIcon.addEventListener('click', togglePassword);
        }

        // Add water effect to submit button
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                const waterEffect = this.querySelector('.water-effect');
                if (waterEffect) {
                    waterEffect.style.width = '300px';
                    waterEffect.style.height = '300px';
                    setTimeout(() => {
                        waterEffect.style.width = '0';
                        waterEffect.style.height = '0';
                    }, 600);
                }
            });
        }

        // Add hover effects to inputs
        const waterInputs = document.querySelectorAll('.water-color-input');
        waterInputs.forEach(input => {
            input.addEventListener('mouseenter', function() {
                this.style.borderColor = 'rgba(52, 152, 219, 0.5)';
                this.style.boxShadow = '0 5px 15px rgba(52, 152, 219, 0.1)';
            });
            input.addEventListener('mouseleave', function() {
                this.style.borderColor = 'rgba(52, 152, 219, 0.3)';
                this.style.boxShadow = 'none';
            });
            
            const inputField = input.querySelector('input');
            if (inputField) {
                inputField.addEventListener('focus', function() {
                    this.closest('.water-color-input').style.borderColor = 'rgba(52, 152, 219, 0.7)';
                    this.closest('.water-color-input').style.boxShadow = '0 5px 20px rgba(52, 152, 219, 0.15)';
                });
                inputField.addEventListener('blur', function() {
                    this.closest('.water-color-input').style.borderColor = 'rgba(52, 152, 219, 0.3)';
                    this.closest('.water-color-input').style.boxShadow = 'none';
                });
            }
        });

        // Add hover effect to social buttons
        const socialBtns = document.querySelectorAll('.water-color-social');
        socialBtns.forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.1)';
                this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            });
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
                this.style.boxShadow = 'none';
            });
        });

        // Add hover effect to register link
        const registerLink = document.querySelector('a[href="{{ route("user.registration") }}"]');
        if (registerLink) {
            const underline = registerLink.querySelector('.water-underline');
            if (underline) {
                registerLink.addEventListener('mouseenter', function() {
                    underline.style.transform = 'scaleX(1)';
                });
                registerLink.addEventListener('mouseleave', function() {
                    underline.style.transform = 'scaleX(0)';
                });
            }
        }
    });
</script>

@if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_customer_login') == 1)
<script src="https://www.google.com/recaptcha/api.js?render={{ env('CAPTCHA_KEY') }}"></script>

<script type="text/javascript">
    document.getElementById('user-login-form').addEventListener('submit', function(e) {
        e.preventDefault();
        grecaptcha.ready(function() {
            grecaptcha.execute(`{{ env('CAPTCHA_KEY') }}`, {
                action: 'register'
            }).then(function(token) {
                var input = document.createElement('input');
                input.setAttribute('type', 'hidden');
                input.setAttribute('name', 'g-recaptcha-response');
                input.setAttribute('value', token);
                e.target.appendChild(input);

                var actionInput = document.createElement('input');
                actionInput.setAttribute('type', 'hidden');
                actionInput.setAttribute('name', 'recaptcha_action');
                actionInput.setAttribute('value', 'recaptcha_customer_login');
                e.target.appendChild(actionInput);

                // Submit the form
                e.target.submit();
            });
        });
    });
</script>
@endif
@endsection

<style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap');

    body {
        font-family: 'Inter', sans-serif;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: 'Inter', sans-serif;
    }

    .water-color-card {
        animation: fadeInUp 0.8s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .water-color-bg {
        animation: float 6s ease-in-out infinite;
    }

    .water-color-bg:nth-child(2) {
        animation-delay: 2s;
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0) rotate(0deg);
        }
        50% {
            transform: translateY(-20px) rotate(10deg);
        }
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4) !important;
    }

    .water-color-icon {
        transition: all 0.3s ease;
    }

    .water-color-icon:hover {
        transform: translateY(-5px) scale(1.1);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    /* Water color ripple effect for inputs */
    .water-color-input:focus-within {
        animation: waterRipple 0.6s ease-out;
    }

    @keyframes waterRipple {
        0% {
            box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.3);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(52, 152, 219, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(52, 152, 219, 0);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .d-none.d-lg-block {
            display: none !important;
        }
        .col-lg-5 {
            max-width: 450px;
            margin: 0 auto;
        }
    }

    @media (max-width: 575.98px) {
        .water-color-card {
            padding: 2rem !important;
        }
        .col-4 {
            margin-bottom: 1rem;
        }
    }
</style>