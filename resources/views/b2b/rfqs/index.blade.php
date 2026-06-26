@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('My RFQs') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                <a href="{{ route('b2b.rfqs.create') }}" class="btn btn-primary rounded-0">{{ translate('Create RFQ') }}</a>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Title') }}</th>
                        <th>{{ translate('Product') }}</th>
                        <th>{{ translate('Quantity') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Quotations') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rfqs as $rfq)
                        <tr>
                            <td>{{ $rfq->title }}</td>
                            <td>{{ $rfq->product?->getTranslation('name') ?? '-' }}</td>
                            <td>{{ $rfq->quantity }} {{ $rfq->unit }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($rfq->status) }}</span></td>
                            <td>{{ $rfq->quotations->count() }}</td>
                            <td class="text-right">
                                <a href="{{ route('b2b.rfqs.show', $rfq->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
                                @if (!in_array($rfq->status, ['closed', 'cancelled']))
                                    <a href="{{ route('b2b.rfqs.edit', $rfq->id) }}" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('Edit') }}">
                                        <i class="las la-edit"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No RFQs found') }}</td>
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
