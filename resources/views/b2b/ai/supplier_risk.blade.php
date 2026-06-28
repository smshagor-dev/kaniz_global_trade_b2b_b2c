@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4"><h1 class="fs-20 fw-700 text-dark">{{ translate('Supplier Risk Engine') }}</h1></div>
    <div class="card rounded-0 shadow-none border mb-4"><div class="card-body">
        <form method="POST" action="{{ route('b2b.ai.supplier-risk') }}">
            @csrf
            <div class="form-group">
                <label>{{ translate('Supplier') }}</label>
                <select class="form-control aiz-selectpicker" data-live-search="true" name="supplier_company_id" required>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary rounded-0">{{ translate('Assess Risk') }}</button>
        </form>
    </div></div>
    @if ($assessment)
        <div class="alert alert-warning">{{ translate('Risk Score') }}: {{ $assessment->risk_score }} ({{ ucfirst($assessment->risk_level) }})<br>{{ $assessment->explanation }}</div>
    @endif
    @include('b2b.ai.partials.history_table', ['title' => translate('Risk History'), 'records' => $history, 'columns' => ['created_at' => 'Date', 'supplier_company_id' => 'Supplier ID', 'risk_score' => 'Score', 'risk_level' => 'Level', 'confidence_score' => 'Confidence']])
@endsection
