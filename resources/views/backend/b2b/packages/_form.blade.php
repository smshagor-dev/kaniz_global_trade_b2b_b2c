@php
    $package = $package ?? null;
@endphp

<div class="card">
    <div class="card-body">
        <input type="hidden" name="package_type" value="{{ old('package_type', $package?->package_type ?? (!empty($forceSupplierFeaturedPackage) ? 'supplier_featured' : 'membership')) }}">
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('Package Name') }}</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" name="name" value="{{ old('name', $package?->name) }}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('Package For') }}</label>
            <div class="col-sm-4">
                @if (!empty($forceSupplierFeaturedPackage))
                    <input type="hidden" name="package_for" value="supplier">
                    <input type="text" class="form-control" value="{{ translate('Supplier') }}" readonly>
                @else
                    <select class="form-control aiz-selectpicker" name="package_for" required>
                        <option value="buyer" @selected(old('package_for', $package?->package_for) === 'buyer')>{{ translate('Buyer') }}</option>
                        <option value="supplier" @selected(old('package_for', $package?->package_for) === 'supplier')>{{ translate('Supplier') }}</option>
                    </select>
                @endif
            </div>
            <label class="col-sm-2 col-form-label">{{ translate('Amount') }}</label>
            <div class="col-sm-4">
                <input type="number" min="0" step="0.01" class="form-control" name="amount" value="{{ old('amount', $package?->amount ?? 0) }}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('Duration') }}</label>
            <div class="col-sm-4">
                <input type="number" min="1" class="form-control" name="duration" value="{{ old('duration', $package?->duration ?? 30) }}" required>
            </div>
            <label class="col-sm-2 col-form-label">{{ translate('Sort Order') }}</label>
            <div class="col-sm-4">
                <input type="number" min="0" class="form-control" name="sort_order" value="{{ old('sort_order', $package?->sort_order ?? 0) }}">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('RFQ Limit') }}</label>
            <div class="col-sm-4">
                <input type="number" min="0" class="form-control" name="rfq_limit" value="{{ old('rfq_limit', $package?->rfq_limit ?? 0) }}" required>
                <small class="text-muted">{{ translate('0 means unlimited') }}</small>
            </div>
            <label class="col-sm-2 col-form-label">{{ translate('Quotation Limit') }}</label>
            <div class="col-sm-4">
                <input type="number" min="0" class="form-control" name="quotation_limit" value="{{ old('quotation_limit', $package?->quotation_limit ?? 0) }}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('Product Limit') }}</label>
            <div class="col-sm-4">
                <input type="number" min="0" class="form-control" name="product_limit" value="{{ old('product_limit', $package?->product_limit ?? 0) }}" required>
            </div>
            <label class="col-sm-2 col-form-label">{{ translate('Team Limit') }}</label>
            <div class="col-sm-4">
                <input type="number" min="1" class="form-control" name="member_limit" value="{{ old('member_limit', $package?->member_limit ?? 1) }}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">{{ translate('Highlight Text') }}</label>
            <div class="col-sm-4">
                <input type="text" class="form-control" name="highlight_text" value="{{ old('highlight_text', $package?->highlight_text) }}">
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
                <div class="row">
                    @foreach ([
                        'priority_listing' => 'Priority Listing',
                        'featured_profile' => 'Featured Supplier Homepage',
                        'verified_badge' => 'Verified Badge',
                        'analytics_access' => 'Analytics Access',
                        'dedicated_support' => 'Dedicated Support',
                        'is_active' => 'Active',
                    ] as $field => $label)
                        @if (!empty($forceSupplierFeaturedPackage) && $field === 'featured_profile')
                            <div class="col-md-4 mb-2">
                                <input type="hidden" name="featured_profile" value="1">
                                <label class="aiz-checkbox">
                                    <input type="checkbox" checked disabled>
                                    <span class="aiz-square-check"></span>
                                    <span>{{ translate($label) }}</span>
                                </label>
                            </div>
                            @continue
                        @endif
                        <div class="col-md-4 mb-2">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $package?->$field ?? ($field === 'is_active')))>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate($label) }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
        </div>
    </div>
</div>
