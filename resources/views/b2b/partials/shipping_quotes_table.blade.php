<div class="card rounded-0 shadow-none border mb-4">
    <div class="card-header">{{ translate('Shipping Quotes') }}</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Quote') }}</th>
                        <th>{{ translate('Provider / Mode') }}</th>
                        <th>{{ translate('Route') }}</th>
                        <th>{{ translate('Incoterm') }}</th>
                        <th>{{ translate('ETA') }}</th>
                        <th>{{ translate('Subtotal') }}</th>
                        <th>{{ translate('Site Charge') }}</th>
                        <th>{{ translate('Total') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quotes as $quote)
                        <tr>
                            <td>{{ $quote->quote_number }}</td>
                            <td>
                                <div>{{ $quote->shippingProvider?->name ?: translate('Direct Supplier Quote') }}</div>
                                <small class="text-muted">{{ ucwords(str_replace('_', ' ', $quote->transport_mode)) }}</small>
                            </td>
                            <td>{{ $quote->origin_country }} → {{ $quote->destination_country }}</td>
                            <td>{{ $quote->incoterm ?: '-' }}</td>
                            <td>{{ $quote->estimated_days ? $quote->estimated_days . ' ' . translate('days') : '-' }}</td>
                            <td>{{ number_format((float) $quote->subtotal_cost, 2) }} {{ $quote->currency }}</td>
                            <td>{{ number_format((float) $quote->site_charge_amount, 2) }} {{ $quote->currency }}</td>
                            <td>{{ number_format((float) $quote->total_cost, 2) }} {{ $quote->currency }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($quote->status) }}</span></td>
                            <td class="text-right">
                                @if (!empty($allowSelect) && $quote->status !== 'selected')
                                    <form action="{{ route('b2b.shipping-quotes.select', $quote->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-soft-success btn-sm">{{ translate('Select') }}</button>
                                    </form>
                                @endif
                                @if ($quote->notes)
                                    <span class="d-block small text-muted mt-1">{{ \Illuminate\Support\Str::limit($quote->notes, 60) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">{{ translate('No shipping quotes available yet') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
