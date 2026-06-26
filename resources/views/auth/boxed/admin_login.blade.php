@extends('auth.layouts.authentication')

@section('content')
<!-- aiz-main-wrapper -->
<div class="aiz-main-wrapper d-flex flex-column justify-content-center align-items-center" style="min-height: 100vh; background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%), url('https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80'); background-size: cover; background-position: center; background-attachment: fixed; padding: 20px;">
    <div class="container">
        <div style="display: -ms-flexbox; display: flex;-ms-flex-wrap: wrap; flex-wrap: wrap; justify-content: center; align-items: center; margin-right: -40px; margin-left: -40px;">
            <!-- Right Side - Admin Login Form -->
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
                            background: linear-gradient(45deg, rgba(230, 46, 4, 0.15), rgba(230, 46, 4, 0.05));
                            border-radius: 50%;
                            z-index: 0;
                        "></div>
                    <div class="water-color-bg" style="
                            position: absolute;
                            bottom: -50px;
                            left: -50px;
                            width: 150px;
                            height: 150px;
                            background: linear-gradient(45deg, rgba(230, 46, 4, 0.1), rgba(255, 153, 0, 0.1));
                            border-radius: 50%;
                            z-index: 0;
                        "></div>

                    <div class="position-relative" style="z-index: 1;">
                        <!-- Site Icon -->
                        <div class="text-center mb-4">
                            <div class="water-color-circle mb-3" style="
                                    width: 48px;
                                    height: 48px;
                                    background: linear-gradient(135deg, rgba(230, 46, 4, 0.2), rgba(230, 46, 4, 0.1));
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
                            <h1 class="fs-24 fw-800 mb-2" style="
                                    background: linear-gradient(45deg, #e62e04, #ff9900);
                                    -webkit-background-clip: text;
                                    -webkit-text-fill-color: transparent;
                                    text-transform: uppercase;
                                    letter-spacing: 1px;
                                ">{{ translate('Welcome Back') }}</h1>
                            <p class="text-muted mb-4">{{ translate('Login to your admin account')}}</p>
                        </div>

                        <!-- Login form -->
                        <div class="pt-2">
                            <form class="form-default" id="login-form" role="form" action="{{ route('login') }}" method="POST">
                                @csrf
                                
                                <!-- Email -->
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
                                            value="{{ old('email') }}" placeholder="{{ translate('johndoe@example.com') }}" 
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
                                    
                                <!-- Password -->
                                <div class="form-group mb-4">
                                    <label for="password" class="fs-12 fw-700 text-dark mb-2">{{ translate('Password') }}</label>
                                    <div class="water-color-input position-relative" style="
                                            background: rgba(255, 255, 255, 0.9);
                                            border-radius: 12px;
                                            border: 1px solid rgba(230, 46, 4, 0.3);
                                            overflow: hidden;
                                            transition: all 0.3s ease;
                                        ">
                                        <input type="password" class="form-control {{ $errors->has('password') ? ' is-invalid' : '' }}" 
                                            placeholder="{{ translate('Password')}}" name="password" id="password" style="
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
                                                font-size: 1.2rem;
                                            " onclick="togglePassword()"></i>
                                    </div>
                                </div>

                                <!-- Recaptcha -->
                                @if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_admin_login') == 1)
                                    @if ($errors->has('g-recaptcha-response'))
                                        <span class="border invalid-feedback rounded p-2 mb-3 bg-danger text-white" role="alert" style="display: block;">
                                            <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                                        </span>
                                    @endif
                                @endif

                                <div class="row mb-3">
                                    <!-- Remember Me -->
                                    <div class="col-6">
                                        <label class="aiz-checkbox" style="display: flex; align-items: center;">
                                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <span class="ml-2 fs-12">{{ translate('Remember Me') }}</span>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                    
                                    <!-- Forgot password -->
                                    <div class="col-6 text-right">
                                        <a href="{{ route('password.request') }}" class="text-reset fs-12 fw-400" style="color: #e62e04;">
                                            <u>{{ translate('Forgot password?')}}</u>
                                        </a>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="mb-4">
                                    <button type="submit" class="btn btn-primary btn-block fw-700 fs-14" style="
                                            background: linear-gradient(45deg, #e62e04, #ff9900);
                                            border: none;
                                            border-radius: 12px;
                                            padding: 15px;
                                            transition: all 0.3s ease;
                                            box-shadow: 0 4px 15px rgba(230, 46, 4, 0.3);
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
                                            background: linear-gradient(45deg, rgba(230, 46, 4, 0.1), rgba(230, 46, 4, 0.05));
                                            border-radius: 12px;
                                            padding: 15px;
                                            border: 1px solid rgba(230, 46, 4, 0.3);
                                        ">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fs-12 fw-600">{{ translate('Admin Account')}}</span>
                                            <button class="btn btn-sm" onclick="autoFillAdmin()" style="
                                                background: rgba(230, 46, 4, 0.2);
                                                border: 1px solid rgba(230, 46, 4, 0.4);
                                                border-radius: 8px;
                                                color: #e62e04;
                                                padding: 6px 15px;
                                                transition: all 0.3s ease;
                                            ">{{ translate('Copy credentials') }}</button>
                                        </div>
                                        <div class="fs-12 text-muted">
                                            <div class="row">
                                                <div class="col-6">
                                                    <strong>{{ translate('Email:') }}</strong> admin@example.com
                                                </div>
                                                <div class="col-6">
                                                    <strong>{{ translate('Password:') }}</strong> 123456
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
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
    <script type="text/javascript">
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

        function autoFillAdmin(){
            $('#email').val('admin@example.com');
            $('#password').val('123456');
            // Add water effect to inputs
            $('.water-color-input').css({
                'border-color': 'rgba(46, 204, 113, 0.5)',
                'box-shadow': '0 0 0 3px rgba(46, 204, 113, 0.2)'
            });
            setTimeout(() => {
                $('.water-color-input').css({
                    'border-color': 'rgba(230, 46, 4, 0.3)',
                    'box-shadow': 'none'
                });
            }, 1000);
        }

        document.addEventListener('DOMContentLoaded', function() {
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
                    this.style.borderColor = 'rgba(230, 46, 4, 0.5)';
                    this.style.boxShadow = '0 5px 15px rgba(230, 46, 4, 0.1)';
                });
                input.addEventListener('mouseleave', function() {
                    this.style.borderColor = 'rgba(230, 46, 4, 0.3)';
                    this.style.boxShadow = 'none';
                });
                
                const inputField = input.querySelector('input');
                if (inputField) {
                    inputField.addEventListener('focus', function() {
                        this.closest('.water-color-input').style.borderColor = 'rgba(230, 46, 4, 0.7)';
                        this.closest('.water-color-input').style.boxShadow = '0 5px 20px rgba(230, 46, 4, 0.15)';
                    });
                    inputField.addEventListener('blur', function() {
                        this.closest('.water-color-input').style.borderColor = 'rgba(230, 46, 4, 0.3)';
                        this.closest('.water-color-input').style.boxShadow = 'none';
                    });
                }
            });

            // Add hover effect to forgot password link
            const forgotLink = document.querySelector('a[href="{{ route("password.request") }}"]');
            if (forgotLink) {
                forgotLink.addEventListener('mouseenter', function() {
                    this.style.color = '#ff9900';
                });
                forgotLink.addEventListener('mouseleave', function() {
                    this.style.color = '#e62e04';
                });
            }

            // Add hover effect to back button
            const backBtn = document.querySelector('a[href="{{ url()->previous() }}"]');
            if (backBtn) {
                backBtn.addEventListener('mouseenter', function() {
                    this.style.background = 'rgba(230, 46, 4, 0.1)';
                    this.style.borderColor = 'rgba(230, 46, 4, 0.5)';
                });
                backBtn.addEventListener('mouseleave', function() {
                    this.style.background = 'rgba(255, 255, 255, 0.9)';
                    this.style.borderColor = 'rgba(230, 46, 4, 0.3)';
                });
            }

            // Add event listener to eye icon
            const eyeIcon = document.querySelector('.password-toggle');
            if (eyeIcon) {
                eyeIcon.addEventListener('click', togglePassword);
            }
        });
    </script>

    @if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_admin_login') == 1)
        <script src="https://www.google.com/recaptcha/api.js?render={{ env('CAPTCHA_KEY') }}"></script>
        
        <script type="text/javascript">
            document.getElementById('login-form').addEventListener('submit', function(e) {
                e.preventDefault();
                grecaptcha.ready(function() {
                    grecaptcha.execute(`{{ env('CAPTCHA_KEY') }}`, {action: 'admin_login'}).then(function(token) {
                        var input = document.createElement('input');
                        input.setAttribute('type', 'hidden');
                        input.setAttribute('name', 'g-recaptcha-response');
                        input.setAttribute('value', token);
                        e.target.appendChild(input);

                        var actionInput = document.createElement('input');
                        actionInput.setAttribute('type', 'hidden');
                        actionInput.setAttribute('name', 'recaptcha_action');
                        actionInput.setAttribute('value', 'recaptcha_admin_login');
                        e.target.appendChild(actionInput);
                        
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
        box-shadow: 0 10px 25px rgba(230, 46, 4, 0.4) !important;
    }

    /* Water color ripple effect for inputs */
    .water-color-input:focus-within {
        animation: waterRipple 0.6s ease-out;
    }

    @keyframes waterRipple {
        0% {
            box-shadow: 0 0 0 0 rgba(230, 46, 4, 0.3);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(230, 46, 4, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(230, 46, 4, 0);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .col-lg-5 {
            max-width: 450px;
            margin: 0 auto;
        }
    }

    @media (max-width: 575.98px) {
        .water-color-card {
            padding: 2rem !important;
        }
        .water-color-alert .row .col-6 {
            margin-bottom: 8px;
        }
    }
</style>