@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <h1 class="h3">{{ translate('Create Shipping Quote') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $purchaseOrder ? route('seller.b2b.shipping-quotes.purchase-orders.store', $purchaseOrder->id) : route('seller.b2b.shipping-quotes.sample-orders.store', $sampleOrder->id) }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Shipping Provider') }}</label>
                        <select class="form-control aiz-selectpicker js-shipping-provider" name="shipping_provider_id" data-live-search="true">
                            <option value="">{{ translate('Direct Supplier Logistics') }}</option>
                            @foreach ($providers as $provider)
                                <option
                                    value="{{ $provider->id }}"
                                    data-default-shipping-cost="{{ $provider->default_shipping_cost ?? 0 }}"
                                    data-default-insurance="{{ $provider->default_insurance_amount ?? 0 }}"
                                    data-default-customs="{{ $provider->default_customs_estimate ?? 0 }}"
                                >{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Transport Mode') }}</label>
                        <select class="form-control aiz-selectpicker" name="transport_mode" required>
                            @foreach ($transportModes as $mode)
                                <option value="{{ $mode }}">{{ ucwords(str_replace('_', ' ', $mode)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Incoterm') }}</label>
                        <select class="form-control aiz-selectpicker" name="incoterm" required>
                            @foreach ($incoterms as $incoterm)
                                <option value="{{ $incoterm }}">{{ $incoterm }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Origin Country') }}</label>
                        <input type="text" class="form-control" name="origin_country" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Destination Country') }}</label>
                        <input type="text" class="form-control" name="destination_country" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('Estimated Days') }}</label>
                        <input type="number" class="form-control" name="estimated_days">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('Currency') }}</label>
                        <input type="text" class="form-control" name="currency" value="{{ get_system_default_currency()->code }}" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('Shipping Cost') }}</label>
                        <input type="number" step="0.01" class="form-control js-shipping-cost" name="shipping_cost">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Insurance') }}</label>
                        <input type="number" step="0.01" class="form-control js-insurance-cost" name="insurance_amount" value="0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Customs Estimate') }}</label>
                        <input type="number" step="0.01" class="form-control js-customs-cost" name="customs_estimate" value="0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Buyer Pay Total') }}</label>
                        <input type="text" class="form-control js-total-logistics-cost" value="0.00" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Kaniz Global Trade Shipping Service Charge') }}</label>
                        <input type="text" class="form-control js-site-charge" value="0.00" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Charge Formula') }}</label>
                        <input
                            type="text"
                            class="form-control"
                            value="{{ $shippingChargeSettings['enabled'] ? ($shippingChargeSettings['type'] === 'percentage' ? ($shippingChargeSettings['percent'] . '%') : number_format($shippingChargeSettings['fixed'], 2) . ' ' . get_system_default_currency()->code) : translate('Disabled') }}"
                            readonly
                        >
                    </div>
                    <div class="col-md-12 form-group">
                        <div class="alert alert-soft-info mb-0">
                            <div><strong>{{ translate('Flow') }}:</strong> {{ translate('Shipping provider quote + Kaniz Global Trade shipping service charge = buyer payment.') }}</div>
                            <div class="mt-1">
                                {{ translate('Platform profit is equal to the shipping service charge amount.') }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ translate('Notes') }}</label>
                        <textarea class="form-control" name="notes" rows="4"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ translate('Create Shipping Quote') }}</button>
            </form>
        </div>
    </div>
@endsection

@push('script')
<script>
    (function () {
        var providerSelect = document.querySelector('.js-shipping-provider');
        var shippingInput = document.querySelector('.js-shipping-cost');
        var insuranceInput = document.querySelector('.js-insurance-cost');
        var customsInput = document.querySelector('.js-customs-cost');
        var siteChargeInput = document.querySelector('.js-site-charge');
        var totalInput = document.querySelector('.js-total-logistics-cost');
        var chargeSettings = @json($shippingChargeSettings);

        if (!providerSelect || !shippingInput || !insuranceInput || !customsInput || !totalInput || !siteChargeInput) {
            return;
        }

        function numberValue(input) {
            var value = parseFloat(input.value || 0);

            return isNaN(value) ? 0 : value;
        }

        function siteCharge(subtotal) {
            if (!chargeSettings.enabled) {
                return 0;
            }

            if (chargeSettings.type === 'percentage') {
                return subtotal * (parseFloat(chargeSettings.percent || 0) / 100);
            }

            return parseFloat(chargeSettings.fixed || 0);
        }

        function syncTotal() {
            var subtotal = numberValue(shippingInput) + numberValue(insuranceInput) + numberValue(customsInput);
            var charge = siteCharge(subtotal);
            siteChargeInput.value = charge.toFixed(2);
            totalInput.value = (subtotal + charge).toFixed(2);
        }

        function applyProviderDefaults() {
            var option = providerSelect.options[providerSelect.selectedIndex];

            if (!option || !option.value) {
                syncTotal();
                return;
            }

            shippingInput.value = option.getAttribute('data-default-shipping-cost') || shippingInput.value || 0;
            insuranceInput.value = option.getAttribute('data-default-insurance') || insuranceInput.value || 0;
            customsInput.value = option.getAttribute('data-default-customs') || customsInput.value || 0;
            syncTotal();
        }

        providerSelect.addEventListener('change', applyProviderDefaults);
        [shippingInput, insuranceInput, customsInput].forEach(function (input) {
            input.addEventListener('input', syncTotal);
        });

        syncTotal();
    })();
</script>
@endpush
