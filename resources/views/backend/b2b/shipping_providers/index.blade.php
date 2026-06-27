@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Shipping Providers') }}</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row gutters-10 align-items-center">
                <div class="col-lg-8">
                    <h5 class="mb-1">{{ translate('Admin Integration Workspace') }}</h5>
                    <p class="text-muted mb-0">{{ translate('Provider settings, webhook URLs, secrets, documentation, events, and health monitoring are all visible directly in the admin UX.') }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex flex-wrap justify-content-lg-end">
                        <a href="{{ route('admin.b2b.freight-forwarders.index') }}" class="btn btn-soft-primary btn-sm mr-2 mb-2">{{ translate('Freight Forwarders') }}</a>
                        <a href="{{ route('admin.b2b.shipping-quotes.index') }}" class="btn btn-soft-warning btn-sm mr-2 mb-2">{{ translate('Shipping Quotes') }}</a>
                        <a href="{{ route('admin.b2b.logistics-charge-settings.index') }}" class="btn btn-soft-info btn-sm mr-2 mb-2">{{ translate('Logistics Charges') }}</a>
                        <a href="{{ route('admin.b2b.shipments.index') }}" class="btn btn-soft-success btn-sm mb-2">{{ translate('Shipments') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">{{ translate('Add Provider') }}</div>
        <div class="card-body">
            <form action="{{ route('admin.b2b.shipping-providers.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Name') }}</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('Mode') }}</label>
                        <select class="form-control aiz-selectpicker js-provider-mode" name="transport_mode" required>
                            @foreach ($transportModes as $mode)
                                <option value="{{ $mode }}">{{ ucwords(str_replace('_', ' ', $mode)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('Provider Type') }}</label>
                        <select class="form-control aiz-selectpicker" name="provider_type" required>
                            @foreach ($providerTypes as $providerType)
                                <option value="{{ $providerType }}">{{ ucfirst($providerType) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>{{ translate('API Driver') }}</label>
                        <select class="form-control aiz-selectpicker js-provider-driver" name="api_driver">
                            <option value="">{{ translate('Manual / None') }}</option>
                            @foreach ($apiDrivers as $apiDriver)
                                <option value="{{ $apiDriver }}">{{ $driverLabels[$apiDriver] ?? strtoupper($apiDriver) }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1 js-driver-help">{{ translate('API Driver options automatically change based on the selected mode.') }}</small>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('API Base URL') }}</label>
                        <input type="text" class="form-control" name="api_base_url">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Website') }}</label>
                        <input type="text" class="form-control" name="website">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Email') }}</label>
                        <input type="email" class="form-control" name="contact_email">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Phone') }}</label>
                        <input type="text" class="form-control" name="contact_phone">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Account Number') }}</label>
                        <input type="text" class="form-control" name="account_number">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('API Key') }}</label>
                        <input type="text" class="form-control" name="api_key">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('API Secret') }}</label>
                        <input type="text" class="form-control" name="api_secret">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Webhook Secret') }}</label>
                        <input type="text" class="form-control" name="webhook_secret">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Supported Countries') }}</label>
                        <textarea class="form-control" name="supported_countries" rows="2" placeholder="BD, CN, US"></textarea>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Supported Services') }}</label>
                        <textarea class="form-control" name="supported_services" rows="2" placeholder="express, economy"></textarea>
                        <small class="text-muted d-block mt-1 js-service-hint">{{ translate('Examples: express, economy') }}</small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Default Shipping Cost') }}</label>
                        <input type="number" step="0.01" class="form-control" name="default_shipping_cost" value="0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Default Insurance') }}</label>
                        <input type="number" step="0.01" class="form-control" name="default_insurance_amount" value="0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Default Customs Estimate') }}</label>
                        <input type="number" step="0.01" class="form-control" name="default_customs_estimate" value="0">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ translate('Notes') }}</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                    <div class="col-md-12 form-group">
                        <label class="aiz-checkbox mr-4">
                            <input type="checkbox" name="is_test_mode" value="1" checked>
                            <span class="aiz-square-check"></span>
                            <span>{{ translate('Test Mode') }}</span>
                        </label>
                        <label class="aiz-checkbox mr-4">
                            <input type="checkbox" name="is_verified" value="1">
                            <span class="aiz-square-check"></span>
                            <span>{{ translate('Verified') }}</span>
                        </label>
                        <label class="aiz-checkbox">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span class="aiz-square-check"></span>
                            <span>{{ translate('Active') }}</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ translate('Create Provider') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @forelse ($providers as $provider)
                <div class="border rounded p-3 mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">{{ $provider->name }}</h5>
                            <div class="small text-muted">{{ ucfirst($provider->provider_type ?: 'manual') }} / {{ $provider->api_driver ? strtoupper($provider->api_driver) : '-' }}</div>
                            <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $provider->transport_mode)) }} / {{ $provider->contact_email ?: '-' }}</div>
                            <div class="small text-muted">{{ translate('Default Cost') }}: {{ number_format((float) $provider->default_shipping_cost + (float) $provider->default_insurance_amount + (float) $provider->default_customs_estimate, 2) }} {{ get_system_default_currency()->code }}</div>
                        </div>
                        <div class="text-lg-right">
                            <span class="badge badge-inline badge-{{ $provider->is_verified ? 'success' : 'secondary' }}">{{ $provider->is_verified ? translate('Verified') : translate('Standard') }}</span>
                            <span class="badge badge-inline badge-{{ $provider->is_active ? 'info' : 'danger' }}">{{ $provider->is_active ? translate('Active') : translate('Inactive') }}</span>
                            <span class="badge badge-inline badge-{{ $provider->is_test_mode ? 'warning' : 'dark' }}">{{ $provider->is_test_mode ? translate('Test') : translate('Live') }}</span>
                            <form action="{{ route('admin.b2b.shipping-providers.test', $provider->id) }}" method="POST" class="d-inline-block ml-2">
                                @csrf
                                <button class="btn btn-soft-primary btn-sm" type="submit">{{ translate('Test Connection') }}</button>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="bg-light rounded p-3 h-100">
                                <h6 class="mb-3">{{ translate('Settings') }}</h6>
                                <form action="{{ route('admin.b2b.shipping-providers.update', $provider->id) }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Name') }}</label>
                                            <input type="text" class="form-control" name="name" value="{{ $provider->name }}" required>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Mode') }}</label>
                                            <select class="form-control aiz-selectpicker js-provider-mode" name="transport_mode" required>
                                                @foreach ($transportModes as $mode)
                                                    <option value="{{ $mode }}" @selected($provider->transport_mode === $mode)>{{ ucwords(str_replace('_', ' ', $mode)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Provider Type') }}</label>
                                            <select class="form-control aiz-selectpicker" name="provider_type" required>
                                                @foreach ($providerTypes as $providerType)
                                                    <option value="{{ $providerType }}" @selected($provider->provider_type === $providerType)>{{ ucfirst($providerType) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('API Driver') }}</label>
                                            <select class="form-control aiz-selectpicker js-provider-driver" name="api_driver">
                                                <option value="">{{ translate('Manual / None') }}</option>
                                                @foreach ($apiDrivers as $apiDriver)
                                                    <option value="{{ $apiDriver }}" @selected($provider->api_driver === $apiDriver)>{{ $driverLabels[$apiDriver] ?? strtoupper($apiDriver) }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted d-block mt-1 js-driver-help">{{ translate('Mode-based drivers include sea freight forwarders like Maersk, MSC, CMA CGM, Hapag-Lloyd, COSCO, Evergreen, ONE, DP World, Freightos, and Flexport.') }}</small>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('API Base URL') }}</label>
                                            <input type="text" class="form-control" name="api_base_url" value="{{ $provider->api_base_url }}">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Website') }}</label>
                                            <input type="text" class="form-control" name="website" value="{{ $provider->website }}">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Email') }}</label>
                                            <input type="email" class="form-control" name="contact_email" value="{{ $provider->contact_email }}">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Phone') }}</label>
                                            <input type="text" class="form-control" name="contact_phone" value="{{ $provider->contact_phone }}">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Account Number') }}</label>
                                            <input type="text" class="form-control" name="account_number" placeholder="{{ translate('Leave blank to keep unchanged') }}">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('API Key') }}</label>
                                            <input type="text" class="form-control" name="api_key" placeholder="{{ translate('Leave blank to keep unchanged') }}">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('API Secret') }}</label>
                                            <input type="text" class="form-control" name="api_secret" placeholder="{{ translate('Leave blank to keep unchanged') }}">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Webhook Secret') }}</label>
                                            <input type="text" class="form-control" name="webhook_secret" placeholder="{{ translate('Leave blank to keep unchanged') }}">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Supported Countries') }}</label>
                                            <textarea class="form-control" name="supported_countries" rows="2">{{ is_array($provider->supported_countries) ? implode(', ', $provider->supported_countries) : '' }}</textarea>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Supported Services') }}</label>
                                            <textarea class="form-control" name="supported_services" rows="2">{{ is_array($provider->supported_services) ? implode(', ', $provider->supported_services) : '' }}</textarea>
                                            <small class="text-muted d-block mt-1 js-service-hint">{{ translate('Examples: express, economy') }}</small>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label>{{ translate('Default Shipping Cost') }}</label>
                                            <input type="number" step="0.01" class="form-control" name="default_shipping_cost" value="{{ $provider->default_shipping_cost ?? 0 }}">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label>{{ translate('Default Insurance') }}</label>
                                            <input type="number" step="0.01" class="form-control" name="default_insurance_amount" value="{{ $provider->default_insurance_amount ?? 0 }}">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label>{{ translate('Default Customs Estimate') }}</label>
                                            <input type="number" step="0.01" class="form-control" name="default_customs_estimate" value="{{ $provider->default_customs_estimate ?? 0 }}">
                                        </div>
                                        <div class="col-md-12 form-group">
                                            <label>{{ translate('Notes') }}</label>
                                            <textarea class="form-control" name="notes" rows="2">{{ $provider->notes }}</textarea>
                                        </div>
                                        <div class="col-md-12 form-group">
                                            <label class="aiz-checkbox mr-4">
                                                <input type="checkbox" name="is_test_mode" value="1" @checked($provider->is_test_mode)>
                                                <span class="aiz-square-check"></span>
                                                <span>{{ translate('Test Mode') }}</span>
                                            </label>
                                            <label class="aiz-checkbox mr-4">
                                                <input type="checkbox" name="is_verified" value="1" @checked($provider->is_verified)>
                                                <span class="aiz-square-check"></span>
                                                <span>{{ translate('Verified') }}</span>
                                            </label>
                                            <label class="aiz-checkbox">
                                                <input type="checkbox" name="is_active" value="1" @checked($provider->is_active)>
                                                <span class="aiz-square-check"></span>
                                                <span>{{ translate('Active') }}</span>
                                            </label>
                                        </div>
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary btn-sm">{{ translate('Save Provider') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-xl-6 mt-3 mt-xl-0">
                            <div class="bg-light rounded p-3 h-100">
                                <h6 class="mb-3">{{ translate('Integration') }}</h6>
                                @include('backend.b2b.partials.integration_panel', [
                                    'model' => $provider,
                                    'integration' => $integrationPayloads[$provider->id] ?? [],
                                    'panelId' => 'provider-' . $provider->id,
                                    'testConnectionRoute' => route('admin.b2b.shipping-providers.test', $provider->id),
                                    'testAuthenticationRoute' => route('admin.b2b.shipping-providers.test-authentication', $provider->id),
                                    'verifyCredentialsRoute' => route('admin.b2b.shipping-providers.verify-credentials', $provider->id),
                                    'testWebhookRoute' => route('admin.b2b.shipping-providers.test-webhook', $provider->id),
                                    'sendSampleWebhookRoute' => route('admin.b2b.shipping-providers.send-sample-webhook', $provider->id),
                                    'regenerateSecretRoute' => route('admin.b2b.shipping-providers.regenerate-secret', $provider->id),
                                    'updateEventsRoute' => route('admin.b2b.shipping-providers.integration-events', $provider->id),
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4">{{ translate('No shipping providers found') }}</div>
            @endforelse
            <div class="aiz-pagination mt-4">{{ $providers->links() }}</div>
        </div>
    </div>
@endsection

@push('script')
<script>
    (function () {
        var modeDriverMap = @json($modeDriverMap);
        var driverLabels = @json($driverLabels);
        var driverCapabilities = @json($driverCapabilities);

        function prettify(value) {
            return (value || '').replace(/_/g, ' ').replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
        }

        function refreshPickers(scope) {
            var pickers = scope.querySelectorAll('.aiz-selectpicker');

            pickers.forEach(function (picker) {
                if (window.jQuery && window.jQuery.fn.selectpicker) {
                    window.jQuery(picker).selectpicker('refresh');
                }
            });
        }

        function updateDriverOptions(form) {
            var modeSelect = form.querySelector('.js-provider-mode');
            var driverSelect = form.querySelector('.js-provider-driver');
            var helpText = form.querySelector('.js-driver-help');
            var serviceHint = form.querySelector('.js-service-hint');

            if (!modeSelect || !driverSelect) {
                return;
            }

            var selectedMode = modeSelect.value;
            var allowedDrivers = modeDriverMap[selectedMode] || Object.keys(driverLabels);
            var currentValue = driverSelect.value;

            Array.prototype.forEach.call(driverSelect.options, function (option) {
                if (!option.value) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                var allowed = allowedDrivers.indexOf(option.value) !== -1;
                option.hidden = !allowed;
                option.disabled = !allowed;
            });

            if (currentValue && allowedDrivers.indexOf(currentValue) === -1) {
                driverSelect.value = allowedDrivers[0] || '';
            }

            var capabilities = driverCapabilities[driverSelect.value] || [];

            if (helpText) {
                helpText.textContent = selectedMode === 'sea_freight'
                    ? 'Sea freight mode supports Maersk, MSC, CMA CGM, Hapag-Lloyd, COSCO, Evergreen, ONE, DP World, Freightos, Flexport, and Custom.'
                    : 'API Driver options automatically change based on the selected mode.';
            }

            if (serviceHint) {
                serviceHint.textContent = capabilities.length
                    ? 'Supported service examples: ' + capabilities.map(prettify).join(', ')
                    : 'Examples: express, economy';
            }

            refreshPickers(form);
        }

        document.querySelectorAll('form').forEach(function (form) {
            if (!form.querySelector('.js-provider-mode') || !form.querySelector('.js-provider-driver')) {
                return;
            }

            updateDriverOptions(form);

            form.querySelector('.js-provider-mode').addEventListener('change', function () {
                updateDriverOptions(form);
            });

            form.querySelector('.js-provider-driver').addEventListener('change', function () {
                updateDriverOptions(form);
            });
        });
    })();
</script>
@endpush
