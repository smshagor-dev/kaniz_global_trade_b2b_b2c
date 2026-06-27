@extends('backend.layouts.app')

@section('content')
<div class="row pt-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h5 mb-0">{{ translate('AI Commercial Intelligence') }}</h1>
            <div>
                <a href="{{ route('ai-commercial-price') }}" class="btn btn-soft-primary btn-sm">{{ translate('Price') }}</a>
                <a href="{{ route('ai-commercial-supplier-risk') }}" class="btn btn-soft-warning btn-sm">{{ translate('Risk') }}</a>
                <a href="{{ route('ai-commercial-freight') }}" class="btn btn-soft-info btn-sm">{{ translate('Freight') }}</a>
            </div>
        </div>
    </div>
    @foreach($summary as $key => $value)
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted d-block">{{ ucwords(str_replace('_', ' ', $key)) }}</small>
                    <h3 class="mb-0">{{ $value }}</h3>
                </div>
            </div>
        </div>
    @endforeach
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Commercial Activity Trend') }}</h5></div>
            <div class="card-body">
                <canvas id="commercialTrendChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Latest Dashboard Insights') }}</h5></div>
            <div class="card-body">
                @forelse($latestInsights as $insight)
                    <div class="border rounded p-3 mb-3">
                        <strong>{{ $insight->title }}</strong>
                        <p class="mb-0 small text-muted">{{ $insight->summary }}</p>
                    </div>
                @empty
                    <p class="mb-0 text-muted">{{ translate('No insights generated yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        const ctx = document.getElementById('commercialTrendChart');
        if (!ctx || typeof Chart === 'undefined') {
            return;
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chart['labels']),
                datasets: [
                    { label: 'Price', data: @json($chart['price']), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', tension: .3, fill: true },
                    { label: 'Risk', data: @json($chart['risk']), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.08)', tension: .3, fill: true },
                    { label: 'Freight', data: @json($chart['freight']), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.08)', tension: .3, fill: true }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    })();
</script>
@endsection
