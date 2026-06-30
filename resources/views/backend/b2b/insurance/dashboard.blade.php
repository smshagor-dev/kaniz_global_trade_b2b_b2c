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
                    <p class="text-muted mb-0">{{ translate('This dashboard now shows only dynamic runtime data such as quotes, policies, claims, payments, providers, and logs.') }}</p>
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
        <div class="card-header"><h5 class="mb-0">{{ translate('Recent Quotes') }}</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Quote') }}</th>
                            <th>{{ translate('Provider') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Premium') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotes as $quote)
                            <tr>
                                <td>{{ $quote->quote_number }}</td>
                                <td>{{ $quote->provider?->name ?: '-' }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $quote->insurance_type)) }}</td>
                                <td>{{ number_format((float) $quote->final_amount, 2) }} {{ $quote->currency }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $quote->status)) }}</td>
                                <td>
                                    @if (!$quote->policy && $quote->status === 'quoted')
                                        <form action="{{ route('admin.b2b.insurance.policies.issue', $quote->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn btn-soft-success btn-sm">{{ translate('Issue Policy') }}</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">{{ $quote->policy ? translate('Policy Issued') : '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">{{ translate('No quotes found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-4">{{ $quotes->links() }}</div>
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
                                <td>{{ ucfirst(str_replace('_', ' ', $policy->status)) }}</td>
                                <td><a href="{{ route('admin.b2b.insurance.policies.export', $policy->id) }}" class="btn btn-soft-primary btn-sm">{{ translate('PDF') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ translate('No policies found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-4">{{ $policies->links() }}</div>
        </div>
    </div>

    <div class="card mb-4">
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
                                <td>{{ ucwords(str_replace('_', ' ', $claim->claim_type)) }}</td>
                                <td>{{ single_price($claim->claim_amount) }}</td>
                                <td>
                                    <div>{{ ucfirst(str_replace('_', ' ', $claim->status)) }}</div>
                                    @if (!in_array($claim->status, ['settled', 'rejected'], true))
                                        <form action="{{ route('admin.b2b.insurance.claims.status', $claim->id) }}" method="POST" class="mt-2">
                                            @csrf
                                            <div class="d-flex flex-wrap">
                                                <select name="status" class="form-control form-control-sm mr-2 mb-2" style="min-width: 160px;">
                                                    @foreach (\App\Models\B2BInsuranceClaim::STATUSES as $status)
                                                        <option value="{{ $status }}" @selected($claim->status === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-soft-success btn-sm mb-2">{{ translate('Update') }}</button>
                                            </div>
                                        </form>
                                    @endif
                                </td>
                                <td><a href="{{ route('admin.b2b.insurance.claims.export', $claim->id) }}" class="btn btn-soft-primary btn-sm">{{ translate('PDF') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ translate('No claims found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-4">{{ $claims->links() }}</div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ translate('Record Insurance Payment') }}</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.b2b.insurance.payments.store') }}" method="POST" class="js-insurance-payment-form">
                @csrf
                <input type="hidden" name="provider_id" class="js-payment-provider-id" value="">
                <input type="hidden" name="buyer_company_id" class="js-payment-buyer-company-id" value="">
                <input type="hidden" name="supplier_company_id" class="js-payment-supplier-company-id" value="">

                <div class="row gutters-10">
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Payment Type') }}</label>
                            <select name="payment_type" class="form-control aiz-selectpicker" required>
                                <option value="premium">{{ translate('Premium') }}</option>
                                <option value="claim_settlement">{{ translate('Claim Settlement') }}</option>
                                <option value="refund">{{ translate('Refund') }}</option>
                                <option value="adjustment">{{ translate('Adjustment') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Policy') }}</label>
                            <select name="policy_id" class="form-control aiz-selectpicker js-payment-policy" data-live-search="true">
                                <option value="">{{ translate('Select policy') }}</option>
                                @foreach ($policies as $policy)
                                    <option
                                        value="{{ $policy->id }}"
                                        data-provider-id="{{ $policy->provider_id }}"
                                        data-buyer-company-id="{{ $policy->buyer_company_id }}"
                                        data-supplier-company-id="{{ $policy->supplier_company_id }}"
                                        data-currency="{{ $policy->currency }}"
                                    >
                                        {{ $policy->policy_number }} - {{ ucfirst(str_replace('_', ' ', $policy->status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Claim') }}</label>
                            <select name="claim_id" class="form-control aiz-selectpicker js-payment-claim" data-live-search="true">
                                <option value="">{{ translate('Select claim') }}</option>
                                @foreach ($claims as $claim)
                                    <option
                                        value="{{ $claim->id }}"
                                        data-provider-id="{{ $claim->provider_id }}"
                                        data-buyer-company-id="{{ $claim->buyer_company_id }}"
                                        data-supplier-company-id="{{ $claim->supplier_company_id }}"
                                        data-policy-id="{{ $claim->policy_id }}"
                                        data-currency="{{ $claim->currency }}"
                                    >
                                        {{ $claim->claim_number }} - {{ ucfirst(str_replace('_', ' ', $claim->status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Payment Method') }}</label>
                            <input type="text" name="payment_method" class="form-control" placeholder="bank_transfer">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Reference') }}</label>
                            <input type="text" name="reference" class="form-control" placeholder="TXN-INS-001">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Amount') }}</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Tax') }}</label>
                            <input type="number" step="0.01" min="0" name="tax_amount" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Fees') }}</label>
                            <input type="number" step="0.01" min="0" name="fees" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Currency') }}</label>
                            <input type="text" name="currency" class="form-control js-payment-currency" value="USD" maxlength="20" required>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Status') }}</label>
                            <select name="status" class="form-control aiz-selectpicker">
                                <option value="paid">{{ translate('Paid') }}</option>
                                <option value="pending">{{ translate('Pending') }}</option>
                                <option value="failed">{{ translate('Failed') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Paid At') }}</label>
                            <input type="datetime-local" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d\\TH:i') }}">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">{{ translate('Record Payment') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ translate('Recent Payments') }}</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Provider') }}</th>
                            <th>{{ translate('Reference') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Paid At') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}</td>
                                <td>{{ $payment->provider?->name ?: '-' }}</td>
                                <td>{{ $payment->reference ?: ($payment->claim?->claim_number ?: $payment->policy?->policy_number ?: '-') }}</td>
                                <td>{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</td>
                                <td>{{ $payment->paid_at?->format('d M Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">{{ translate('No insurance payments found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-4">{{ $payments->links() }}</div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function () {
            var form = document.querySelector('.js-insurance-payment-form');

            if (!form) {
                return;
            }

            var policySelect = form.querySelector('.js-payment-policy');
            var claimSelect = form.querySelector('.js-payment-claim');
            var providerInput = form.querySelector('.js-payment-provider-id');
            var buyerCompanyInput = form.querySelector('.js-payment-buyer-company-id');
            var supplierCompanyInput = form.querySelector('.js-payment-supplier-company-id');
            var currencyInput = form.querySelector('.js-payment-currency');

            var syncFromOption = function (option) {
                if (!option || !option.value) {
                    return false;
                }

                providerInput.value = option.getAttribute('data-provider-id') || '';
                buyerCompanyInput.value = option.getAttribute('data-buyer-company-id') || '';
                supplierCompanyInput.value = option.getAttribute('data-supplier-company-id') || '';

                var currency = option.getAttribute('data-currency');
                if (currency) {
                    currencyInput.value = currency;
                }

                return true;
            };

            var syncFromPolicy = function () {
                var option = policySelect.options[policySelect.selectedIndex];
                syncFromOption(option);
            };

            var syncFromClaim = function () {
                var option = claimSelect.options[claimSelect.selectedIndex];

                if (!syncFromOption(option)) {
                    return;
                }

                var linkedPolicyId = option.getAttribute('data-policy-id');
                if (linkedPolicyId) {
                    policySelect.value = linkedPolicyId;
                    if (window.jQuery && window.jQuery(policySelect).selectpicker) {
                        window.jQuery(policySelect).selectpicker('refresh');
                    }
                }
            };

            claimSelect.addEventListener('change', syncFromClaim);
            policySelect.addEventListener('change', syncFromPolicy);
        })();
    </script>
@endsection
