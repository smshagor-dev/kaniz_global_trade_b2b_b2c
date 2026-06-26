@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('B2B RFQs') }}</h1>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header row gutters-5">
            <div class="col-md-4">
                <form method="GET" action="{{ route('admin.b2b.rfqs.index') }}">
                    <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="{{ translate('Search RFQ, buyer or company') }}">
                </form>
            </div>
            <div class="col-md-3">
                <form method="GET" action="{{ route('admin.b2b.rfqs.index') }}">
                    <select class="form-control aiz-selectpicker" name="status" onchange="this.form.submit()">
                        <option value="">{{ translate('All Statuses') }}</option>
                        @foreach (['open', 'quoted', 'closed', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Title') }}</th>
                        <th>{{ translate('Buyer') }}</th>
                        <th>{{ translate('Company') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Quotations') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rfqs as $rfq)
                        <tr>
                            <td>{{ $rfq->title }}</td>
                            <td>{{ $rfq->user?->name }}</td>
                            <td>{{ $rfq->company?->company_name }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($rfq->status) }}</span></td>
                            <td>{{ $rfq->quotations_count ?? $rfq->quotations?->count() ?? 0 }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.b2b.rfqs.show', $rfq->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
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
