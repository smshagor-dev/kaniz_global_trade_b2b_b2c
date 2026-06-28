@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4"><h1 class="fs-20 fw-700 text-dark">{{ translate('Trade Finance Recommendation') }}</h1></div>
    <div class="card rounded-0 shadow-none border mb-4"><div class="card-body">
        <form method="POST" action="{{ route('b2b.ai.trade-finance') }}">
            @csrf
            <div class="form-group">
                <label>{{ translate('Purchase Order') }}</label>
                <select class="form-control aiz-selectpicker" data-live-search="true" name="purchase_order_id" required>
                    @foreach($purchaseOrders as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}">{{ $purchaseOrder->po_number }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary rounded-0">{{ translate('Recommend Trade Finance') }}</button>
        </form>
    </div></div>
    @if ($recommendation)
        <div class="alert alert-success">{{ translate('Recommended Term') }}: {{ $recommendation->recommended_term }}<br>{{ $recommendation->explanation }}</div>
    @endif
    @include('b2b.ai.partials.history_table', ['title' => translate('Trade Finance History'), 'records' => $history, 'columns' => ['created_at' => 'Date', 'recommended_term' => 'Term', 'risk_score' => 'Risk', 'confidence_score' => 'Confidence']])
@endsection
