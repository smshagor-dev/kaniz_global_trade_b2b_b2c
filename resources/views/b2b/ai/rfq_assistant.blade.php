@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <h1 class="fs-20 fw-700 text-dark">{{ translate('RFQ Assistant') }}</h1>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('b2b.ai.rfq-assistant.generate') }}">
                @csrf
                <div class="form-group">
                    <label>{{ translate('Title') }}</label>
                    <input type="text" class="form-control rounded-0" name="title" value="{{ old('title', request('title')) }}">
                </div>
                <div class="form-group">
                    <label>{{ translate('Description') }}</label>
                    <textarea class="form-control rounded-0" name="description" rows="5">{{ old('description', request('description')) }}</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>{{ translate('Category ID') }}</label>
                        <input type="number" class="form-control rounded-0" name="category_id" value="{{ old('category_id', request('category_id')) }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>{{ translate('Product ID') }}</label>
                        <input type="number" class="form-control rounded-0" name="product_id" value="{{ old('product_id', request('product_id')) }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>{{ translate('Quantity') }}</label>
                        <input type="number" step="0.01" class="form-control rounded-0" name="quantity" value="{{ old('quantity', request('quantity')) }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>{{ translate('Unit') }}</label>
                        <input type="text" class="form-control rounded-0" name="unit" value="{{ old('unit', request('unit')) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label>{{ translate('Target Price') }}</label>
                        <input type="number" step="0.01" class="form-control rounded-0" name="target_price" value="{{ old('target_price', request('target_price')) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label>{{ translate('Currency') }}</label>
                        <input type="text" class="form-control rounded-0" name="currency" value="{{ old('currency', request('currency', get_system_default_currency()->code)) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label>{{ translate('Incoterm') }}</label>
                        <input type="text" class="form-control rounded-0" name="incoterm" value="{{ old('incoterm', request('incoterm')) }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>{{ translate('Destination Country') }}</label>
                        <input type="text" class="form-control rounded-0" name="destination_country" value="{{ old('destination_country', request('destination_country')) }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>{{ translate('Destination City') }}</label>
                        <input type="text" class="form-control rounded-0" name="destination_city" value="{{ old('destination_city', request('destination_city')) }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary rounded-0">{{ translate('Generate Suggestions') }}</button>
            </form>
        </div>
    </div>

    @if ($suggestion)
        <div class="card rounded-0 shadow-none border">
            <div class="card-header bg-white">
                <h5 class="mb-0">{{ translate('Suggested RFQ Draft') }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-secondary small">{{ translate('Suggested Title') }}</div>
                    <div>{{ $suggestion['title'] ?? '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">{{ translate('Suggested Description') }}</div>
                    <div class="white-space-pre-line">{{ $suggestion['description'] ?? '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">{{ translate('Suggested Incoterm') }}</div>
                    <div>{{ $suggestion['suggested_incoterm'] ?? '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">{{ translate('Suggested Documents') }}</div>
                    <div>{{ collect($suggestion['suggested_documents'] ?? [])->implode(', ') ?: '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">{{ translate('Supplier Requirements') }}</div>
                    <div>{{ collect($suggestion['supplier_requirements'] ?? [])->implode(', ') ?: '-' }}</div>
                </div>
                <div class="text-muted small">{{ translate('Provider') }}: {{ data_get($suggestion, '_meta.provider') }} · {{ translate('Model') }}: {{ data_get($suggestion, '_meta.model') }}</div>
            </div>
        </div>
    @endif
@endsection
