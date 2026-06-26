@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Freight Forwarders') }}</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row gutters-10 align-items-center">
                <div class="col-lg-8">
                    <h5 class="mb-1">{{ translate('Admin Integration Workspace') }}</h5>
                    <p class="text-muted mb-0">{{ translate('Every freight forwarder now shows settings and integration details directly on the page without hidden tabs or collapses.') }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex flex-wrap justify-content-lg-end">
                        <a href="{{ route('admin.b2b.ports.index') }}" class="btn btn-soft-secondary btn-sm mr-2 mb-2">{{ translate('Ports') }}</a>
                        <a href="{{ route('admin.b2b.freight-quotes.index') }}" class="btn btn-soft-warning btn-sm mr-2 mb-2">{{ translate('Freight Quotes') }}</a>
                        <a href="{{ route('admin.b2b.logistics-charge-settings.index') }}" class="btn btn-soft-dark btn-sm mr-2 mb-2">{{ translate('Logistics Charges') }}</a>
                        <a href="{{ route('admin.b2b.freight-pricing-rules.index') }}" class="btn btn-soft-success btn-sm mr-2 mb-2">{{ translate('Pricing Rules') }}</a>
                        <a href="{{ route('admin.b2b.hs-codes.index') }}" class="btn btn-soft-danger btn-sm mb-2">{{ translate('HS Codes') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">{{ translate('Add Freight Forwarder') }}</div>
        <div class="card-body">
            <form action="{{ route('admin.b2b.freight-forwarders.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3 form-group"><label>{{ translate('Name') }}</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Driver') }}</label><select name="driver" class="form-control aiz-selectpicker">@foreach (\App\Models\B2BFreightForwarder::DRIVERS as $driver)<option value="{{ $driver }}">{{ strtoupper($driver) }}</option>@endforeach</select></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Provider Type') }}</label><input type="text" name="provider_type" class="form-control" value="ocean_carrier"></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Environment') }}</label><input type="text" name="environment" class="form-control" value="sandbox"></div>
                    <div class="col-md-3 form-group"><label>{{ translate('API Base URL') }}</label><input type="text" name="api_base_url" class="form-control"></div>
                    <div class="col-md-3 form-group"><label>{{ translate('Website') }}</label><input type="url" name="website" class="form-control"></div>
                    <div class="col-md-3 form-group"><label>{{ translate('Support Email') }}</label><input type="email" name="support_email" class="form-control"></div>
                    <div class="col-md-3 form-group"><label>{{ translate('Support Phone') }}</label><input type="text" name="support_phone" class="form-control"></div>
                    <div class="col-md-3 form-group"><label>{{ translate('Supported Countries') }}</label><textarea name="supported_countries" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-3 form-group"><label>{{ translate('Supported Modes') }}</label><textarea name="supported_modes" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-3 form-group"><label>{{ translate('Supported Services') }}</label><textarea name="supported_services" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-3 form-group"><label>{{ translate('Container Types') }}</label><textarea name="container_types" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-4 form-group"><label>{{ translate('Default Freight Cost') }}</label><input type="number" step="0.01" name="default_freight_cost" class="form-control" value="0"></div>
                    <div class="col-md-4 form-group"><label>{{ translate('Default Insurance') }}</label><input type="number" step="0.01" name="default_insurance_cost" class="form-control" value="0"></div>
                    <div class="col-md-4 form-group"><label>{{ translate('Default Customs Estimate') }}</label><input type="number" step="0.01" name="default_customs_estimate" class="form-control" value="0"></div>
                    <div class="col-md-6 form-group"><label>{{ translate('Credentials JSON') }}</label><textarea name="credentials" class="form-control" rows="3"></textarea></div>
                    <div class="col-md-6 form-group"><label>{{ translate('Notes') }}</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
                    <div class="col-md-12 form-group">
                        <label class="aiz-checkbox mr-3"><input type="checkbox" name="is_test_mode" value="1" checked><span class="aiz-square-check"></span><span>{{ translate('Test Mode') }}</span></label>
                        <label class="aiz-checkbox"><input type="checkbox" name="is_active" value="1" checked><span class="aiz-square-check"></span><span>{{ translate('Active') }}</span></label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ translate('Save Forwarder') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @forelse ($forwarders as $forwarder)
                <div class="border rounded p-3 mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">{{ $forwarder->name }}</h5>
                            <div class="small text-muted">{{ strtoupper($forwarder->driver) }} / {{ $forwarder->provider_type ?: '-' }}</div>
                            <div class="small text-muted">{{ $forwarder->support_email ?: $forwarder->contact_email ?: '-' }}</div>
                            <div class="small text-muted">{{ translate('Default Cost') }}: {{ number_format((float) $forwarder->default_freight_cost + (float) $forwarder->default_insurance_cost + (float) $forwarder->default_customs_estimate, 2) }} {{ get_system_default_currency()->code }}</div>
                        </div>
                        <div class="text-lg-right">
                            <span class="badge badge-inline badge-{{ $forwarder->is_active ? 'info' : 'danger' }}">{{ $forwarder->is_active ? translate('Active') : translate('Inactive') }}</span>
                            <span class="badge badge-inline badge-{{ $forwarder->is_test_mode ? 'warning' : 'dark' }}">{{ $forwarder->is_test_mode ? translate('Sandbox') : translate('Production') }}</span>
                            <form action="{{ route('admin.b2b.freight-forwarders.test', $forwarder->id) }}" method="POST" class="d-inline-block ml-2">@csrf<button type="submit" class="btn btn-soft-primary btn-sm">{{ translate('Test Connection') }}</button></form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="bg-light rounded p-3 h-100">
                                <h6 class="mb-3">{{ translate('Settings') }}</h6>
                                <form action="{{ route('admin.b2b.freight-forwarders.update', $forwarder->id) }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 form-group"><label>{{ translate('Name') }}</label><input type="text" name="name" class="form-control" value="{{ $forwarder->name }}" required></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Driver') }}</label><select name="driver" class="form-control aiz-selectpicker">@foreach (\App\Models\B2BFreightForwarder::DRIVERS as $driver)<option value="{{ $driver }}" @selected($forwarder->driver === $driver)>{{ strtoupper($driver) }}</option>@endforeach</select></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Provider Type') }}</label><input type="text" name="provider_type" class="form-control" value="{{ $forwarder->provider_type }}"></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Environment') }}</label><input type="text" name="environment" class="form-control" value="{{ $forwarder->environment }}"></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('API Base URL') }}</label><input type="text" name="api_base_url" class="form-control" value="{{ $forwarder->api_base_url }}"></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Website') }}</label><input type="url" name="website" class="form-control" value="{{ $forwarder->website }}"></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Support Email') }}</label><input type="email" name="support_email" class="form-control" value="{{ $forwarder->support_email }}"></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Support Phone') }}</label><input type="text" name="support_phone" class="form-control" value="{{ $forwarder->support_phone }}"></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('API Key') }}</label><input type="text" name="api_key" class="form-control" placeholder="{{ translate('Leave blank to keep unchanged') }}"></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('API Secret') }}</label><input type="text" name="api_secret" class="form-control" placeholder="{{ translate('Leave blank to keep unchanged') }}"></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Username') }}</label><input type="text" name="username" class="form-control" placeholder="{{ translate('Leave blank to keep unchanged') }}"></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Password') }}</label><input type="text" name="password" class="form-control" placeholder="{{ translate('Leave blank to keep unchanged') }}"></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Supported Countries') }}</label><textarea name="supported_countries" class="form-control" rows="2">{{ is_array($forwarder->supported_countries) ? implode(', ', $forwarder->supported_countries) : '' }}</textarea></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Supported Modes') }}</label><textarea name="supported_modes" class="form-control" rows="2">{{ is_array($forwarder->supported_modes) ? implode(', ', $forwarder->supported_modes) : '' }}</textarea></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Supported Services') }}</label><textarea name="supported_services" class="form-control" rows="2">{{ is_array($forwarder->supported_services) ? implode(', ', $forwarder->supported_services) : '' }}</textarea></div>
                                        <div class="col-md-6 form-group"><label>{{ translate('Container Types') }}</label><textarea name="container_types" class="form-control" rows="2">{{ is_array($forwarder->container_types) ? implode(', ', $forwarder->container_types) : '' }}</textarea></div>
                                        <div class="col-md-4 form-group"><label>{{ translate('Default Freight Cost') }}</label><input type="number" step="0.01" name="default_freight_cost" class="form-control" value="{{ $forwarder->default_freight_cost ?? 0 }}"></div>
                                        <div class="col-md-4 form-group"><label>{{ translate('Default Insurance') }}</label><input type="number" step="0.01" name="default_insurance_cost" class="form-control" value="{{ $forwarder->default_insurance_cost ?? 0 }}"></div>
                                        <div class="col-md-4 form-group"><label>{{ translate('Default Customs Estimate') }}</label><input type="number" step="0.01" name="default_customs_estimate" class="form-control" value="{{ $forwarder->default_customs_estimate ?? 0 }}"></div>
                                        <div class="col-md-12 form-group"><label>{{ translate('Credentials JSON') }}</label><textarea name="credentials" class="form-control" rows="3">{{ $forwarder->credentials ? json_encode($forwarder->credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea></div>
                                        <div class="col-md-12 form-group"><label>{{ translate('Notes') }}</label><textarea name="notes" class="form-control" rows="3">{{ $forwarder->notes }}</textarea></div>
                                        <div class="col-md-12 form-group">
                                            <label class="aiz-checkbox mr-3"><input type="checkbox" name="is_test_mode" value="1" @checked($forwarder->is_test_mode)><span class="aiz-square-check"></span><span>{{ translate('Test Mode') }}</span></label>
                                            <label class="aiz-checkbox"><input type="checkbox" name="is_active" value="1" @checked($forwarder->is_active)><span class="aiz-square-check"></span><span>{{ translate('Active') }}</span></label>
                                        </div>
                                        <div class="col-md-12"><button type="submit" class="btn btn-primary btn-sm">{{ translate('Save Forwarder') }}</button></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-xl-6 mt-3 mt-xl-0">
                            <div class="bg-light rounded p-3 h-100">
                                <h6 class="mb-3">{{ translate('Integration') }}</h6>
                                @include('backend.b2b.partials.integration_panel', [
                                    'model' => $forwarder,
                                    'integration' => $integrationPayloads[$forwarder->id] ?? [],
                                    'panelId' => 'forwarder-' . $forwarder->id,
                                    'testConnectionRoute' => route('admin.b2b.freight-forwarders.test', $forwarder->id),
                                    'testAuthenticationRoute' => route('admin.b2b.freight-forwarders.test-authentication', $forwarder->id),
                                    'verifyCredentialsRoute' => route('admin.b2b.freight-forwarders.verify-credentials', $forwarder->id),
                                    'testWebhookRoute' => route('admin.b2b.freight-forwarders.test-webhook', $forwarder->id),
                                    'sendSampleWebhookRoute' => route('admin.b2b.freight-forwarders.send-sample-webhook', $forwarder->id),
                                    'regenerateSecretRoute' => route('admin.b2b.freight-forwarders.regenerate-secret', $forwarder->id),
                                    'updateEventsRoute' => route('admin.b2b.freight-forwarders.integration-events', $forwarder->id),
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4">{{ translate('No freight forwarders found') }}</div>
            @endforelse
            <div class="aiz-pagination mt-4">{{ $forwarders->links() }}</div>
        </div>
    </div>
@endsection
