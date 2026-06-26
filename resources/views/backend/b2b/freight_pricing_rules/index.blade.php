@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3"><h1 class="h3">{{ translate('Freight Pricing Rules') }}</h1></div>
    <div class="card mb-4">
        <div class="card-header">{{ translate('Add Pricing Rule') }}</div>
        <div class="card-body">
            <form action="{{ route('admin.b2b.freight-pricing-rules.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3 form-group"><label>{{ translate('Name') }}</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Forwarder') }}</label><select name="forwarder_id" class="form-control aiz-selectpicker" data-live-search="true"><option value="">{{ translate('Any') }}</option>@foreach ($forwarders as $forwarder)<option value="{{ $forwarder->id }}">{{ $forwarder->name }}</option>@endforeach</select></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Mode') }}</label><select name="freight_mode" class="form-control aiz-selectpicker"><option value="">{{ translate('Any') }}</option>@foreach ($freightModes as $mode)<option value="{{ $mode }}">{{ ucwords(str_replace('_', ' ', $mode)) }}</option>@endforeach</select></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Service') }}</label><select name="service_type" class="form-control aiz-selectpicker"><option value="">{{ translate('Any') }}</option>@foreach ($serviceTypes as $type)<option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>@endforeach</select></div>
                    <div class="col-md-1 form-group"><label>{{ translate('Base') }}</label><input type="number" step="0.01" name="base_price" class="form-control" required></div>
                    <div class="col-md-1 form-group"><label>{{ translate('/kg') }}</label><input type="number" step="0.0001" name="price_per_kg" class="form-control"></div>
                    <div class="col-md-1 form-group"><label>{{ translate('/cbm') }}</label><input type="number" step="0.0001" name="price_per_cbm" class="form-control"></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Origin') }}</label><input type="text" name="origin_country" class="form-control"></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Destination') }}</label><input type="text" name="destination_country" class="form-control"></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Container') }}</label><input type="text" name="container_type" class="form-control"></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Incoterm') }}</label><select name="incoterm" class="form-control aiz-selectpicker"><option value="">{{ translate('Any') }}</option>@foreach ($incoterms as $incoterm)<option value="{{ $incoterm }}">{{ $incoterm }}</option>@endforeach</select></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Fuel %') }}</label><input type="number" step="0.001" name="fuel_surcharge_percent" class="form-control"></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Platform %') }}</label><input type="number" step="0.001" name="platform_fee_percent" class="form-control"></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Platform Fixed') }}</label><input type="number" step="0.01" name="platform_fee_fixed" class="form-control"></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Currency') }}</label><input type="text" name="currency" class="form-control" value="USD" required></div>
                    <div class="col-md-2 form-group pt-4"><label class="aiz-checkbox"><input type="checkbox" name="active" value="1" checked><span class="aiz-square-check"></span><span>{{ translate('Active') }}</span></label></div>
                </div>
                <button type="submit" class="btn btn-primary">{{ translate('Save Rule') }}</button>
            </form>
        </div>
    </div>
    <div class="card"><div class="card-body"><table class="table aiz-table mb-0"><thead><tr><th>{{ translate('Rule') }}</th><th>{{ translate('Route') }}</th><th>{{ translate('Pricing') }}</th><th>{{ translate('Status') }}</th></tr></thead><tbody>@forelse ($rules as $rule)<tr><td>{{ $rule->name }}<div class="small text-muted">{{ $rule->forwarder?->name ?: translate('Any Forwarder') }}</div></td><td>{{ $rule->origin_country ?: '*' }} to {{ $rule->destination_country ?: '*' }}</td><td>{{ single_price($rule->base_price) }} {{ $rule->currency }} / {{ $rule->price_per_kg }} kg / {{ $rule->price_per_cbm }} cbm</td><td>{{ $rule->active ? translate('Active') : translate('Inactive') }}</td></tr>@empty<tr><td colspan="4" class="text-center">{{ translate('No pricing rules found') }}</td></tr>@endforelse</tbody></table><div class="aiz-pagination mt-4">{{ $rules->links() }}</div></div></div>
@endsection
