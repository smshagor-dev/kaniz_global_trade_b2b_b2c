@php
    $package = $package ?? null;
@endphp

<div class="card">
    <div class="card-body">
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('Package Name') }}</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="name" value="{{ old('name', $package?->name) }}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('Amount') }}</label>
            <div class="col-sm-4">
                <input type="number" min="0" step="0.01" class="form-control" name="amount" value="{{ old('amount', $package?->amount ?? 0) }}" required>
            </div>
            <label class="col-sm-2 col-form-label">{{ translate('Duration') }}</label>
            <div class="col-sm-4">
                <input type="number" min="1" class="form-control" name="duration" value="{{ old('duration', $package?->duration ?? 30) }}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('Product Promote Limit') }}</label>
            <div class="col-sm-4">
                <input type="number" min="1" class="form-control" name="product_limit" value="{{ old('product_limit', $package?->product_limit ?? 1000) }}" required>
            </div>
            <label class="col-sm-2 col-form-label">{{ translate('Sort Order') }}</label>
            <div class="col-sm-4">
                <input type="number" min="0" class="form-control" name="sort_order" value="{{ old('sort_order', $package?->sort_order ?? 0) }}">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('Highlight Text') }}</label>
            <div class="col-sm-4">
                <input type="text" class="form-control" name="highlight_text" value="{{ old('highlight_text', $package?->highlight_text) }}" placeholder="Sponsored Product">
            </div>
            <label class="col-sm-2 col-form-label">{{ translate('Logo') }}</label>
            <div class="col-sm-4">
                <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
                    <div class="input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                    </div>
                    <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                    <input type="hidden" name="logo" class="selected-files" value="{{ old('logo', $package?->logo) }}">
                </div>
                <div class="file-preview box sm"></div>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('Description') }}</label>
            <div class="col-sm-10">
                <textarea class="form-control" name="description" rows="4">{{ old('description', $package?->description) }}</textarea>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-sm-10 offset-sm-2">
                <label class="aiz-checkbox">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package?->is_active ?? true))>
                    <span class="aiz-square-check"></span>
                    <span>{{ translate('Active') }}</span>
                </label>
            </div>
        </div>
        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
        </div>
    </div>
</div>
