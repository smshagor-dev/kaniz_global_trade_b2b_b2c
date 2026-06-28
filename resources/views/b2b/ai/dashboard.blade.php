@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark mb-1">{{ translate('AI Trade Desk') }}</h1>
                <p class="text-muted mb-0">{{ $company->company_name }} Ã‚Â· {{ ucfirst($company->company_type) }}</p>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                <div>
                    <div class="fw-700">{{ translate('Global B2B AI Flow Active') }}</div>
                    <div class="text-muted small">{{ translate('This AI flow is managed separately from B2B packages through Global B2B Config.') }}</div>
                </div>
                <div class="mt-3 mt-lg-0 text-lg-right">
                    <div class="small text-muted">{{ translate('Enabled Tools') }}</div>
                    <div class="fw-700">
                        {{ collect([
                            ($aiSettings['rfq_enabled'] ?? false) ? 'RFQ' : null,
                            ($aiSettings['product_description_enabled'] ?? false) ? 'Product Description' : null,
                            ($aiSettings['negotiation_enabled'] ?? false) ? 'Negotiation' : null,
                            ($aiSettings['translation_enabled'] ?? false) ? 'Translation' : null,
                        ])->filter()->implode(', ') ?: translate('No tools enabled') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('RFQ Assistant') }}</h5>
                    <p class="text-muted small">{{ translate('Draft stronger sourcing requests with structured AI suggestions.') }}</p>
                    <a href="{{ route('b2b.ai.rfq-assistant') }}" class="btn btn-primary rounded-0">{{ translate('Open RFQ Assistant') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('HS Code Assistant') }}</h5>
                    <p class="text-muted small">{{ translate('Get guided HS code suggestions using local trade data and AI ranking.') }}</p>
                    <a href="{{ route('b2b.ai.hs-code') }}" class="btn btn-primary rounded-0">{{ translate('Open HS Assistant') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('Trade Assistant') }}</h5>
                    <p class="text-muted small">{{ translate('Ask workflow questions without exposing private records outside your company access.') }}</p>
                    <a href="{{ route('b2b.ai.trade-assistant') }}" class="btn btn-primary rounded-0">{{ translate('Open Trade Assistant') }}</a>
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
                    <a href="{{ route('b2b.ai.price-recommendation') }}" class="btn btn-soft-primary rounded-0">{{ translate('Open') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('Supplier Risk') }}</h5>
                    <p class="small text-muted mb-2">{{ translate('Calculate supplier risk score and review explanations.') }}</p>
                    <div class="fw-700 mb-3">{{ optional($latest['supplier_risk'])->risk_score ?? '-' }}</div>
                    <a href="{{ route('b2b.ai.supplier-risk') }}" class="btn btn-soft-primary rounded-0">{{ translate('Open') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('Buyer Risk') }}</h5>
                    <p class="small text-muted mb-2">{{ translate('Review trust score for counterparties before offering terms.') }}</p>
                    <div class="fw-700 mb-3">{{ optional($latest['buyer_risk'])->trust_score ?? '-' }}</div>
                    <a href="{{ route('b2b.ai.buyer-risk') }}" class="btn btn-soft-primary rounded-0">{{ translate('Open') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('Freight Recommendation') }}</h5>
                    <p class="small text-muted mb-2">{{ translate('Compare mode, forwarder, cost, delay, and optimization strategy.') }}</p>
                    <div class="fw-700 mb-3">{{ optional($latest['freight'])->recommended_mode ?? '-' }}</div>
                    <a href="{{ route('b2b.ai.freight-recommendation') }}" class="btn btn-soft-primary rounded-0">{{ translate('Open') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('Currency Analysis') }}</h5>
                    <p class="small text-muted mb-2">{{ translate('Monitor FX exposure, volatility, and invoice-currency advice.') }}</p>
                    <div class="fw-700 mb-3">{{ optional($latest['currency'])->recommended_invoice_currency ?? '-' }}</div>
                    <a href="{{ route('b2b.ai.currency-analysis') }}" class="btn btn-soft-primary rounded-0">{{ translate('Open') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('Trade Finance') }}</h5>
                    <p class="small text-muted mb-2">{{ translate('Recommended payment structure from buyer, supplier, and amount risk.') }}</p>
                    <div class="fw-700 mb-3">{{ optional($latest['finance'])->recommended_term ?? '-' }}</div>
                    <a href="{{ route('b2b.ai.trade-finance') }}" class="btn btn-soft-primary rounded-0">{{ translate('Open') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('Trade Opportunities') }}</h5>
                    <p class="small text-muted mb-2">{{ translate('See market and supplier opportunities detected from marketplace activity.') }}</p>
                    <a href="{{ route('b2b.ai.opportunities') }}" class="btn btn-soft-primary rounded-0">{{ translate('Open') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('Dashboard Insights') }}</h5>
                    <p class="small text-muted mb-2">{{ translate('Executive summary covering revenue, RFQs, freight, shipments, and alerts.') }}</p>
                    <a href="{{ route('b2b.ai.dashboard-insights') }}" class="btn btn-soft-primary rounded-0">{{ translate('Open') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card rounded-0 shadow-none border h-100">
                <div class="card-body">
                    <h5 class="mb-2">{{ translate('AI Notifications') }}</h5>
                    <p class="small text-muted mb-2">{{ translate('Generate and review alerts for risk, FX, freight, and opportunities.') }}</p>
                    <a href="{{ route('b2b.ai.notifications') }}" class="btn btn-soft-primary rounded-0">{{ translate('Open') }}</a>
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
                </tbody>
            </table>
        </div>
    </div>
@endsection
