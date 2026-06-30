@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 pl-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $readOnlyQuote = $readOnlyQuote ?? false;
    $offerProductLabel = $rfq->product?->getTranslation('name')
        ?: $rfq->title
        ?: $rfq->category?->getTranslation('name')
        ?: '-';
    $quotationCurrency = $rfq->currency ?: ($currency ?? get_system_default_currency()->code);
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Offer Product') }}</label>
        <div class="col-md-9">
            <input type="hidden" name="product_id" value="{{ old('product_id', $quotationProductId ?? $rfq->product_id) }}">
            <input type="text" class="form-control" value="{{ $offerProductLabel }}" disabled>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Price') }} <span class="text-danger">*</span></label>
        <div class="col-md-9">
            <input type="number" min="0" step="0.01" class="form-control" name="price" value="{{ old('price', $price ?? null) }}" @disabled($readOnlyQuote) required>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Currency') }} <span class="text-danger">*</span></label>
        <div class="col-md-9">
            <input type="hidden" name="currency" value="{{ $quotationCurrency }}">
            <input type="text" class="form-control" value="{{ $quotationCurrency }}" disabled>
            <small class="form-text text-muted">{{ translate('Currency is fixed by the buyer RFQ.') }}</small>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('MOQ') }}</label>
        <div class="col-md-9">
            <input type="number" min="0" step="0.01" class="form-control" name="moq" value="{{ old('moq', $moq ?? null) }}" @disabled($readOnlyQuote)>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Lead Time Days') }}</label>
        <div class="col-md-9">
            <input type="number" min="0" class="form-control" name="lead_time_days" value="{{ old('lead_time_days', $leadTimeDays ?? null) }}" @disabled($readOnlyQuote)>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Shipping Terms') }}</label>
        <div class="col-md-9">
            <input type="text" class="form-control" name="shipping_terms" value="{{ old('shipping_terms', $shippingTerms ?? null) }}" @disabled($readOnlyQuote)>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Incoterm') }}</label>
        <div class="col-md-9">
            <select class="form-control aiz-selectpicker" name="incoterm" @disabled($readOnlyQuote)>
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
            <input type="text" class="form-control" name="payment_terms" value="{{ old('payment_terms', $paymentTerms ?? null) }}" @disabled($readOnlyQuote)>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Message') }}</label>
        <div class="col-md-9">
            <textarea class="form-control" rows="5" name="message" @disabled($readOnlyQuote)>{{ old('message', $message ?? null) }}</textarea>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Attachment') }}</label>
        <div class="col-md-9">
            @unless ($readOnlyQuote)
                <input type="file" class="form-control" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            @endunless
            @if (!empty($attachment))
                <small class="form-text text-muted">
                    <a href="{{ asset($attachment) }}" target="_blank">{{ translate('View current attachment') }}</a>
                </small>
            @endif
        </div>
    </div>
    @unless ($readOnlyQuote)
        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        </div>
    @endunless
</form>
