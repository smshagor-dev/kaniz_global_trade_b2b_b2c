@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark mb-1">{{ translate('AI Trade Desk') }}</h1>
                <p class="text-muted mb-0">{{ $company->company_name }} | {{ ucfirst($company->company_type) }}</p>
            </div>
        </div>
    </div>

    @if (!$aiSettings['enabled'])
        <div class="alert alert-warning mb-4">
            {{ translate('B2B AI tools are currently disabled in Global B2B Config.') }}
        </div>
    @endif

    @php
        $aiAction = function (string $route) use ($hasAiAccess) {
            return $hasAiAccess ? route($route) : 'javascript:void(0)';
        };
    @endphp

    @if ($aiSettings['enabled'])
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ translate('RFQ Assistant') }}</h5>
                        <p class="text-muted small">{{ translate('Draft stronger sourcing requests with structured AI suggestions.') }}</p>
                        <a href="{{ $aiAction('b2b.ai.rfq-assistant') }}" class="btn btn-primary rounded-0" @unless($hasAiAccess) onclick="openAiTradeDeskPaymentModal()" @endunless>{{ translate('Open RFQ Assistant') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ translate('HS Code Assistant') }}</h5>
                        <p class="text-muted small">{{ translate('Get guided HS code suggestions using local trade data and AI ranking.') }}</p>
                        <a href="{{ $aiAction('b2b.ai.hs-code') }}" class="btn btn-primary rounded-0" @unless($hasAiAccess) onclick="openAiTradeDeskPaymentModal()" @endunless>{{ translate('Open HS Assistant') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ translate('Trade Assistant') }}</h5>
                        <p class="text-muted small">{{ translate('Ask workflow questions without exposing private records outside your company access.') }}</p>
                        <a href="{{ $aiAction('b2b.ai.trade-assistant') }}" class="btn btn-primary rounded-0" @unless($hasAiAccess) onclick="openAiTradeDeskPaymentModal()" @endunless>{{ translate('Open Trade Assistant') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ translate('Price Recommendation') }}</h5>
                        <p class="small text-muted mb-2">{{ translate('Suggested selling, wholesale, distributor, and export pricing.') }}</p>
                        <div class="fw-700 mb-3">{{ optional($latest['price'])->selling_price ? $latest['price']->selling_price . ' ' . $latest['price']->currency : '-' }}</div>
                        <a href="{{ $aiAction('b2b.ai.price-recommendation') }}" class="btn btn-soft-primary rounded-0" @unless($hasAiAccess) onclick="openAiTradeDeskPaymentModal()" @endunless>{{ translate('Open') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ translate('Freight Recommendation') }}</h5>
                        <p class="small text-muted mb-2">{{ translate('Compare mode, forwarder, cost, delay, and optimization strategy.') }}</p>
                        <div class="fw-700 mb-3">{{ optional($latest['freight'])->recommended_mode ?? '-' }}</div>
                        <a href="{{ $aiAction('b2b.ai.freight-recommendation') }}" class="btn btn-soft-primary rounded-0" @unless($hasAiAccess) onclick="openAiTradeDeskPaymentModal()" @endunless>{{ translate('Open') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ translate('Currency Analysis') }}</h5>
                        <p class="small text-muted mb-2">{{ translate('Monitor FX exposure, volatility, and invoice-currency advice.') }}</p>
                        <div class="fw-700 mb-3">{{ optional($latest['currency'])->recommended_invoice_currency ?? '-' }}</div>
                        <a href="{{ $aiAction('b2b.ai.currency-analysis') }}" class="btn btn-soft-primary rounded-0" @unless($hasAiAccess) onclick="openAiTradeDeskPaymentModal()" @endunless>{{ translate('Open') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ translate('Trade Finance') }}</h5>
                        <p class="small text-muted mb-2">{{ translate('Recommended payment structure from buyer, supplier, and amount risk.') }}</p>
                        <div class="fw-700 mb-3">{{ optional($latest['finance'])->recommended_term ?? '-' }}</div>
                        <a href="{{ $aiAction('b2b.ai.trade-finance') }}" class="btn btn-soft-primary rounded-0" @unless($hasAiAccess) onclick="openAiTradeDeskPaymentModal()" @endunless>{{ translate('Open') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ translate('Trade Opportunities') }}</h5>
                        <p class="small text-muted mb-2">{{ translate('See market and supplier opportunities detected from marketplace activity.') }}</p>
                        <a href="{{ $aiAction('b2b.ai.opportunities') }}" class="btn btn-soft-primary rounded-0" @unless($hasAiAccess) onclick="openAiTradeDeskPaymentModal()" @endunless>{{ translate('Open') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ translate('Dashboard Insights') }}</h5>
                        <p class="small text-muted mb-2">{{ translate('Executive summary covering revenue, RFQs, freight, shipments, and alerts.') }}</p>
                        <a href="{{ $aiAction('b2b.ai.dashboard-insights') }}" class="btn btn-soft-primary rounded-0" @unless($hasAiAccess) onclick="openAiTradeDeskPaymentModal()" @endunless>{{ translate('Open') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card rounded-0 shadow-none border h-100">
                    <div class="card-body">
                        <h5 class="mb-2">{{ translate('AI Notifications') }}</h5>
                        <p class="small text-muted mb-2">{{ translate('Generate and review alerts for risk, FX, freight, and opportunities.') }}</p>
                        <a href="{{ $aiAction('b2b.ai.notifications') }}" class="btn btn-soft-primary rounded-0" @unless($hasAiAccess) onclick="openAiTradeDeskPaymentModal()" @endunless>{{ translate('Open') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card rounded-0 shadow-none border">
            <div class="card-header bg-white">
                <h5 class="mb-0">{{ translate('Recent AI Activity') }}</h5>
            </div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Module') }}</th>
                            <th>{{ translate('Provider') }}</th>
                            <th>{{ translate('Model') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Tokens') }}</th>
                            <th>{{ translate('Time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!$hasAiAccess)
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ translate('Pay for AI Trade Desk access to start using these modules and view activity history.') }}</td>
                            </tr>
                        @else
                            @forelse ($recentRequests as $request)
                                <tr>
                                    <td>{{ $request->module }}</td>
                                    <td>{{ strtoupper($request->provider) }}</td>
                                    <td>{{ $request->model }}</td>
                                    <td><span class="badge badge-inline badge-{{ $request->status === 'success' ? 'success' : 'warning' }}">{{ ucfirst($request->status) }}</span></td>
                                    <td>{{ $request->total_tokens ?: '-' }}</td>
                                    <td>{{ $request->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">{{ translate('No B2B AI requests yet.') }}</td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@section('modal')
    <div class="modal fade" id="ai_trade_desk_payment_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Pay For AI Trade Desk Access') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="ai_trade_desk_payment_form" method="POST" action="{{ route('b2b.ai.purchase-access') }}">
                    @csrf
                    <div class="modal-body" style="overflow-y: unset;">
                        <div class="mb-3">
                            <div class="text-muted small">{{ translate('Payable Amount') }}</div>
                            <div class="fw-700 fs-18">{{ single_price($aiSettings['global_price'] ?? 0) }}</div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <label>{{ translate('Payment Method') }}</label>
                            </div>
                            <div class="col-md-9">
                                <div class="mb-3">
                                    <select class="form-control aiz-selectpicker" data-live-search="true" name="payment_option" required>
                                        @include('partials.online_payment_options')
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-right mb-0">
                            <button type="button" class="btn btn-sm btn-secondary mr-1" data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Confirm Payment') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function openAiTradeDeskPaymentModal() {
            $('#ai_trade_desk_payment_modal').modal('show');
        }
    </script>
@endsection
