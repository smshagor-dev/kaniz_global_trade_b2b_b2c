@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <h1 class="fs-20 fw-700 text-dark">{{ translate('HS Code Assistant') }}</h1>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('b2b.ai.hs-code.suggest') }}">
                @csrf
                <div class="form-group">
                    <label>{{ translate('Product Description') }}</label>
                    <textarea class="form-control rounded-0" name="query" rows="4" required>{{ old('query') }}</textarea>
                </div>
                <div class="form-group">
                    <label>{{ translate('Country') }}</label>
                    <input type="text" class="form-control rounded-0" name="country" value="{{ old('country', $company->country) }}">
                </div>
                <button type="submit" class="btn btn-primary rounded-0">{{ translate('Suggest HS Code') }}</button>
            </form>
        </div>
    </div>

    @if ($suggestion)
        <div class="card rounded-0 shadow-none border">
            <div class="card-header bg-white">
                <h5 class="mb-0">{{ translate('Suggestion') }}</h5>
            </div>
            <div class="card-body">
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Suggested HS Code') }}</div><div class="col-md-9">{{ $suggestion['suggested_hs_code'] ?: '-' }}</div></div>
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Description') }}</div><div class="col-md-9">{{ $suggestion['description'] ?? '-' }}</div></div>
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Confidence') }}</div><div class="col-md-9">{{ $suggestion['confidence'] ?? '-' }}</div></div>
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Restrictions') }}</div><div class="col-md-9">{{ $suggestion['restrictions'] ?? '-' }}</div></div>
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Required Documents') }}</div><div class="col-md-9">{{ collect($suggestion['required_documents'] ?? [])->implode(', ') ?: '-' }}</div></div>
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Source') }}</div><div class="col-md-9">{{ strtoupper($suggestion['source'] ?? 'database') }}</div></div>
                @if (!empty($suggestion['candidates']))
                    <div class="mt-4">
                        <h6>{{ translate('Top Candidates') }}</h6>
                        <ul class="mb-0 pl-3">
                            @foreach ($suggestion['candidates'] as $candidate)
                                <li>{{ $candidate['hs_code'] }} - {{ $candidate['description'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection
