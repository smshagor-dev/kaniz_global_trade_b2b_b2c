@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <h1 class="fs-20 fw-700 text-dark">{{ translate('Company Invitation') }}</h1>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <h5 class="mb-3">{{ $invitation->company?->company_name }}</h5>
            <p><strong>{{ translate('Invited Email') }}:</strong> {{ $invitation->email }}</p>
            <p><strong>{{ translate('Role') }}:</strong> {{ ucwords(str_replace('_', ' ', $invitation->role)) }}</p>
            <p><strong>{{ translate('Status') }}:</strong> <span class="badge badge-inline badge-secondary">{{ ucfirst($status) }}</span></p>
            <div class="alert alert-info mb-0">{{ $message }}</div>
        </div>
    </div>
@endsection
