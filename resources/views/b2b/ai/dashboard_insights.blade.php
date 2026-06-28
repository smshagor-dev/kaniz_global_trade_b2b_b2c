@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4 d-flex justify-content-between align-items-center">
        <h1 class="fs-20 fw-700 text-dark mb-0">{{ translate('AI Dashboard Insights') }}</h1>
        <div>
            <a href="{{ route('b2b.ai.dashboard-insights', ['refresh' => 1]) }}" class="btn btn-primary rounded-0">{{ translate('Generate Now') }}</a>
            <a href="{{ route('b2b.ai.dashboard-insights', ['queue' => 1]) }}" class="btn btn-soft-primary rounded-0">{{ translate('Queue Job') }}</a>
        </div>
    </div>
    @forelse($insights as $insight)
        <div class="card rounded-0 shadow-none border mb-3">
            <div class="card-body">
                <h5 class="mb-2">{{ $insight->title }}</h5>
                <p class="mb-2">{{ $insight->summary }}</p>
                <div class="row">
                    @foreach(($insight->insights ?? []) as $label => $value)
                        <div class="col-md-3 mb-2"><div class="border p-2 h-100"><small class="text-muted d-block">{{ ucwords(str_replace('_', ' ', $label)) }}</small><strong>{{ is_array($value) ? json_encode($value) : $value }}</strong></div></div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-secondary">{{ translate('No dashboard insights generated yet.') }}</div>
    @endforelse
    <div class="aiz-pagination">{{ $insights->links() }}</div>
@endsection
