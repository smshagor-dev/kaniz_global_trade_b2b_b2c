@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4"><h1 class="fs-20 fw-700 text-dark">{{ translate('Currency Impact Analysis') }}</h1></div>
    <div class="card rounded-0 shadow-none border mb-4"><div class="card-body">
        <form method="POST" action="{{ route('b2b.ai.currency-analysis') }}">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-4"><label>{{ translate('Currency') }}</label><input type="text" name="currency_code" class="form-control rounded-0" value="{{ old('currency_code', 'USD') }}" required></div>
                <div class="form-group col-md-4"><label>{{ translate('Amount') }}</label><input type="number" step="0.01" name="amount" class="form-control rounded-0" value="{{ old('amount') }}" required></div>
                <div class="form-group col-md-4 d-flex align-items-end"><label class="aiz-checkbox mb-3"><input type="checkbox" name="queue" value="1"><span class="aiz-square-check"></span><span>{{ translate('Queue analysis') }}</span></label></div>
            </div>
            <button type="submit" class="btn btn-primary rounded-0">{{ translate('Analyze Currency Risk') }}</button>
        </form>
    </div></div>
    @if ($analysis)
        <div class="alert alert-info">{{ $analysis->summary }}<br>{{ translate('Recommended invoice currency') }}: {{ $analysis->recommended_invoice_currency }} | {{ translate('Volatility') }}: {{ $analysis->volatility_score }}</div>
    @endif
    @include('b2b.ai.partials.history_table', ['title' => translate('Currency Analysis History'), 'records' => $history, 'columns' => ['created_at' => 'Date', 'currency_code' => 'Currency', 'volatility_score' => 'Volatility', 'recommended_invoice_currency' => 'Recommended', 'confidence_score' => 'Confidence']])
@endsection
