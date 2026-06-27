@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('B2B Insurance Dashboard') }}</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row gutters-10 align-items-center">
                <div class="col-lg-8">
                    <h5 class="mb-1">{{ translate('Live Insurance Overview') }}</h5>
                    <p class="text-muted mb-0">{{ translate('This dashboard now shows only dynamic runtime data such as providers, logs, policies, and claims.') }}</p>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex flex-wrap justify-content-lg-end">
                        <a href="{{ route('admin.b2b.insurance.config') }}" class="btn btn-soft-success btn-sm mr-2 mb-2">{{ translate('Insurance Config') }}</a>
                        <a href="{{ route('admin.b2b.logistics-charge-settings.index') }}" class="btn btn-soft-info btn-sm mr-2 mb-2">{{ translate('Global B2B Config') }}</a>
                        <a href="{{ route('admin.b2b.trade-finance.dashboard') }}" class="btn btn-soft-warning btn-sm mr-2 mb-2">{{ translate('Trade Finance') }}</a>
                        <a href="{{ route('admin.b2b.dashboard') }}" class="btn btn-soft-primary btn-sm mb-2">{{ translate('B2B Dashboard') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['providers'] ?? $providers->total() }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Providers') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['active_providers'] ?? 0 }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Active Providers') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['policies'] ?? $policies->total() }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Policies') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['claims'] ?? $claims->total() }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Claims') }}</h3>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ translate('Providers') }}</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Provider') }}</th>
                            <th>{{ translate('Company') }}</th>
                            <th>{{ translate('Mode') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Last API') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($providers as $provider)
                            <tr>
                                <td>{{ $provider->name }}</td>
                                <td>{{ $provider->company ?: '-' }}</td>
                                <td>{{ ucfirst($provider->integration_mode ?: 'api') }}</td>
                                <td>
                                    <span class="badge badge-inline badge-{{ $provider->is_active ? 'info' : 'secondary' }}">{{ $provider->is_active ? translate('Active') : translate('Inactive') }}</span>
                                    @if ($provider->is_default)
                                        <span class="badge badge-inline badge-success">{{ translate('Default') }}</span>
                                    @endif
                                </td>
                                <td>{{ $provider->last_api_called_at ? $provider->last_api_called_at->format('d M Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ translate('No insurance providers found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-4">{{ $providers->links() }}</div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ translate('Recent API Logs') }}</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Provider') }}</th>
                            <th>{{ translate('Direction') }}</th>
                            <th>{{ translate('Endpoint') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('HTTP') }}</th>
                            <th>{{ translate('Latency') }}</th>
                            <th>{{ translate('Time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($apiLogs as $log)
                            <tr>
                                <td>{{ $log->provider?->name ?: '-' }}</td>
                                <td>{{ ucfirst($log->direction) }}</td>
                                <td><small>{{ \Illuminate\Support\Str::limit($log->endpoint, 60) ?: '-' }}</small></td>
                                <td>{{ ucfirst(str_replace('_', ' ', $log->status)) }}</td>
                                <td>{{ $log->http_status ?: '-' }}</td>
                                <td>{{ $log->latency_ms ? $log->latency_ms . ' ms' : '-' }}</td>
                                <td>{{ $log->created_at ? $log->created_at->format('d M Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">{{ translate('No insurance API logs found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ translate('Policies') }}</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Policy') }}</th>
                            <th>{{ translate('Coverage') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Export') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($policies as $policy)
                            <tr>
                                <td>{{ $policy->policy_number }}</td>
                                <td>{{ single_price($policy->coverage_amount) }}</td>
                                <td>{{ ucfirst($policy->status) }}</td>
                                <td><a href="{{ route('admin.b2b.insurance.policies.export', $policy->id) }}" class="btn btn-soft-primary btn-sm">{{ translate('PDF') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ translate('No policies found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ translate('Claims') }}</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Claim') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Export') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($claims as $claim)
                            <tr>
                                <td>{{ $claim->claim_number }}</td>
                                <td>{{ $claim->claim_type }}</td>
                                <td>{{ single_price($claim->claim_amount) }}</td>
                                <td>{{ ucfirst($claim->status) }}</td>
                                <td><a href="{{ route('admin.b2b.insurance.claims.export', $claim->id) }}" class="btn btn-soft-primary btn-sm">{{ translate('PDF') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ translate('No claims found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
