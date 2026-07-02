@extends('backend.layouts.app')

@section('content')

<div class="row">

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('System Default Currency')}}</h5>
            </div>
            <div class="card-body">
                <form class="form-horizontal" action="{{ route('business_settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group row">
                        <div class="col-lg-3">
                            <label class="control-label">{{translate('System Default Currency')}}</label>
                        </div>
                        <div class="col-lg-6">
                            <select class="form-control aiz-selectpicker" name="system_default_currency" data-live-search="true">
                                @foreach ($active_currencies as $key => $currency)
                                    <option value="{{ $currency->id }}" <?php if(get_setting('system_default_currency') == $currency->id) echo 'selected'?> >
                                        {{ $currency->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="types[]" value="system_default_currency">
                        <div class="col-lg-3">
                            <button class="btn btn-sm btn-primary" type="submit">{{translate('Save')}}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Set Currency Formats')}}</h5>
            </div>
            <div class="card-body">
                <form class="form-horizontal" action="{{ route('business_settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="symbol_format">
                        <div class="col-lg-3">
                            <label class="control-label">{{translate('Symbol Format')}}</label>
                        </div>
                        <div class="col-lg-6">
                            <select class="form-control aiz-selectpicker" name="symbol_format">
                                <option value="1" @if(get_setting('symbol_format') == 1) selected @endif>[Symbol][Amount]</option>
                                <option value="2" @if(get_setting('symbol_format') == 2) selected @endif>[Amount][Symbol]</option>
                                <option value="3" @if(get_setting('symbol_format') == 3) selected @endif>[Symbol] [Amount]</option>
                                <option value="4" @if(get_setting('symbol_format') == 4) selected @endif>[Amount] [Symbol]</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="decimal_separator">
                        <div class="col-lg-3">
                            <label class="control-label">{{translate('Decimal Separator')}}</label>
                        </div>
                        <div class="col-lg-6">
                            <select class="form-control aiz-selectpicker" name="decimal_separator">
                                <option value="1" @if(get_setting('decimal_separator') == 1) selected @endif>1,23,456.70</option>
                                <option value="2" @if(get_setting('decimal_separator') == 2) selected @endif>1.23.456,70</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="no_of_decimals">
                        <div class="col-lg-3">
                            <label class="control-label">{{translate('No of decimals')}}</label>
                        </div>
                        <div class="col-lg-6">
                            <select class="form-control aiz-selectpicker" name="no_of_decimals">
                                <option value="0" @if(get_setting('no_of_decimals') == 0) selected @endif>12345</option>
                                <option value="1" @if(get_setting('no_of_decimals') == 1) selected @endif>1234.5</option>
                                <option value="2" @if(get_setting('no_of_decimals') == 2) selected @endif>123.45</option>
                                <option value="3" @if(get_setting('no_of_decimals') == 3) selected @endif>12.345</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Global Currency Sync') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('currency.api_settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">{{ translate('Provider') }}</label>
                        <div class="col-lg-9">
                            <select class="form-control aiz-selectpicker" name="provider">
                                <option value="exchange_rate_api" @selected(($currencySettings->provider ?? 'exchange_rate_api') === 'exchange_rate_api')>ExchangeRate-API</option>
                                <option value="manual" @selected(($currencySettings->provider ?? null) === 'manual')>{{ translate('Manual Rates') }}</option>
                                <option value="custom" @selected(($currencySettings->provider ?? null) === 'custom')>{{ translate('Custom Driver') }}</option>
                            </select>
                            <input type="hidden" name="driver" value="{{ $currencySettings->driver ?? 'exchange_rate_api' }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">{{ translate('Base Currency') }}</label>
                        <div class="col-lg-9">
                            <select class="form-control aiz-selectpicker" name="base_currency_code" data-live-search="true">
                                @foreach ($active_currencies as $currency)
                                    <option value="{{ $currency->code }}" @selected(($currencySettings->base_currency_code ?? get_system_default_currency()->code) === $currency->code)>{{ $currency->name }} ({{ $currency->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">{{ translate('Default Display Currency') }}</label>
                        <div class="col-lg-9">
                            <select class="form-control aiz-selectpicker" name="default_display_currency_code" data-live-search="true">
                                @foreach ($active_currencies as $currency)
                                    <option value="{{ $currency->code }}" @selected(($currencySettings->default_display_currency_code ?? get_system_default_currency()->code) === $currency->code)>{{ $currency->name }} ({{ $currency->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">{{ translate('Sync Frequency') }}</label>
                        <div class="col-lg-9">
                            <select class="form-control aiz-selectpicker" name="sync_frequency">
                                <option value="hourly" @selected(($currencySettings->sync_frequency ?? null) === 'hourly')>{{ translate('Hourly') }}</option>
                                <option value="six_hours" @selected(($currencySettings->sync_frequency ?? 'six_hours') === 'six_hours')>{{ translate('Every 6 Hours') }}</option>
                                <option value="daily" @selected(($currencySettings->sync_frequency ?? null) === 'daily')>{{ translate('Daily') }}</option>
                                <option value="weekly" @selected(($currencySettings->sync_frequency ?? null) === 'weekly')>{{ translate('Weekly') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">{{ translate('API Key') }}</label>
                        <div class="col-lg-9">
                            <input type="password" class="form-control" name="api_key" placeholder="{{ translate('ExchangeRate-API key') }}">
                            <small class="text-muted">{{ translate('Stored encrypted in the database. If blank, the existing DB value or EXCHANGE_RATE_API_KEY fallback is used.') }}</small>
                            <br>
                            <small class="text-muted">{{ translate('Current environment fallback key is detected from .env and will be used automatically if no DB key is saved.') }}</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">{{ translate('Automatic Sync') }}</label>
                        <div class="col-lg-9">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input type="checkbox" name="auto_sync_enabled" value="1" @checked(($currencySettings->auto_sync_enabled ?? true))>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save Currency Sync Settings') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Sync Status') }}</h5>
            </div>
            <div class="card-body">
                <p><strong>{{ translate('Last Sync') }}:</strong> {{ optional($currencySettings?->last_sync_at)->diffForHumans() ?? translate('Never') }}</p>
                <p><strong>{{ translate('Status') }}:</strong> {{ $currencySettings?->last_sync_status ?? translate('Not started') }}</p>
                <p><strong>{{ translate('Cron Cadence') }}:</strong> {{ translate('Every 6 Hours') }}</p>
                <p><strong>{{ translate('Last Error') }}:</strong> {{ $currencySettings?->last_error ?? translate('None') }}</p>
                <div class="d-flex flex-wrap" style="gap: 8px;">
                    <form action="{{ route('currency.test_connection') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary">{{ translate('Test Connection') }}</button>
                    </form>
                    <form action="{{ route('currency.sync') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Sync Now') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col-md-6">
			<h1 class="h3">{{translate('All Currencies')}}</h1>
		</div>
		<div class="col-md-6 text-md-right">
			<a onclick="currency_modal()" href="#" class="btn btn-circle btn-info">
				<span>{{translate('Add New Currency')}}</span>
			</a>
		</div>
	</div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col text-center text-md-left">
            <h5 class="mb-md-0 h6">{{ translate('All Currencies') }}</h5>
        </div>
        <div class="col-md-4">
            <form class="" id="sort_currencies" action="" method="GET">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="search" name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type name & Enter') }}">
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th data-breakpoints="lg">#</th>
                    <th>{{translate('Currency name')}}</th>
                    <th data-breakpoints="lg">{{translate('Currency symbol')}}</th>
                    <th data-breakpoints="lg">{{translate('Currency code')}}</th>
                    <th data-breakpoints="xl">{{translate('Decimals')}}</th>
                    <th>{{translate('Exchange rate')}}(1 USD = ?)</th>
                    <th data-breakpoints="lg">{{translate('Status')}}</th>
                    <th class="text-right">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($currencies as $key => $currency)
                    <tr>
                        <td>{{ ($key+1) + ($currencies->currentPage() - 1)*$currencies->perPage() }}</td>
                        <td>{{$currency->name}}</td>
                        <td>{{$currency->symbol}}</td>
                        <td>{{$currency->code}}</td>
                        <td>{{ $currency->decimal_places ?? get_setting('no_of_decimals') }}</td>
                        <td>{{$currency->exchange_rate}}</td>
                        <td>
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input onchange="update_currency_status(this)" value="{{ $currency->id }}" type="checkbox" <?php if($currency->status == 1) echo "checked";?> >
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td class="text-right">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" onclick="edit_currency_modal('{{$currency->id}}');" title="{{ translate('Edit') }}">
                                <i class="las la-edit"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $currencies->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')

    <!-- Delete Modal -->
    @include('modals.delete_modal')

    <div class="modal fade" id="add_currency_modal">
        <div class="modal-dialog">
            <div class="modal-content" id="modal-content">

            </div>
        </div>
    </div>

    <div class="modal fade" id="currency_modal_edit">
        <div class="modal-dialog">
            <div class="modal-content" id="modal-content">

            </div>
        </div>
    </div>

@endsection

@section('script')
    <script type="text/javascript">

        function sort_currencies(el){
            $('#sort_currencies').submit();
        }

        function currency_modal(){
            $.get('{{ route('currency.create') }}',function(data){
                $('#modal-content').html(data);
                $('#add_currency_modal').modal('show');
            });
        }

        function update_currency_status(el){

            if('{{env('DEMO_MODE')}}' == 'On'){
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            if(el.checked){
                var status = 1;
            }
            else{
                var status = 0;
            }

            $.post('{{ route('currency.update_status') }}', {_token:'{{ csrf_token() }}', id:el.value, status:status}, function(data){
                if(data == 1){
                    AIZ.plugins.notify('success', '{{ translate('Currency Status updated successfully') }}');
                }
                else{
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }

        function edit_currency_modal(id){
            $.post('{{ route('currency.edit') }}',{_token:'{{ @csrf_token() }}', id:id}, function(data){
                $('#currency_modal_edit .modal-content').html(data);
                $('#currency_modal_edit').modal('show', {backdrop: 'static'});
            });
        }
    </script>
@endsection
