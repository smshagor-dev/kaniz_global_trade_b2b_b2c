@extends('backend.layouts.app')

@section('content')


    <div class="row">
        <div class="col-lg-8 mx-auto">


            <!-- General Settings -->
            <div class="card">
                <div class="card-header">
                    <h6 class="fw-600 mb-0">{{ translate('Banners & Sliders') }}</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('business_settings.update') }}" method="POST">
                        @csrf

                        <!-- Flash Deal Page Banner - Large -->
                        <div class="form-group mb-4">
                            <label class="col-from-label">{{ translate('Flash Deal Page Banner - Large') }}</label>
                            <div class="add-product-page-content">
                                <div class="img-upload-container">
                                    <div class="input-group file-upload-input border border-dashed border-gray-400 rounded-1 w-120px h-120px d-flex align-items-center justify-content-center"
                                        data-toggle="aizuploader" data-type="image" data-multiple="false">
                                        <div
                                            class="form-control p-0 border-0 d-flex align-items-center justify-content-center">
                                            <img src="{{ static_asset('assets/img/plus-lg.svg') }}"
                                                class="w-40px h-40px w-md-64px h-md-64px" alt="generate Icon">
                                        </div>
                                        <input type="hidden" name="types[]" value="flash_deal_banner">
                                        <input type="hidden" name="flash_deal_banner" class="selected-files"
                                            value="{{ get_setting('flash_deal_banner') }}">
                                    </div>
                                    <div class="file-preview box sm"></div>
                                </div>
                            </div>

                            <small
                                class="text-muted">{{ translate('Will be shown in large device. Minimum dimensions required: 1370px width X 242px height.') }}</small>

                        </div>
                        <!-- Flash Deal Page Banner - Small -->
                        <div class="form-group mb-4">
                            <label class="col-from-label">{{ translate('Flash Deal Page Banner - Small') }}</label>
                            <div class="add-product-page-content">
                                <div class="img-upload-container">
                                    <div class="input-group file-upload-input border border-dashed border-gray-400 rounded-1 w-120px h-120px d-flex align-items-center justify-content-center"
                                        data-toggle="aizuploader" data-type="image" data-multiple="false">
                                        <div
                                            class="form-control p-0 border-0 d-flex align-items-center justify-content-center">
                                            <img src="{{ static_asset('assets/img/plus-lg.svg') }}"
                                                class="w-40px h-40px w-md-64px h-md-64px" alt="generate Icon">
                                        </div>
                                        <input type="hidden" name="types[]" value="flash_deal_banner_small">
                                        <input type="hidden" name="flash_deal_banner_small" class="selected-files"
                                            value="{{ get_setting('flash_deal_banner_small') }}">
                                    </div>
                                    <div class="file-preview box sm"></div>
                                </div>
                            </div>
                            <small
                                class="text-muted">{{ translate('Will be shown in small device. Minimum dimensions required: 400px width X 184px height.') }}</small>
                        </div>
                        <!-- Update Button -->
                        <div class="mt-4 text-right">
                            <button type="submit"
                                class="btn btn-success w-230px btn-md rounded-2 fs-14 fw-700 shadow-success">{{ translate('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script type="text/javascript">
        $('select[name="image_watermark_type"]').on('change', function () {
            let val = $(this).val();
            if (val == 'image') {
                $('#watermark_image').removeClass('d-none');
                $('#watermark_text').addClass('d-none');
            } else {
                $('#watermark_text').removeClass('d-none');
                $('#watermark_image').addClass('d-none');
            }
        });
    </script>
@endsection