@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Request Product Sample') }}</h1>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <form action="{{ route('b2b.sample-orders.store') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product?->id }}">
                <input type="hidden" name="supplier_company_id" value="{{ $supplierCompany?->id }}">
                @if ($sampleProcessingFeeSettings['enabled'])
                    <div class="alert alert-info">
                        {{ translate('Sample Processing Fee') }}:
                        <strong>{{ single_price($sampleProcessingFeeSettings['fixed']) }}</strong>
                        {{ translate('will be added when this sample order is paid.') }}
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Buyer Company') }}</label>
                        <input type="text" class="form-control" value="{{ $buyerCompany->company_name }}" disabled>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Supplier Company') }}</label>
                        <input type="text" class="form-control" value="{{ $supplierCompany?->company_name }}" disabled>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Product') }}</label>
                        <input type="text" class="form-control" value="{{ $product?->getTranslation('name') ?: '-' }}" disabled>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Quantity') }}</label>
                        <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" value="{{ old('quantity', 1) }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Unit') }}</label>
                        <input type="text" name="unit" class="form-control" value="{{ old('unit', $product?->unit) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Currency') }}</label>
                        <input type="text" name="currency" class="form-control" value="{{ old('currency', get_system_default_currency()->code) }}">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ translate('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary rounded-0">{{ translate('Submit Sample Request') }}</button>
            </form>
        </div>
    </div>
@endsection
