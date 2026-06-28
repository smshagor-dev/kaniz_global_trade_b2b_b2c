@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('My Quotations') }}</h1>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('RFQ') }}</th>
                        <th>{{ translate('Buyer Company') }}</th>
                        <th>{{ translate('Price') }}</th>
                        <th>{{ translate('Incoterm') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quotations as $quotation)
                        <tr>
                            <td>{{ $quotation->rfq?->title }}</td>
                            <td>{{ $quotation->rfq?->company?->company_name }}</td>
                            <td>{{ $quotation->price }} {{ $quotation->currency }}</td>
                            <td>{{ $quotation->incoterm ?: '-' }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($quotation->status) }}</span></td>
                            <td class="text-right">
                                <a href="{{ route('seller.b2b.quotations.show', $quotation->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
                                @if ($quotation->status === 'pending')
                                    <a href="{{ route('seller.b2b.quotations.edit', $quotation->id) }}" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('Edit') }}">
                                        <i class="las la-pen"></i>
                                    </a>
                                @endif
                                @if ($quotation->status !== 'accepted')
                                    <form action="{{ route('seller.b2b.quotations.withdraw', $quotation->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-soft-danger btn-icon btn-circle btn-sm" title="{{ translate('Withdraw') }}">
                                            <i class="las la-times"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No quotations found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">
                {{ $quotations->links() }}
            </div>
        </div>
    </div>
@endsection
