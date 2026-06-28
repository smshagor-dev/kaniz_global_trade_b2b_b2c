<!doctype html>
@if(\App\Models\Language::where('code', Session::get('locale', Config::get('app.locale')))->first()->rtl == 1)
<html dir="rtl" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@else
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@endif
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ getBaseURL() }}">
    <meta name="file-base-url" content="{{ getFileBaseURL() }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" href="{{ uploaded_asset(get_setting('site_icon')) }}">
    <title>{{ get_setting('website_name').' | '.get_setting('site_motto') }}</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">
    <link rel="stylesheet" href="{{ static_asset('assets/css/vendors.css') }}">
    @if(\App\Models\Language::where('code', Session::get('locale', Config::get('app.locale')))->first()->rtl == 1)
    <link rel="stylesheet" href="{{ static_asset('assets/css/bootstrap-rtl.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ static_asset('assets/css/aiz-seller.css') }}">
    <link rel="stylesheet" href="{{ static_asset('assets/css/custom-style.css') }}">
    <link rel="stylesheet" href="{{ static_asset('assets/css/seller-custom-style.css') }}">

    @php
        $portal = $portal ?? 'buyer';
        $portalTitle = $portal === 'supplier' ? translate('Supplier Portal') : translate('Buyer Portal');
        $portalSubtitle = $portal === 'supplier'
            ? translate('Enterprise trade workspace')
            : translate('Procurement and sourcing workspace');
    @endphp

    <style>
        body {
            font-size: 12px;
            font-family: {!! !empty(get_setting('system_font_family')) ? get_setting('system_font_family') : "'Public Sans', sans-serif" !!}, sans-serif;
            background: #f2f3f8;
        }
        .b2b-sidebar-wrap .aiz-sidebar {
            width: 265px;
        }
        .b2b-sidebar-wrap .aiz-side-nav-logo-wrap {
            padding: 18px 20px 8px;
            border-bottom: 1px solid #edf0f5;
        }
        .b2b-sidebar-wrap .b2b-portal-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            background: rgba(37, 99, 235, .08);
            color: #2563eb;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .b2b-sidebar-wrap .aiz-side-nav-text {
            font-size: 13px;
        }
        .b2b-portal-topbar-note {
            font-size: 12px;
            color: #6b7280;
        }
        .b2b-portal-pagehead {
            background: #fff;
            border: 1px solid #e6ebf1;
            border-radius: 4px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .b2b-portal-pagehead h1 {
            margin-bottom: 4px;
        }
        .b2b-kpi-card,
        .b2b-section-card {
            background: #fff;
            border: 1px solid #e6ebf1;
            border-radius: 4px;
            box-shadow: none;
        }
        .b2b-kpi-card {
            padding: 18px;
            height: 100%;
        }
        .b2b-kpi-card .value {
            font-size: 28px;
            line-height: 1.1;
            font-weight: 700;
            color: #223548;
        }
        .b2b-section-card {
            padding: 20px;
            height: 100%;
        }
        .b2b-pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            background: #f3f6f9;
            color: #52606d;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .b2b-timeline-item {
            position: relative;
            padding-left: 18px;
            margin-bottom: 16px;
        }
        .b2b-timeline-item::before {
            content: "";
            position: absolute;
            left: 0;
            top: 6px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #f59e0b;
        }
        .b2b-timeline-item::after {
            content: "";
            position: absolute;
            left: 3px;
            top: 18px;
            bottom: -13px;
            width: 1px;
            background: #dbe2ea;
        }
        .b2b-timeline-item:last-child::after {
            display: none;
        }
        .b2b-main-wrap .aiz-main-content {
            background: #f2f3f8;
        }
    </style>

    <script>
        var AIZ = AIZ || {};
        AIZ.local = {
            nothing_selected: '{!! translate('Nothing selected', null, true) !!}',
            nothing_found: '{!! translate('Nothing found', null, true) !!}',
            choose_file: '{{ translate('Choose file') }}',
            file_selected: '{{ translate('File selected') }}',
            files_selected: '{{ translate('Files selected') }}',
            add_more_files: '{{ translate('Add more files') }}',
            adding_more_files: '{{ translate('Adding more files') }}',
            drop_files_here_paste_or: '{{ translate('Drop files here, paste or') }}',
            browse: '{{ translate('Browse') }}',
            upload_complete: '{{ translate('Upload complete') }}',
            upload_paused: '{{ translate('Upload paused') }}',
            resume_upload: '{{ translate('Resume upload') }}',
            pause_upload: '{{ translate('Pause upload') }}',
            retry_upload: '{{ translate('Retry upload') }}',
            cancel_upload: '{{ translate('Cancel upload') }}',
            uploading: '{{ translate('Uploading') }}',
            processing: '{{ translate('Processing') }}',
            complete: '{{ translate('Complete') }}',
            file: '{{ translate('File') }}',
            files: '{{ translate('Files') }}',
        }
    </script>
</head>
<body>
    <div class="aiz-main-wrapper b2b-main-wrap">
        @include('b2b.layouts.partials.sidebar')
        <div class="aiz-content-wrapper">
            @include('b2b.layouts.partials.topnav', compact('portalTitle', 'portalSubtitle'))
            <div class="aiz-main-content">
                <div class="px-15px px-lg-25px pt-3">
                    @yield('panel_content')
                </div>
                <div class="bg-white text-center py-3 px-15px px-lg-25px mt-auto border-sm-top">
                    <div class="d-flex justify-content-center flex-wrap">
                        <a href="{{ route('home') }}" target="_blank" class="btn btn-link p-0 text-decoration-none mr-3 fw-700 fs-12 hov-text-info">
                            {{ translate('Browse Website') }}
                        </a>
                        <a href="{{ route('b2b.suppliers.index') }}" target="_blank" class="btn btn-link p-0 text-decoration-none mr-3 fw-700 fs-12 hov-text-info">
                            {{ translate('Suppliers') }}
                        </a>
                        <a href="{{ route('buyer.portal') }}" class="btn btn-link p-0 text-decoration-none mr-3 fw-700 fs-12 hov-text-info">
                            {{ translate('Buyer Portal') }}
                        </a>
                    </div>
                    <p class="mb-0 mt-2 fs-11">&copy; {{ get_setting('site_name') }} v{{ get_setting('current_version') }}</p>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ static_asset('assets/js/vendors.js') }}"></script>
    <script src="{{ static_asset('assets/js/aiz-core.js') }}"></script>
    @yield('script')

    <script type="text/javascript">
        @foreach (session('flash_notification', collect())->toArray() as $message)
            AIZ.plugins.notify('{{ $message['level'] }}', '{{ $message['message'] }}');
        @endforeach

        if ($('#lang-change').length > 0) {
            $('#lang-change .dropdown-menu a').each(function() {
                $(this).on('click', function(e){
                    e.preventDefault();
                    var $this = $(this);
                    var locale = $this.data('flag');
                    $.post('{{ route('language.change') }}',{_token:'{{ csrf_token() }}', locale:locale}, function(data){
                        location.reload();
                    });
                });
            });
        }

        function menuSearch(){
            var filter, item;
            filter = $("#menu-search").val().toUpperCase();
            items = $("#main-menu").find("a");
            items = items.filter(function(i,item){
                if($(item).find(".aiz-side-nav-text")[0].innerText.toUpperCase().indexOf(filter) > -1 && $(item).attr('href') !== '#'){
                    return item;
                }
            });

            if(filter !== ''){
                $("#main-menu").addClass('d-none');
                $("#search-menu").html('')
                if(items.length > 0){
                    for (i = 0; i < items.length; i++) {
                        const text = $(items[i]).find(".aiz-side-nav-text")[0].innerText;
                        const link = $(items[i]).attr('href');
                        $("#search-menu").append(`<li class="aiz-side-nav-item"><a href="${link}" class="aiz-side-nav-link"><i class="las la-ellipsis-h aiz-side-nav-icon"></i><span>${text}</span></a></li>`);
                    }
                }else{
                    $("#search-menu").html(`<li class="aiz-side-nav-item"><span class="text-center text-muted d-block">{{ translate('Nothing Found') }}</span></li>`);
                }
            }else{
                $("#main-menu").removeClass('d-none');
                $("#search-menu").html('')
            }
        }
    </script>
</body>
</html>
