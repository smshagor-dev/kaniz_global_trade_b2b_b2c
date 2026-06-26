@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('B2B RFQs') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                <form method="GET" action="{{ route('seller.b2b.rfqs.index') }}" class="d-inline-block">
                    <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="{{ translate('Search RFQs') }}">
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Title') }}</th>
                        <th>{{ translate('Buyer Location') }}</th>
                        <th>{{ translate('Product / Category') }}</th>
                        <th>{{ translate('Quantity') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Quotation') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rfqs as $rfq)
                        @php
                            $existingQuotation = $rfq->quotations->firstWhere('supplier_company_id', $supplierCompany->id);
                            $alreadyQuoted = !is_null($existingQuotation);
                        @endphp
                        <tr>
                            <td>{{ $rfq->title }}</td>
                            <td>
                                <div>{{ $rfq->destination_country ?? '-' }}</div>
                                <small class="text-muted">{{ $rfq->destination_city ?? '-' }}</small>
                            </td>
                            <td>
                                <div>{{ $rfq->product?->getTranslation('name') ?? '-' }}</div>
                                <small class="text-muted">{{ $rfq->category?->getTranslation('name') ?? '-' }}</small>
                            </td>
                            <td>{{ $rfq->quantity }} {{ $rfq->unit }}</td>
                            <td>
                                <span class="badge badge-inline badge-secondary">{{ ucfirst($rfq->status) }}</span>
                                @if ((int) $rfq->supplier_company_id === (int) $supplierCompany->id)
                                    <span class="badge badge-inline badge-info">{{ translate('Targeted') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($alreadyQuoted)
                                    <span class="badge badge-inline badge-success">{{ translate('Already Quoted') }}</span>
                                @else
                                    <span class="badge badge-inline badge-light">{{ translate('Not Quoted') }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($alreadyQuoted)
                                    <a href="{{ $existingQuotation->status === 'pending' ? route('seller.b2b.quotations.edit', $existingQuotation->id) : route('seller.b2b.quotations.show', $existingQuotation->id) }}" class="btn btn-soft-info btn-sm">
                                        {{ translate('View Quote') }}
                                    </a>
                                @elseif ($rfq->status === 'open')
                                    <a href="{{ route('seller.b2b.rfqs.quote', $rfq->id) }}" class="btn btn-soft-primary btn-sm">{{ translate('Submit Quote') }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ translate('No RFQs available') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">
                {{ $rfqs->links() }}
            </div>
        </div>
    </div>
@endsection
