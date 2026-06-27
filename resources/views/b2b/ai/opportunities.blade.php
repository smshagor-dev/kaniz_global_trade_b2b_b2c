@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4 d-flex justify-content-between align-items-center">
        <h1 class="fs-20 fw-700 text-dark mb-0">{{ translate('Trade Opportunities') }}</h1>
        <div>
            <a href="{{ route('b2b.ai.opportunities', ['refresh' => 1]) }}" class="btn btn-primary rounded-0">{{ translate('Run Scan') }}</a>
            <a href="{{ route('b2b.ai.opportunities', ['queue' => 1]) }}" class="btn btn-soft-primary rounded-0">{{ translate('Queue Scan') }}</a>
        </div>
    </div>
    <div class="row">
        @forelse($opportunities as $opportunity)
            <div class="col-md-6 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ $opportunity->title }}</h5>
                        <p class="text-muted">{{ $opportunity->summary }}</p>
                        <div class="small">{{ translate('Type') }}: {{ $opportunity->opportunity_type }}</div>
                        <div class="small">{{ translate('Score') }}: {{ $opportunity->opportunity_score }}</div>
                        <div class="small">{{ translate('Confidence') }}: {{ $opportunity->confidence_score }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-secondary">{{ translate('No opportunities have been generated yet.') }}</div></div>
        @endforelse
    </div>
    <div class="aiz-pagination">{{ $opportunities->links() }}</div>
@endsection
