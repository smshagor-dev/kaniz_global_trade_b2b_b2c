@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mb-4"><h1 class="h3">{{ translate('Fraud Settings') }}</h1></div>
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ translate('Settings') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.fraud.settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group"><label><input type="checkbox" name="enabled" value="1" @checked($settings['enabled'])> {{ translate('Enable fraud checker') }}</label></div>
                    <div class="form-group"><label><input type="checkbox" name="ai_enabled" value="1" @checked($settings['ai_enabled'])> {{ translate('Enable AI fraud check') }}</label></div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label>{{ translate('Manual review threshold') }}</label><input type="number" class="form-control" name="manual_review_threshold" value="{{ $settings['manual_review_threshold'] }}"></div>
                        <div class="form-group col-md-6"><label>{{ translate('Restriction threshold') }}</label><input type="number" class="form-control" name="restriction_threshold" value="{{ $settings['restriction_threshold'] }}"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label>{{ translate('Block threshold') }}</label><input type="number" class="form-control" name="block_threshold" value="{{ $settings['block_threshold'] }}"></div>
                        <div class="form-group col-md-6"><label>{{ translate('RFQ limit per day') }}</label><input type="number" class="form-control" name="rfq_limit_per_day" value="{{ $settings['rfq_limit_per_day'] }}"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label>{{ translate('Rule weight %') }}</label><input type="number" class="form-control" name="rule_weight_percentage" value="{{ $settings['rule_weight_percentage'] }}"></div>
                        <div class="form-group col-md-6"><label>{{ translate('AI weight %') }}</label><input type="number" class="form-control" name="ai_weight_percentage" value="{{ $settings['ai_weight_percentage'] }}"></div>
                    </div>
                    <div class="form-group"><label><input type="checkbox" name="auto_block_enabled" value="1" @checked($settings['auto_block_enabled'])> {{ translate('Enable auto block') }}</label></div>
                    <button class="btn btn-primary">{{ translate('Save Settings') }}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ translate('Fraud Rules') }}</h5></div>
            <div class="card-body">
                @forelse ($rules as $rule)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-700">{{ $rule->name }}</div>
                        <div class="small text-muted">{{ $rule->code }} • {{ $rule->score }}</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ translate('No fraud rules seeded yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
