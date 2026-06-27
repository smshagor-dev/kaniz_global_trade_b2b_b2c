@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3 mb-0">{{ translate('B2B Insurance Dashboard') }}</h1>
                <p class="text-muted mb-0">{{ translate('Global visibility for providers, policies, claims, and insurance collections.') }}</p>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['providers'] ?? $stats['insurance_providers'] ?? $providers->total() }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Providers') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['policies'] ?? $stats['insurance_policies'] ?? $policies->total() }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Policies') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $stats['claims'] ?? $stats['insurance_claims'] ?? $claims->total() }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Claims') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ single_price($stats['premium_revenue'] ?? $stats['insurance_premium_revenue'] ?? 0) }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Premium Revenue') }}</h3>
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
                            <th>{{ translate('Name') }}</th>
                            <th>{{ translate('Country') }}</th>
                            <th>{{ translate('Mode') }}</th>
                            <th>{{ translate('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($providers as $provider)
                            <tr>
                                <td>{{ $provider->name }}</td>
                                <td>{{ $provider->country ?: '-' }}</td>
                                <td>{{ ucfirst($provider->integration_mode ?: 'manual') }}</td>
                                <td>{{ $provider->is_active ? translate('Active') : translate('Inactive') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ translate('No providers found.') }}</td></tr>
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
