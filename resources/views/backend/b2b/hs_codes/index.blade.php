@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3"><h1 class="h3">{{ translate('HS Codes') }}</h1></div>
    <div class="card mb-4">
        <div class="card-header">{{ translate('Add HS Code') }}</div>
        <div class="card-body">
            <form action="{{ route('admin.b2b.hs-codes.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-2 form-group"><label>{{ translate('HS Code') }}</label><input type="text" name="hs_code" class="form-control" required></div>
                    <div class="col-md-3 form-group"><label>{{ translate('Description') }}</label><input type="text" name="description" class="form-control"></div>
                    <div class="col-md-2 form-group"><label>{{ translate('Country') }}</label><input type="text" name="country" class="form-control"></div>
                    <div class="col-md-1 form-group"><label>{{ translate('Duty %') }}</label><input type="number" step="0.001" name="duty_percent" class="form-control"></div>
                    <div class="col-md-1 form-group"><label>{{ translate('VAT/GST %') }}</label><input type="number" step="0.001" name="vat_gst_percent" class="form-control"></div>
                    <div class="col-md-3 form-group"><label>{{ translate('Required Documents') }}</label><input type="text" name="required_documents" class="form-control" placeholder="Invoice, Packing List"></div>
                    <div class="col-md-6 form-group"><label>{{ translate('Restrictions') }}</label><textarea name="restrictions" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-6 form-group pt-4">
                        <label class="aiz-checkbox mr-3"><input type="checkbox" name="is_dangerous_goods" value="1"><span class="aiz-square-check"></span><span>{{ translate('Dangerous Goods') }}</span></label>
                        <label class="aiz-checkbox"><input type="checkbox" name="is_active" value="1" checked><span class="aiz-square-check"></span><span>{{ translate('Active') }}</span></label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ translate('Save HS Code') }}</button>
            </form>
        </div>
    </div>
    <div class="card"><div class="card-body"><table class="table aiz-table mb-0"><thead><tr><th>{{ translate('HS Code') }}</th><th>{{ translate('Description') }}</th><th>{{ translate('Country') }}</th><th>{{ translate('Duty') }}</th><th>{{ translate('VAT/GST') }}</th></tr></thead><tbody>@forelse ($codes as $code)<tr><td>{{ $code->hs_code }}</td><td>{{ $code->description ?: '-' }}</td><td>{{ $code->country ?: '-' }}</td><td>{{ $code->duty_percent }}%</td><td>{{ $code->vat_gst_percent }}%</td></tr>@empty<tr><td colspan="5" class="text-center">{{ translate('No HS codes found') }}</td></tr>@endforelse</tbody></table><div class="aiz-pagination mt-4">{{ $codes->links() }}</div></div></div>
@endsection
