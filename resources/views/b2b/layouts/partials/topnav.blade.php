<div class="aiz-topbar px-15px px-lg-25px d-flex align-items-stretch justify-content-between">
    <div class="d-flex align-items-center">
        <div class="aiz-topbar-nav-toggler d-flex align-items-center justify-content-start mr-2 mr-md-3 ml-0" data-toggle="aiz-mobile-nav">
            <button class="aiz-mobile-toggler">
                <span></span>
            </button>
        </div>
        <div class="d-none d-md-block">
            <div class="fw-700 fs-15">{{ $portalTitle }}</div>
            <div class="b2b-portal-topbar-note">{{ $portalSubtitle }}</div>
        </div>
    </div>

    <div class="d-flex justify-content-around align-items-center align-items-stretch">
        <div class="aiz-topbar-item mr-3">
            <div class="d-flex align-items-center">
                <a class="btn btn-icon btn-circle btn-light" href="{{ route('home') }}" target="_blank" title="{{ translate('Browse Website') }}">
                    <i class="las la-globe"></i>
                </a>
            </div>
        </div>

        @php
            if(Session::has('locale')){
                $locale = Session::get('locale', Config::get('app.locale'));
            } else {
                $locale = env('DEFAULT_LANGUAGE');
            }
        @endphp
        <div class="aiz-topbar-item ml-2">
            <div class="align-items-stretch d-flex dropdown" id="lang-change">
                <a class="dropdown-toggle no-arrow" data-toggle="dropdown" href="javascript:void(0);" role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="btn btn-icon">
                        <img src="{{ static_asset('assets/img/flags/'.$locale.'.png') }}" height="11">
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-right dropdown-menu-animated dropdown-menu-xs">
                    @foreach (\App\Models\Language::where('status', 1)->get() as $language)
                        <li>
                            <a href="javascript:void(0)" data-flag="{{ $language->code }}" class="dropdown-item @if($locale == $language->code) active @endif">
                                <img src="{{ static_asset('assets/img/flags/'.$language->code.'.png') }}" class="mr-2">
                                <span class="language">{{ $language->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="aiz-topbar-item ml-2">
            <div class="align-items-stretch d-flex dropdown">
                <a class="dropdown-toggle no-arrow text-dark" data-toggle="dropdown" href="javascript:void(0);" role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="d-flex align-items-center">
                        <span class="avatar avatar-sm mr-md-2">
                            <img src="{{ uploaded_asset(Auth::user()->avatar_original) }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                        </span>
                        <span class="d-none d-md-block">
                            <span class="d-block fw-500">{{ Auth::user()->name }}</span>
                            <span class="d-block small opacity-60">{{ $portalTitle }}</span>
                        </span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated dropdown-menu-md">
                    <a href="{{ route('b2b.company.show') }}" class="dropdown-item">
                        <i class="las la-building"></i>
                        <span>{{ translate('My Company') }}</span>
                    </a>
                    <a href="{{ route('logout') }}" class="dropdown-item">
                        <i class="las la-sign-out-alt"></i>
                        <span>{{ translate('Logout') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
