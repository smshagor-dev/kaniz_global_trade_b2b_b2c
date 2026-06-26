@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 pl-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Offer Product') }}</label>
        <div class="col-md-9">
            <input type="hidden" name="product_id" value="{{ old('product_id', $quotationProductId ?? $rfq->product_id) }}">
            <input type="text" class="form-control" value="{{ $rfq->product?->getTranslation('name') ?? '-' }}" disabled>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Price') }} <span class="text-danger">*</span></label>
        <div class="col-md-9">
            <input type="number" min="0" step="0.01" class="form-control" name="price" value="{{ old('price', $price ?? null) }}" required>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Currency') }} <span class="text-danger">*</span></label>
        <div class="col-md-9">
            <input type="text" class="form-control" name="currency" value="{{ old('currency', $currency ?? get_system_default_currency()->code) }}" required>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('MOQ') }}</label>
        <div class="col-md-9">
            <input type="number" min="0" step="0.01" class="form-control" name="moq" value="{{ old('moq', $moq ?? null) }}">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Lead Time Days') }}</label>
        <div class="col-md-9">
            <input type="number" min="0" class="form-control" name="lead_time_days" value="{{ old('lead_time_days', $leadTimeDays ?? null) }}">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Shipping Terms') }}</label>
        <div class="col-md-9">
            <input type="text" class="form-control" name="shipping_terms" value="{{ old('shipping_terms', $shippingTerms ?? null) }}">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Incoterm') }}</label>
        <div class="col-md-9">
            <select class="form-control aiz-selectpicker" name="incoterm">
                <option value="">{{ translate('Select Incoterm') }}</option>
                @foreach (\App\Services\B2BTradeService::INCOTERMS as $incoterm)
                    <option value="{{ $incoterm }}" @selected(old('incoterm', $incotermValue ?? null) === $incoterm)>{{ $incoterm }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Payment Terms') }}</label>
        <div class="col-md-9">
            <input type="text" class="form-control" name="payment_terms" value="{{ old('payment_terms', $paymentTerms ?? null) }}">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Message') }}</label>
        <div class="col-md-9">
            <textarea class="form-control" rows="5" name="message">{{ old('message', $message ?? null) }}</textarea>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Attachment') }}</label>
        <div class="col-md-9">
            <input type="file" class="form-control" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            @if (!empty($attachment))
                <small class="form-text text-muted">
                    <a href="{{ asset($attachment) }}" target="_blank">{{ translate('View current attachment') }}</a>
                </small>
            @endif
        </div>
    </div>
    <div class="text-right">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</form>
