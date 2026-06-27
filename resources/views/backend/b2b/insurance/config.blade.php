@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('B2B Insurance Config') }}</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row gutters-10 align-items-center">
                <div class="col-lg-8">
                    <h5 class="mb-1">{{ translate('Real Insurance Provider Setup') }}</h5>
                    <p class="text-muted mb-0">{{ translate('Choose a real insurance provider, connect API credentials from admin, and control B2B insurance visibility from this dedicated config screen.') }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex flex-wrap justify-content-lg-end">
                        <a href="{{ route('admin.b2b.insurance.dashboard') }}" class="btn btn-soft-success btn-sm mr-2 mb-2">{{ translate('Insurance Dashboard') }}</a>
                        <a href="{{ route('admin.b2b.logistics-charge-settings.index') }}" class="btn btn-soft-info btn-sm mb-2">{{ translate('Global B2B Config') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4 h-100 border-success">
                    <div class="card-header">{{ translate('Module Controls') }}</div>
                    <div class="card-body">
                        <form action="{{ route('admin.b2b.insurance.config.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="save_scope" value="module">
                            <input type="hidden" name="provider_key" value="{{ ($presetProviders[0]['key'] ?? 'allianz_trade') }}">
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="b2b_insurance_module_enabled" value="1" @checked(old('b2b_insurance_module_enabled', $insuranceSettings['enabled']))>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Enable B2B insurance module globally') }}</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="b2b_insurance_visible" value="1" @checked(old('b2b_insurance_visible', $insuranceSettings['visible']))>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Show insurance UI in buyer and seller panels') }}</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="is_test_mode" value="1" checked>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Apply test mode for the submitted company') }}</span>
                            </label>
                        </div>
                        <div class="border rounded p-3 bg-light text-muted fs-13">
                            {{ translate('Each company below has its own direct CRUD form. Leaving a secret field blank keeps the existing saved value for that company.') }}
                        </div>
                        <button type="submit" class="btn btn-success mt-3">{{ translate('Save Module Controls') }}</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-4 h-100 border-info">
                    <div class="card-header">{{ translate('Insurance Companies') }}</div>
                    <div class="card-body">
                        @foreach (($presetProviders ?? []) as $item)
                            <div class="border rounded p-3 mb-3">
                                <div class="fw-700">{{ $item['preset']['name'] }}</div>
                                <div class="text-muted fs-13">{{ $item['preset']['company'] ?? $item['preset']['name'] }}{{ !empty($item['preset']['country']) ? ' / ' . $item['preset']['country'] : '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                @foreach (($presetProviders ?? []) as $item)
                    @php
                        $provider = $item['provider'];
                        $preset = $item['preset'];
                    @endphp
                    <form action="{{ route('admin.b2b.insurance.config.update') }}" method="POST">
                        @csrf
                    <input type="hidden" name="save_scope" value="provider">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>{{ $preset['name'] }}</span>
                            <div>
                                @if ($provider?->is_default)
                                    <span class="badge badge-inline badge-success">{{ translate('Default') }}</span>
                                @endif
                                <span class="badge badge-inline badge-{{ $provider?->is_active ? 'info' : 'secondary' }}">{{ $provider?->is_active ? translate('Active') : translate('Inactive') }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="provider_key" value="{{ $item['key'] }}">
                            @if ($provider)
                                <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                            @endif
                            <input type="hidden" name="b2b_insurance_module_enabled" value="{{ $insuranceSettings['enabled'] ? 1 : 0 }}">
                            <input type="hidden" name="b2b_insurance_visible" value="{{ $insuranceSettings['visible'] ? 1 : 0 }}">
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label>{{ translate('Company Name') }}</label>
                                    <input type="text" class="form-control" value="{{ $preset['name'] }}" readonly>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ translate('API Key') }}</label>
                                    <input type="password" class="form-control" name="api_key" value="" placeholder="{{ $provider && filled($provider->api_key) ? translate('Keep current') : translate('Paste key') }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ translate('API Secret') }}</label>
                                    <input type="password" class="form-control" name="api_secret" value="" placeholder="{{ translate('Keep current') }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ translate('Username') }}</label>
                                    <input type="text" class="form-control" name="username" value="" placeholder="{{ translate('Optional') }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ translate('Password') }}</label>
                                    <input type="password" class="form-control" name="password" value="" placeholder="{{ translate('Optional') }}">
                                </div>
                                <div class="col-md-1 form-group">
                                    <label>{{ translate('Test') }}</label>
                                    <div class="pt-2">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="is_test_mode" value="1" @checked($provider?->is_test_mode ?? true)>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ translate('Default') }}</label>
                                    <div class="pt-2">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="is_default" value="1" @checked($provider?->is_default)>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ translate('Active') }}</label>
                                    <div class="pt-2">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="is_active" value="1" @checked($provider?->is_active ?? $insuranceSettings['enabled'])>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="d-flex flex-wrap align-items-center">
                                        <div class="text-muted fs-13 mr-3">
                                            {{ $provider && filled($provider->api_key) ? translate('Credentials already saved for this company.') : translate('No credentials saved for this company yet.') }}
                                        </div>
                                        <button type="submit" class="btn btn-success">{{ translate('Save') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </form>
                @endforeach
            </div>
        </div>
@endsection
