@php
    $rfq = $rfq ?? null;
    $selectedProduct = $selectedProduct ?? null;
    $categories = $categories ?? collect();
@endphp

<div class="card rounded-0 shadow-none border">
    <div class="card-header pt-4 border-bottom-0">
        <h5 class="mb-0 fs-18 fw-700 text-dark">{{ $title }}</h5>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($selectedProduct)
            <div class="alert alert-info">
                <strong>{{ translate('Selected Product') }}:</strong>
                {{ $selectedProduct->getTranslation('name') }}
            </div>
        @endif

        <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="product_id" value="{{ old('product_id', $rfq?->product_id ?? $selectedProduct?->id) }}">
            <input type="hidden" name="supplier_company_id" value="{{ old('supplier_company_id', $rfq?->supplier_company_id ?? ($targetSupplierCompany->id ?? null)) }}">

            @if (!empty($targetSupplierCompany))
                <div class="alert alert-info">
                    <strong>{{ translate('Target Supplier') }}:</strong> {{ $targetSupplierCompany->company_name }}
                </div>
            @endif

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Category') }}</label>
                <div class="col-md-9">
                    <select class="form-control aiz-selectpicker" name="category_id" data-live-search="true">
                        <option value="">{{ translate('Select Category') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $rfq?->category_id ?? $selectedProduct?->category_id) === (string) $category->id)>
                                {{ $category->getTranslation('name') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Title') }} <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <input type="text" class="form-control rounded-0" name="title" value="{{ old('title', $rfq?->title ?? ($selectedProduct ? translate('RFQ for') . ' ' . $selectedProduct->getTranslation('name') : null)) }}" required>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Description') }} <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <textarea class="form-control rounded-0" name="description" rows="5" required>{{ old('description', $rfq?->description) }}</textarea>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Quantity') }} <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <input type="number" min="1" step="0.01" class="form-control rounded-0" name="quantity" value="{{ old('quantity', $rfq?->quantity) }}" required>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Unit') }}</label>
                <div class="col-md-9">
                    <input type="text" class="form-control rounded-0" name="unit" value="{{ old('unit', $rfq?->unit ?? $selectedProduct?->unit) }}">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Target Price') }}</label>
                <div class="col-md-9">
                    <input type="number" min="0" step="0.01" class="form-control rounded-0" name="target_price" value="{{ old('target_price', $rfq?->target_price) }}">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Currency') }}</label>
                <div class="col-md-9">
                    <input type="text" class="form-control rounded-0" name="currency" value="{{ old('currency', $rfq?->currency ?? get_system_default_currency()->code) }}">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Incoterm') }}</label>
                <div class="col-md-9">
                    <select class="form-control aiz-selectpicker" name="incoterm">
                        <option value="">{{ translate('Select Incoterm') }}</option>
                        @foreach (\App\Services\B2BTradeService::INCOTERMS as $incoterm)
                            <option value="{{ $incoterm }}" @selected(old('incoterm', $rfq?->incoterm) === $incoterm)>{{ $incoterm }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Destination Country') }}</label>
                <div class="col-md-9">
                    <input type="text" class="form-control rounded-0" name="destination_country" value="{{ old('destination_country', $rfq?->destination_country) }}">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Destination City') }}</label>
                <div class="col-md-9">
                    <input type="text" class="form-control rounded-0" name="destination_city" value="{{ old('destination_city', $rfq?->destination_city) }}">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Expected Delivery Date') }}</label>
                <div class="col-md-9">
                    <input type="date" class="form-control rounded-0" name="expected_delivery_date" value="{{ old('expected_delivery_date', optional($rfq?->expected_delivery_date)->format('Y-m-d')) }}">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Expires At') }}</label>
                <div class="col-md-9">
                    <input type="datetime-local" class="form-control rounded-0" name="expires_at" value="{{ old('expires_at', optional($rfq?->expires_at)->format('Y-m-d\TH:i')) }}">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label fs-14">{{ translate('Attachment') }}</label>
                <div class="col-md-9">
                    <input type="file" class="form-control rounded-0" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    @if ($rfq?->attachment)
                        <small class="text-muted d-block mt-2">
                            <a href="{{ asset($rfq->attachment) }}" target="_blank">{{ translate('View current attachment') }}</a>
                        </small>
                    @endif
                </div>
            </div>

            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary rounded-0 w-150px mt-3">{{ $submitText }}</button>
            </div>
        </form>
    </div>
</div>
