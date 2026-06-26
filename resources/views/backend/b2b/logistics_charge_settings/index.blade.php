@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Global B2B Config') }}</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <p class="text-muted mb-0">
                {{ translate('Configure global B2B shipping charges, platform fees, sample processing fees, inspection service charges, and trade document fees from one place.') }}
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <form action="{{ route('admin.b2b.logistics-charge-settings.update') }}" method="POST">
                @csrf
                <input type="hidden" name="config_section" value="shipping">
                <div class="card mb-4 h-100">
                    <div class="card-header">{{ translate('Shipping Site Charge') }}</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="b2b_shipping_site_charge_enabled" value="1" @checked($shippingSettings['enabled'])>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Enable B2B shipping service charge') }}</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Charge Type') }}</label>
                            <select name="b2b_shipping_site_charge_type" class="form-control aiz-selectpicker js-charge-type" data-charge-group="shipping">
                                @foreach ($shippingChargeTypes as $chargeType)
                                    <option value="{{ $chargeType }}" @selected($shippingSettings['type'] === $chargeType)>
                                        {{ translate(ucfirst($chargeType)) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ translate('Choose either a fixed amount or a percentage-based service charge.') }}</small>
                        </div>
                        <div
                            class="form-group js-charge-field"
                            data-charge-group="shipping"
                            data-charge-field="percentage"
                            style="{{ $shippingSettings['type'] === 'percentage' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Percentage Charge') }}</label>
                            <input type="number" step="0.001" min="0" name="b2b_shipping_site_charge_percent" class="form-control" value="{{ $shippingSettings['percent'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Percentage.') }}</small>
                        </div>
                        <div
                            class="form-group mb-3 js-charge-field"
                            data-charge-group="shipping"
                            data-charge-field="fixed"
                            style="{{ $shippingSettings['type'] === 'fixed' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Fixed Charge') }} ({{ $currencyCode }})</label>
                            <input type="number" step="0.01" min="0" name="b2b_shipping_site_charge_fixed" class="form-control" value="{{ $shippingSettings['fixed'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Fixed.') }}</small>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ translate('Save Shipping Settings') }}</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <form action="{{ route('admin.b2b.logistics-charge-settings.update') }}" method="POST">
                @csrf
                <input type="hidden" name="config_section" value="order">
                <div class="card mb-4 h-100">
                    <div class="card-header">{{ translate('Order Platform Service Fee') }}</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="b2b_order_platform_fee_enabled" value="1" @checked($orderFeeSettings['enabled'])>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Enable B2B order platform service fee') }}</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Charge Type') }}</label>
                            <select name="b2b_order_platform_fee_type" class="form-control aiz-selectpicker js-charge-type" data-charge-group="order">
                                @foreach ($orderChargeTypes as $chargeType)
                                    <option value="{{ $chargeType }}" @selected($orderFeeSettings['type'] === $chargeType)>
                                        {{ translate(ucfirst($chargeType)) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ translate('This fee is deducted from supplier payout after buyer payment is received.') }}</small>
                        </div>
                        <div
                            class="form-group js-charge-field"
                            data-charge-group="order"
                            data-charge-field="percentage"
                            style="{{ $orderFeeSettings['type'] === 'percentage' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Percentage Fee') }}</label>
                            <input type="number" step="0.001" min="0" name="b2b_order_platform_fee_percent" class="form-control" value="{{ $orderFeeSettings['percent'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Percentage.') }}</small>
                        </div>
                        <div
                            class="form-group mb-3 js-charge-field"
                            data-charge-group="order"
                            data-charge-field="fixed"
                            style="{{ $orderFeeSettings['type'] === 'fixed' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Fixed Fee') }} ({{ $currencyCode }})</label>
                            <input type="number" step="0.01" min="0" name="b2b_order_platform_fee_fixed" class="form-control" value="{{ $orderFeeSettings['fixed'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Fixed.') }}</small>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ translate('Save Order Fee Settings') }}</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <form action="{{ route('admin.b2b.logistics-charge-settings.update') }}" method="POST">
                @csrf
                <input type="hidden" name="config_section" value="escrow">
                <div class="card mb-4 h-100">
                    <div class="card-header">{{ translate('Escrow Fee') }}</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="b2b_escrow_fee_enabled" value="1" @checked($escrowFeeSettings['enabled'])>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Enable B2B escrow fee') }}</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Charge Type') }}</label>
                            <select name="b2b_escrow_fee_type" class="form-control aiz-selectpicker js-charge-type" data-charge-group="escrow">
                                @foreach ($escrowChargeTypes as $chargeType)
                                    <option value="{{ $chargeType }}" @selected($escrowFeeSettings['type'] === $chargeType)>
                                        {{ translate(ucfirst($chargeType)) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ translate('This fee is added on top of the buyer payment when escrow is used.') }}</small>
                        </div>
                        <div
                            class="form-group js-charge-field"
                            data-charge-group="escrow"
                            data-charge-field="percentage"
                            style="{{ $escrowFeeSettings['type'] === 'percentage' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Percentage Fee') }}</label>
                            <input type="number" step="0.001" min="0" name="b2b_escrow_fee_percent" class="form-control" value="{{ $escrowFeeSettings['percent'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Percentage.') }}</small>
                        </div>
                        <div
                            class="form-group mb-3 js-charge-field"
                            data-charge-group="escrow"
                            data-charge-field="fixed"
                            style="{{ $escrowFeeSettings['type'] === 'fixed' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Fixed Fee') }} ({{ $currencyCode }})</label>
                            <input type="number" step="0.01" min="0" name="b2b_escrow_fee_fixed" class="form-control" value="{{ $escrowFeeSettings['fixed'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Fixed.') }}</small>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ translate('Save Escrow Settings') }}</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <form action="{{ route('admin.b2b.logistics-charge-settings.update') }}" method="POST">
                @csrf
                <input type="hidden" name="config_section" value="sample">
                <div class="card mb-4 h-100">
                    <div class="card-header">{{ translate('Sample Processing Fee') }}</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="b2b_sample_processing_fee_enabled" value="1" @checked($sampleProcessingFeeSettings['enabled'])>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Enable sample processing fee') }}</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Charge Type') }}</label>
                            <select name="b2b_sample_processing_fee_type" class="form-control aiz-selectpicker js-charge-type" data-charge-group="sample">
                                @foreach ($sampleChargeTypes as $chargeType)
                                    <option value="{{ $chargeType }}" @selected($sampleProcessingFeeSettings['type'] === $chargeType)>
                                        {{ translate(ucfirst($chargeType)) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ translate('Choose either a fixed amount or a percentage-based sample processing fee.') }}</small>
                        </div>
                        <div
                            class="form-group js-charge-field"
                            data-charge-group="sample"
                            data-charge-field="percentage"
                            style="{{ $sampleProcessingFeeSettings['type'] === 'percentage' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Percentage Fee') }}</label>
                            <input type="number" step="0.001" min="0" name="b2b_sample_processing_fee_percent" class="form-control" value="{{ $sampleProcessingFeeSettings['percent'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Percentage.') }}</small>
                        </div>
                        <div
                            class="form-group mb-3 js-charge-field"
                            data-charge-group="sample"
                            data-charge-field="fixed"
                            style="{{ $sampleProcessingFeeSettings['type'] === 'fixed' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Fixed Fee') }} ({{ $currencyCode }})</label>
                            <input type="number" step="0.01" min="0" name="b2b_sample_processing_fee_fixed" class="form-control" value="{{ $sampleProcessingFeeSettings['fixed'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Fixed.') }}</small>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ translate('Save Sample Fee Settings') }}</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <form action="{{ route('admin.b2b.logistics-charge-settings.update') }}" method="POST">
                @csrf
                <input type="hidden" name="config_section" value="inspection">
                <div class="card mb-4 h-100">
                    <div class="card-header">{{ translate('Inspection Service Charge') }}</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="b2b_inspection_service_charge_enabled" value="1" @checked($inspectionServiceChargeSettings['enabled'])>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Enable inspection service charge') }}</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Charge Type') }}</label>
                            <select name="b2b_inspection_service_charge_type" class="form-control aiz-selectpicker js-charge-type" data-charge-group="inspection">
                                @foreach ($inspectionChargeTypes as $chargeType)
                                    <option value="{{ $chargeType }}" @selected($inspectionServiceChargeSettings['type'] === $chargeType)>
                                        {{ translate(ucfirst($chargeType)) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ translate('Applied automatically when an inspection fee line is added to a freight quote.') }}</small>
                        </div>
                        <div
                            class="form-group js-charge-field"
                            data-charge-group="inspection"
                            data-charge-field="percentage"
                            style="{{ $inspectionServiceChargeSettings['type'] === 'percentage' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Percentage Charge') }}</label>
                            <input type="number" step="0.001" min="0" name="b2b_inspection_service_charge_percent" class="form-control" value="{{ $inspectionServiceChargeSettings['percent'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Percentage.') }}</small>
                        </div>
                        <div
                            class="form-group mb-3 js-charge-field"
                            data-charge-group="inspection"
                            data-charge-field="fixed"
                            style="{{ $inspectionServiceChargeSettings['type'] === 'fixed' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Fixed Charge') }} ({{ $currencyCode }})</label>
                            <input type="number" step="0.01" min="0" name="b2b_inspection_service_charge_fixed" class="form-control" value="{{ $inspectionServiceChargeSettings['fixed'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Fixed.') }}</small>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ translate('Save Inspection Settings') }}</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <form action="{{ route('admin.b2b.logistics-charge-settings.update') }}" method="POST">
                @csrf
                <input type="hidden" name="config_section" value="trade_document">
                <div class="card mb-4 h-100">
                    <div class="card-header">{{ translate('Trade Document Service Fee') }}</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="b2b_trade_document_fee_enabled" value="1" @checked($tradeDocumentFeeSettings['enabled'])>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Enable trade document service fee') }}</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Charge Type') }}</label>
                            <select name="b2b_trade_document_fee_type" class="form-control aiz-selectpicker js-charge-type" data-charge-group="trade-document">
                                @foreach ($tradeDocumentChargeTypes as $chargeType)
                                    <option value="{{ $chargeType }}" @selected($tradeDocumentFeeSettings['type'] === $chargeType)>
                                        {{ translate(ucfirst($chargeType)) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ translate('Applied to Commercial Invoice, Packing List, Certificate of Origin, and Bill of Lading generation/uploads.') }}</small>
                        </div>
                        <div
                            class="form-group js-charge-field"
                            data-charge-group="trade-document"
                            data-charge-field="percentage"
                            style="{{ $tradeDocumentFeeSettings['type'] === 'percentage' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Percentage Fee') }}</label>
                            <input type="number" step="0.001" min="0" name="b2b_trade_document_fee_percent" class="form-control" value="{{ $tradeDocumentFeeSettings['percent'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Percentage.') }}</small>
                        </div>
                        <div
                            class="form-group mb-3 js-charge-field"
                            data-charge-group="trade-document"
                            data-charge-field="fixed"
                            style="{{ $tradeDocumentFeeSettings['type'] === 'fixed' ? '' : 'display: none;' }}"
                        >
                            <label>{{ translate('Fixed Fee') }} ({{ $currencyCode }})</label>
                            <input type="number" step="0.01" min="0" name="b2b_trade_document_fee_fixed" class="form-control" value="{{ $tradeDocumentFeeSettings['fixed'] }}">
                            <small class="text-muted">{{ translate('Used only when charge type is Fixed.') }}</small>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ translate('Save Trade Document Settings') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            function syncChargeFields(group) {
                var selectedType = $('.js-charge-type[data-charge-group="' + group + '"]').val();

                $('.js-charge-field[data-charge-group="' + group + '"]').each(function () {
                    var $field = $(this);
                    var fieldType = $field.data('charge-field');

                    if (fieldType === selectedType) {
                        $field.show();
                    } else {
                        $field.hide();
                    }
                });
            }

            $(document).on('change', '.js-charge-type', function () {
                syncChargeFields($(this).data('charge-group'));
            });

            $(window).on('load', function () {
                syncChargeFields('shipping');
                syncChargeFields('order');
                syncChargeFields('escrow');
                syncChargeFields('sample');
                syncChargeFields('inspection');
                syncChargeFields('trade-document');
            });

            syncChargeFields('shipping');
            syncChargeFields('order');
            syncChargeFields('escrow');
            syncChargeFields('sample');
            syncChargeFields('inspection');
            syncChargeFields('trade-document');
        })();
    </script>
@endpush
