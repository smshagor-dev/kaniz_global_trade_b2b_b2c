@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3"><h1 class="h3">{{ translate('Freight Quotes') }}</h1></div>
    @include('b2b.partials.freight_quote_filters')
    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead><tr><th>{{ translate('Quote') }}</th><th>{{ translate('Route') }}</th><th>{{ translate('Forwarder') }}</th><th>{{ translate('Cost') }}</th><th>{{ translate('Status') }}</th><th class="text-right">{{ translate('Options') }}</th></tr></thead>
                <tbody>
                    @forelse ($quotes as $quote)
                        <tr>
                            <td><div class="fw-600">{{ $quote->quote_number }}</div><div class="small text-muted">{{ $quote->buyerCompany?->company_name }} / {{ $quote->supplierCompany?->company_name ?: '-' }}</div></td>
                            <td>{{ $quote->origin_country }} to {{ $quote->destination_country }}</td>
                            <td>{{ $quote->forwarder?->name ?: translate('Manual / Rule Based') }}</td>
                            <td>{{ single_price($quote->total_cost_base_currency ?: $quote->total_cost) }} {{ $quote->base_currency ?: $quote->currency }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucwords(str_replace('_', ' ', $quote->status)) }}</span></td>
                            <td class="text-right"><a href="{{ route('admin.b2b.freight-quotes.show', $quote->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm"><i class="las la-eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ translate('No freight quotes found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">{{ $quotes->links() }}</div>
        </div>
    </div>
@endsection
