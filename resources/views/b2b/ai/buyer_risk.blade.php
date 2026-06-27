@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4"><h1 class="fs-20 fw-700 text-dark">{{ translate('Buyer Risk Engine') }}</h1></div>
    <div class="card rounded-0 shadow-none border mb-4"><div class="card-body">
        <form method="POST" action="{{ route('b2b.ai.buyer-risk') }}">
            @csrf
            <div class="form-group">
                <label>{{ translate('Buyer Company') }}</label>
                <select class="form-control aiz-selectpicker" data-live-search="true" name="buyer_company_id" required>
                    @foreach($buyers as $buyer)
                        <option value="{{ $buyer->id }}">{{ $buyer->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary rounded-0">{{ translate('Assess Trust') }}</button>
        </form>
    </div></div>
    @if ($assessment)
        <div class="alert alert-info">{{ translate('Trust Score') }}: {{ $assessment->trust_score }} ({{ ucfirst($assessment->risk_level) }})<br>{{ $assessment->explanation }}</div>
    @endif
    @include('b2b.ai.partials.history_table', ['title' => translate('Buyer Risk History'), 'records' => $history, 'columns' => ['created_at' => 'Date', 'buyer_company_id' => 'Buyer ID', 'trust_score' => 'Trust', 'risk_level' => 'Level', 'confidence_score' => 'Confidence']])
@endsection
