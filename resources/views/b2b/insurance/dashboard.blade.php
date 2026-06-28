@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark mb-1">{{ translate('Trade Insurance') }}</h1>
                <p class="text-muted mb-0">{{ $company->company_name }} Ã‚Â· {{ ucfirst($panelRole) }} Ã‚Â· {{ translate('Insurance operations and claim tracking') }}</p>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-md-4">
            <div class="card rounded-0 shadow-none border">
                <div class="card-body">
                    <div class="text-muted small">{{ translate('Policies') }}</div>
                    <div class="fs-24 fw-700">{{ $stats['total_policies'] ?? $stats['policies'] ?? $policies->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card rounded-0 shadow-none border">
                <div class="card-body">
                    <div class="text-muted small">{{ translate('Claims') }}</div>
                    <div class="fs-24 fw-700">{{ $stats['total_claims'] ?? $stats['claims'] ?? $claims->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card rounded-0 shadow-none border">
                <div class="card-body">
                    <div class="text-muted small">{{ translate('Open Claims') }}</div>
                    <div class="fs-24 fw-700">{{ $stats['open_claims'] ?? $stats['claims_open'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ translate('Recent Quotes') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Quote') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Coverage') }}</th>
                            <th>{{ translate('Final Amount') }}</th>
                            <th>{{ translate('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotes as $quote)
                            <tr>
                                <td>{{ $quote->quote_number }}</td>
                                <td>{{ $quote->insurance_type }}</td>
                                <td>{{ single_price($quote->coverage_amount) }}</td>
                                <td>{{ single_price($quote->final_amount) }}</td>
                                <td>{{ ucfirst($quote->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ translate('No insurance quotes found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ translate('Policies') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Policy') }}</th>
                            <th>{{ translate('Coverage Plan') }}</th>
                            <th>{{ translate('Coverage Amount') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Export') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($policies as $policy)
                            <tr>
                                <td>{{ $policy->policy_number }}</td>
                                <td>{{ $policy->coverage_plan ?: '-' }}</td>
                                <td>{{ single_price($policy->coverage_amount) }}</td>
                                <td>{{ ucfirst($policy->status) }}</td>
                                <td>
                                    <a href="{{ $panelRole === 'supplier' ? route('seller.b2b.insurance.policies.export', $policy->id) : route('b2b.insurance.policies.export', $policy->id) }}" class="btn btn-soft-primary btn-sm">
                                        {{ translate('PDF') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ translate('No insurance policies found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $policies->links() }}</div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ translate('Claims') }}</h5>
        </div>
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
                                <td>
                                    <a href="{{ $panelRole === 'supplier' ? route('seller.b2b.insurance.claims.export', $claim->id) : route('b2b.insurance.claims.export', $claim->id) }}" class="btn btn-soft-primary btn-sm">
                                        {{ translate('PDF') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ translate('No insurance claims found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $claims->links() }}</div>
        </div>
    </div>
@endsection
