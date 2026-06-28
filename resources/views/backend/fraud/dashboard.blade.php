@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3">{{ translate('Fraud Dashboard') }}</h1>
        </div>
    </div>
</div>

<div class="row gutters-16">
    @foreach ($stats as $label => $value)
        <div class="col-md-3 mb-3">
            <div class="bg-white rounded border p-3 h-100">
                <div class="text-muted small text-uppercase">{{ str_replace('_', ' ', $label) }}</div>
                <div class="fs-24 fw-700">{{ $value }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ translate('Recently Flagged Users') }}</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('User') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Risk') }}</th>
                            <th>{{ translate('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentlyFlaggedUsers as $item)
                            <tr>
                                <td><a href="{{ route('admin.fraud.users.show', $item->user_id) }}">{{ $item->user?->name ?: 'User #' . $item->user_id }}</a></td>
                                <td>{{ ucfirst($item->user_type) }}</td>
                                <td><span class="badge badge-inline badge-warning">{{ ucfirst($item->risk_level) }}</span></td>
                                <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ translate('No flagged users found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ translate('Top Fraud Reasons') }}</h5></div>
            <div class="card-body">
                @forelse ($topReasons as $reason => $count)
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ $reason }}</span>
                        <strong>{{ $count }}</strong>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ translate('No fraud reasons recorded yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">{{ translate('Risk Trend') }}</h5></div>
    <div class="card-body">
        <div class="row">
            @foreach ($riskTrend as $point)
                <div class="col text-center">
                    <div class="fs-18 fw-700">{{ $point['count'] }}</div>
                    <div class="text-muted small">{{ $point['date'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
