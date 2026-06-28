@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4"><h1 class="fs-20 fw-700 text-dark">{{ translate('Smart Product Pricing') }}</h1></div>
    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('b2b.ai.price-recommendation') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-3"><label>{{ translate('Product ID') }}</label><input type="number" name="product_id" class="form-control rounded-0" value="{{ old('product_id') }}"></div>
                    <div class="form-group col-md-3"><label>{{ translate('Country') }}</label><input type="text" name="country" class="form-control rounded-0" value="{{ old('country', $company->country) }}"></div>
                    <div class="form-group col-md-3"><label>{{ translate('Currency') }}</label><input type="text" name="currency" class="form-control rounded-0" value="{{ old('currency', 'USD') }}" required></div>
                    <div class="form-group col-md-3"><label>{{ translate('Profit Margin') }}</label><input type="number" step="0.01" name="profit_margin" class="form-control rounded-0" value="{{ old('profit_margin', 0.2) }}" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-2"><label>{{ translate('Supplier Cost') }}</label><input type="number" step="0.01" name="supplier_cost" class="form-control rounded-0" value="{{ old('supplier_cost') }}" required></div>
                    <div class="form-group col-md-2"><label>{{ translate('Shipping') }}</label><input type="number" step="0.01" name="shipping_cost" class="form-control rounded-0" value="{{ old('shipping_cost', 0) }}"></div>
                    <div class="form-group col-md-2"><label>{{ translate('Customs') }}</label><input type="number" step="0.01" name="customs_cost" class="form-control rounded-0" value="{{ old('customs_cost', 0) }}"></div>
                    <div class="form-group col-md-2"><label>{{ translate('Tax') }}</label><input type="number" step="0.01" name="tax_cost" class="form-control rounded-0" value="{{ old('tax_cost', 0) }}"></div>
                    <div class="form-group col-md-2"><label>{{ translate('VAT') }}</label><input type="number" step="0.01" name="vat_cost" class="form-control rounded-0" value="{{ old('vat_cost', 0) }}"></div>
                    <div class="form-group col-md-2"><label>{{ translate('Platform Fee') }}</label><input type="number" step="0.01" name="platform_fee" class="form-control rounded-0" value="{{ old('platform_fee', 0) }}"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label>{{ translate('Competition Index') }}</label><input type="number" step="0.01" min="0" max="1" name="competition_index" class="form-control rounded-0" value="{{ old('competition_index', 0.5) }}"></div>
                    <div class="form-group col-md-4"><label>{{ translate('Market Trend Index') }}</label><input type="number" step="0.01" min="0" max="1" name="market_trend_index" class="form-control rounded-0" value="{{ old('market_trend_index', 0.5) }}"></div>
                    <div class="form-group col-md-4"><label>{{ translate('Seasonality Index') }}</label><input type="number" step="0.01" min="0" max="1" name="seasonality_index" class="form-control rounded-0" value="{{ old('seasonality_index', 0.5) }}"></div>
                </div>
                <button type="submit" class="btn btn-primary rounded-0">{{ translate('Generate Recommendation') }}</button>
            </form>
        </div>
    </div>
    @if ($recommendation)
        <div class="card rounded-0 shadow-none border mb-4"><div class="card-body">
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Selling Price') }}</div><div class="col-md-9">{{ $recommendation->selling_price }} {{ $recommendation->currency }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Minimum Profitable') }}</div><div class="col-md-9">{{ $recommendation->minimum_profitable_price }} {{ $recommendation->currency }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Wholesale / Distributor / Export') }}</div><div class="col-md-9">{{ $recommendation->wholesale_price }} / {{ $recommendation->distributor_price }} / {{ $recommendation->export_price }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Confidence / Source') }}</div><div class="col-md-9">{{ $recommendation->confidence_score }} / {{ strtoupper($recommendation->source) }}</div></div>
            <div class="row"><div class="col-md-3 text-secondary">{{ translate('Explanation') }}</div><div class="col-md-9">{{ $recommendation->explanation }}</div></div>
        </div></div>
    @endif
    @include('b2b.ai.partials.history_table', ['title' => translate('Recommendation History'), 'records' => $history, 'columns' => ['created_at' => 'Date', 'selling_price' => 'Selling', 'minimum_profitable_price' => 'Minimum', 'confidence_score' => 'Confidence', 'source' => 'Source']])
@endsection
