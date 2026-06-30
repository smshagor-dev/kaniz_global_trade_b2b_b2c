@extends('b2b.layouts.app')

@php
    $quoteRoute = $panelRole === 'supplier' ? 'seller.b2b.insurance.quotes.store' : 'b2b.insurance.quotes.store';
    $claimRoutePrefix = $panelRole === 'supplier' ? 'seller.b2b.insurance' : 'b2b.insurance';
    $insuranceTypes = $providerOptions['insurance_types'] ?? [];
    $claimDocumentTypes = $providerOptions['claim_document_types'] ?? [];
    $claimablePolicies = $policies->filter(fn ($policy) => in_array($policy->status, ['approved', 'active', 'in_transit', 'claim_submitted'], true));
@endphp

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="fs-20 fw-700 text-dark mb-1">{{ translate('Trade Insurance') }}</h1>
                <p class="text-muted mb-0">{{ $company->company_name }} · {{ ucfirst($panelRole) }} · {{ translate('Live insurance quotes, policies, and claims') }}</p>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ translate('Policies') }}</div>
                    <div class="fs-24 fw-700">{{ $stats['policies'] ?? $policies->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ translate('Claims') }}</div>
                    <div class="fs-24 fw-700">{{ $stats['claims'] ?? $claims->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ translate('Open Claims') }}</div>
                    <div class="fs-24 fw-700">{{ $stats['open_claims'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ translate('Coverage Value') }}</div>
                    <div class="fs-24 fw-700">{{ single_price($stats['coverage'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        @if ($canManageInsurance)
            <div class="col-lg-6">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ translate('Request Insurance Quote') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route($quoteRoute) }}" method="POST">
                            @csrf
                            <div class="row gutters-10">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>{{ translate('Insurance Type') }}</label>
                                        <select name="insurance_type" class="form-control aiz-selectpicker" data-live-search="true" required>
                                            @foreach ($insuranceTypes as $insuranceType)
                                                <option value="{{ $insuranceType }}">{{ ucwords(str_replace('_', ' ', $insuranceType)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>{{ translate('Provider') }}</label>
                                        <select name="provider_id" class="form-control aiz-selectpicker" data-live-search="true">
                                            <option value="">{{ translate('Auto assign default provider') }}</option>
                                            @foreach ($activeProviders as $provider)
                                                <option value="{{ $provider->id }}">{{ $provider->name }}{{ $provider->is_default ? ' (' . translate('Default') . ')' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>{{ translate('Shipment Value') }}</label>
                                        <input type="number" step="0.01" min="0.01" name="shipment_value" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>{{ translate('Coverage Amount') }}</label>
                                        <input type="number" step="0.01" min="0.01" name="coverage_amount" class="form-control">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>{{ translate('Currency') }}</label>
                                        <input type="text" name="currency" class="form-control" value="USD" maxlength="20" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>{{ translate('Transport Mode') }}</label>
                                        <input type="text" name="transport_mode" class="form-control" placeholder="sea_freight">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>{{ translate('Origin Country') }}</label>
                                        <input type="text" name="origin_country" class="form-control">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>{{ translate('Destination Country') }}</label>
                                        <input type="text" name="destination_country" class="form-control">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>{{ translate('Commodity') }}</label>
                                        <input type="text" name="commodity" class="form-control">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>{{ translate('Incoterm') }}</label>
                                        <input type="text" name="incoterm" class="form-control" placeholder="FOB">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ translate('Generate Quote') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if ($canSubmitInsuranceClaim)
            <div class="col-lg-6">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ translate('Submit Insurance Claim') }}</h5>
                    </div>
                    <div class="card-body">
                        @if ($claimablePolicies->count())
                            <form action="{{ route($claimRoutePrefix . '.claims.store', $claimablePolicies->first()->id) }}" method="POST" class="js-insurance-claim-form">
                                @csrf
                                <div class="form-group">
                                    <label>{{ translate('Policy') }}</label>
                                    <select name="policy_id" class="form-control js-claim-policy-selector" data-route-template="{{ route($claimRoutePrefix . '.claims.store', ['policyId' => '__POLICY__']) }}" required>
                                        @foreach ($claimablePolicies as $policy)
                                            <option value="{{ $policy->id }}">{{ $policy->policy_number }} · {{ ucfirst($policy->status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row gutters-10">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>{{ translate('Claim Type') }}</label>
                                            <input type="text" name="claim_type" class="form-control" value="damage" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>{{ translate('Claim Amount') }}</label>
                                            <input type="number" step="0.01" min="0.01" name="claim_amount" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label>{{ translate('Summary') }}</label>
                                            <input type="text" name="summary" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label>{{ translate('Description') }}</label>
                                            <textarea name="description" class="form-control" rows="4"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>{{ translate('Currency') }}</label>
                                            <input type="text" name="currency" class="form-control" value="USD" maxlength="20">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>{{ translate('Incident Date') }}</label>
                                            <input type="datetime-local" name="incident_at" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="border rounded p-3 mb-3">
                                    <div class="fw-600 mb-2">{{ translate('Required Documents') }}</div>
                                    @foreach (array_slice($claimDocumentTypes, 0, 2) as $index => $documentType)
                                        <div class="row gutters-10 mb-2">
                                            <div class="col-sm-4">
                                                <input type="text" class="form-control" value="{{ $documentType }}" readonly>
                                                <input type="hidden" name="documents[{{ $index }}][document_type]" value="{{ $documentType }}">
                                            </div>
                                            <div class="col-sm-8">
                                                <input type="text" name="documents[{{ $index }}][file_path]" class="form-control" placeholder="uploads/insurance/{{ $documentType }}.pdf" required>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="submit" class="btn btn-primary">{{ translate('Submit Claim') }}</button>
                            </form>
                        @else
                            <div class="text-muted">{{ translate('Only approved or active policies can be used to submit claims.') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ translate('Recent Quotes') }}</h5>
            <span class="badge badge-inline badge-soft-secondary">{{ $quotes->total() }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Quote') }}</th>
                            <th>{{ translate('Provider') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Coverage') }}</th>
                            <th>{{ translate('Final Amount') }}</th>
                            <th>{{ translate('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotes as $quote)
                            <tr>
                                <td>
                                    <div class="fw-600">{{ $quote->quote_number }}</div>
                                    <small class="text-muted">{{ $quote->created_at?->format('d M Y, h:i A') }}</small>
                                </td>
                                <td>{{ $quote->provider?->name ?? translate('Default Provider') }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $quote->insurance_type)) }}</td>
                                <td>{{ number_format((float) $quote->coverage_amount, 2) }} {{ $quote->currency }}</td>
                                <td>{{ number_format((float) $quote->final_amount, 2) }} {{ $quote->currency }}</td>
                                <td>
                                    <span class="badge badge-inline badge-soft-info">{{ ucfirst($quote->status) }}</span>
                                    @if ($quote->policy)
                                        <div><small class="text-muted">{{ translate('Policy issued') }}</small></div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ translate('No insurance quotes found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $quotes->links() }}</div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ translate('Policies') }}</h5>
            <span class="badge badge-inline badge-soft-secondary">{{ $policies->total() }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Policy') }}</th>
                            <th>{{ translate('Provider') }}</th>
                            <th>{{ translate('Coverage Plan') }}</th>
                            <th>{{ translate('Coverage Window') }}</th>
                            <th>{{ translate('Coverage Amount') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Export') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($policies as $policy)
                            <tr>
                                <td>{{ $policy->policy_number }}</td>
                                <td>{{ $policy->provider?->name ?? '-' }}</td>
                                <td>{{ $policy->coverage_plan ?: '-' }}</td>
                                <td>
                                    {{ $policy->coverage_start?->format('d M Y') ?? '-' }}
                                    -
                                    {{ $policy->coverage_end?->format('d M Y') ?? '-' }}
                                </td>
                                <td>{{ number_format((float) $policy->coverage_amount, 2) }} {{ $policy->currency }}</td>
                                <td>
                                    <span class="badge badge-inline badge-soft-success">{{ ucfirst(str_replace('_', ' ', $policy->status)) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route($claimRoutePrefix . '.policies.export', $policy->id) }}" class="btn btn-soft-primary btn-sm">
                                        {{ translate('PDF') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ translate('No insurance policies found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $policies->links() }}</div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ translate('Claims') }}</h5>
            <span class="badge badge-inline badge-soft-secondary">{{ $claims->total() }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Claim') }}</th>
                            <th>{{ translate('Policy') }}</th>
                            <th>{{ translate('Provider') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Documents') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Export') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($claims as $claim)
                            <tr>
                                <td>
                                    <div class="fw-600">{{ $claim->claim_number }}</div>
                                    <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $claim->claim_type)) }}</small>
                                </td>
                                <td>{{ $claim->policy?->policy_number ?? '-' }}</td>
                                <td>{{ $claim->provider?->name ?? '-' }}</td>
                                <td>{{ number_format((float) $claim->claim_amount, 2) }} {{ $claim->currency }}</td>
                                <td>{{ $claim->documents->count() }}</td>
                                <td><span class="badge badge-inline badge-soft-warning">{{ ucfirst(str_replace('_', ' ', $claim->status)) }}</span></td>
                                <td>
                                    <a href="{{ route($claimRoutePrefix . '.claims.export', $claim->id) }}" class="btn btn-soft-primary btn-sm">
                                        {{ translate('PDF') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ translate('No insurance claims found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">{{ $claims->links() }}</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var claimForm = document.querySelector('.js-insurance-claim-form');
            var policySelector = document.querySelector('.js-claim-policy-selector');

            if (!claimForm || !policySelector) {
                return;
            }

            var syncAction = function () {
                var template = policySelector.getAttribute('data-route-template') || '';
                claimForm.setAttribute('action', template.replace('__POLICY__', policySelector.value));
            };

            policySelector.addEventListener('change', syncAction);
            syncAction();
        })();
    </script>
@endpush
