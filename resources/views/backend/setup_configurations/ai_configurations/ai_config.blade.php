@extends('backend.layouts.app')

@section('content')
<div class="row pt-4">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ $editProvider ? translate('Edit AI Provider') : translate('Add AI Provider') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ $editProvider ? route('ai.providers.update', encrypt($editProvider->id)) : route('ai.providers.store') }}" method="POST">
                    @csrf
                    @if ($editProvider)
                        @method('PATCH')
                    @endif
                    <div class="form-group">
                        <label>{{ translate('Provider') }}</label>
                        <select class="form-control aiz-selectpicker" name="provider">
                            @foreach ($providerOptions as $providerOption)
                                <option value="{{ $providerOption }}" @selected(optional($editProvider)->provider === $providerOption)>{{ ucfirst($providerOption) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Display Name') }}</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', optional($editProvider)->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('API Key') }}</label>
                        <input type="password" class="form-control" name="api_key" value="" placeholder="{{ $editProvider ? translate('Leave blank to keep current key') : '' }}">
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Base URL') }}</label>
                        <input type="text" class="form-control" name="base_url" value="{{ old('base_url', optional($editProvider)->base_url) }}">
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Model') }}</label>
                        <input type="text" class="form-control" name="model" value="{{ old('model', optional($editProvider)->model) }}" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>{{ translate('Temperature') }}</label>
                            <input type="number" class="form-control" name="temperature" step="0.1" min="0" max="2" value="{{ old('temperature', optional($editProvider)->temperature ?? 0.7) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>{{ translate('Max Tokens') }}</label>
                            <input type="number" class="form-control" name="max_tokens" min="1" max="4096" value="{{ old('max_tokens', optional($editProvider)->max_tokens ?? 1024) }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>{{ translate('Timeout') }}</label>
                            <input type="number" class="form-control" name="timeout" min="1" max="120" value="{{ old('timeout', optional($editProvider)->timeout ?? 30) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>{{ translate('Retry Count') }}</label>
                            <input type="number" class="form-control" name="retry_count" min="0" max="5" value="{{ old('retry_count', optional($editProvider)->retry_count ?? 1) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>{{ translate('Daily Limit') }}</label>
                            <input type="number" class="form-control" name="daily_limit" min="0" value="{{ old('daily_limit', optional($editProvider)->daily_limit) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Monthly Limit') }}</label>
                        <input type="number" class="form-control" name="monthly_limit" min="0" value="{{ old('monthly_limit', optional($editProvider)->monthly_limit) }}">
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Settings JSON') }}</label>
                        <textarea class="form-control" rows="4" name="settings_json" placeholder='{"pricing":{"prompt_per_1k":0.001,"completion_per_1k":0.002}}'>{{ old('settings_json', $editProvider ? json_encode($editProvider->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="aiz-switch aiz-switch-success mb-0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', optional($editProvider)->is_active ?? true))><span></span></label>
                        <span class="ml-2">{{ translate('Enable provider') }}</span>
                    </div>
                    <div class="form-group">
                        <label class="aiz-switch aiz-switch-success mb-0"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', optional($editProvider)->is_default ?? false))><span></span></label>
                        <span class="ml-2">{{ translate('Set as default') }}</span>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ $editProvider ? translate('Update Provider') : translate('Add Provider') }}</button>
                    @if ($editProvider)
                        <a href="{{ route('ai-config') }}" class="btn btn-soft-secondary ml-2">{{ translate('Cancel') }}</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Legacy Gemini Compatibility') }}</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">{{ translate('Existing Gemini product generation remains active through the new AI foundation layer.') }}</p>
                <p class="mb-0">{{ translate('The legacy Gemini settings form still syncs into the new provider table for backward compatibility.') }}</p>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">{{ translate('AI Providers') }}</h5>
                <div>
                    <a href="{{ route('ai-cost-analytics') }}" class="btn btn-soft-primary btn-sm">{{ translate('Cost Analytics') }}</a>
                    <a href="{{ route('ai-feedback') }}" class="btn btn-soft-warning btn-sm">{{ translate('Feedback') }}</a>
                </div>
            </div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Provider') }}</th>
                            <th>{{ translate('Model') }}</th>
                            <th>{{ translate('Limits') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($providers as $provider)
                            <tr>
                                <td>
                                    <strong>{{ $provider->name }}</strong><br>
                                    <small class="text-muted">{{ ucfirst($provider->provider) }}</small>
                                </td>
                                <td>{{ $provider->model }}</td>
                                <td>
                                    <small>{{ translate('Daily') }}: {{ $provider->daily_limit ?: translate('Unlimited') }}</small><br>
                                    <small>{{ translate('Monthly') }}: {{ $provider->monthly_limit ?: translate('Unlimited') }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-inline {{ $provider->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ $provider->is_active ? translate('Active') : translate('Disabled') }}</span>
                                    @if ($provider->is_default)
                                        <span class="badge badge-inline badge-soft-primary">{{ translate('Default') }}</span>
                                    @endif
                                    @if ($provider->last_status)
                                        <div><small class="text-muted">{{ $provider->last_status }}</small></div>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('ai-config', ['edit' => $provider->id]) }}" class="btn btn-soft-info btn-sm">{{ translate('Edit') }}</a>
                                    <form action="{{ route('ai.providers.test', encrypt($provider->id)) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-soft-primary btn-sm">{{ translate('Test') }}</button>
                                    </form>
                                    <form action="{{ route('ai.providers.default', encrypt($provider->id)) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-soft-success btn-sm">{{ translate('Default') }}</button>
                                    </form>
                                    <form action="{{ route('ai.providers.toggle', encrypt($provider->id)) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-soft-warning btn-sm">{{ $provider->is_active ? translate('Disable') : translate('Enable') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">{{ translate('No AI providers configured yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
