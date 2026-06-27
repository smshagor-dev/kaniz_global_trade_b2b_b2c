@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <h1 class="fs-20 fw-700 text-dark">{{ translate('Document Summary') }}</h1>
        <p class="text-muted mb-0">{{ strtoupper($type) }} #{{ $id }}</p>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <div class="row mb-3"><div class="col-md-3 text-secondary">{{ translate('Title') }}</div><div class="col-md-9">{{ $summary['title'] ?? '-' }}</div></div>
            <div class="row mb-3"><div class="col-md-3 text-secondary">{{ translate('Summary') }}</div><div class="col-md-9">{{ $summary['summary'] ?? '-' }}</div></div>
            <div class="row mb-3"><div class="col-md-3 text-secondary">{{ translate('Action Items') }}</div><div class="col-md-9">{{ collect($summary['action_items'] ?? [])->implode(', ') ?: '-' }}</div></div>
            <div class="row"><div class="col-md-3 text-secondary">{{ translate('Source') }}</div><div class="col-md-9">{{ strtoupper($summary['source'] ?? 'deterministic') }}</div></div>
        </div>
    </div>
@endsection
