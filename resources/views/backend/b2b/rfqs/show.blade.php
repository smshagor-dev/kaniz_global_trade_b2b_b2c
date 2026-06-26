@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('RFQ Details') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                @if (!in_array($rfq->status, ['closed', 'cancelled']))
                    <form action="{{ route('admin.b2b.rfqs.close', $rfq->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">{{ translate('Close RFQ') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Buyer') }}</div><div class="col-md-9">{{ $rfq->user?->name }} ({{ $rfq->user?->email }})</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Company') }}</div><div class="col-md-9">{{ $rfq->company?->company_name }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Title') }}</div><div class="col-md-9">{{ $rfq->title }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Description') }}</div><div class="col-md-9">{{ $rfq->description }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Product') }}</div><div class="col-md-9">{{ $rfq->product?->getTranslation('name') ?? '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Category') }}</div><div class="col-md-9">{{ $rfq->category?->getTranslation('name') ?? '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Quantity') }}</div><div class="col-md-9">{{ $rfq->quantity }} {{ $rfq->unit }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Status') }}</div><div class="col-md-9"><span class="badge badge-inline badge-secondary">{{ ucfirst($rfq->status) }}</span></div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Attachment') }}</div><div class="col-md-9">@if($rfq->attachment)<a href="{{ asset($rfq->attachment) }}" target="_blank">{{ translate('View attachment') }}</a>@else - @endif</div></div>
        </div>
    </div>

    <div class="row gutters-10 mb-4">
        <div class="col-md-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="fs-12 text-secondary">{{ translate('Total Quotations') }}</div>
                <div class="fs-20 fw-700">{{ $rfq->quotations->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="fs-12 text-secondary">{{ translate('Pending Quotations') }}</div>
                <div class="fs-20 fw-700">{{ $rfq->quotations->where('status', 'pending')->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="fs-12 text-secondary">{{ translate('Accepted Quotation') }}</div>
                <div class="fs-20 fw-700">{{ $rfq->quotations->where('status', 'accepted')->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-white border rounded p-3 h-100">
                <div class="fs-12 text-secondary">{{ translate('Rejected / Withdrawn') }}</div>
                <div class="fs-20 fw-700">{{ $rfq->quotations->whereIn('status', ['rejected', 'withdrawn'])->count() }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('Quotations') }}</h5>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Supplier') }}</th>
                        <th>{{ translate('Company') }}</th>
                        <th>{{ translate('Price') }}</th>
                        <th>{{ translate('MOQ') }}</th>
                        <th>{{ translate('Lead Time') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Message') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rfq->quotations as $quotation)
                        <tr>
                            <td>{{ $quotation->supplier?->name }}</td>
                            <td>{{ $quotation->supplierCompany?->company_name }}</td>
                            <td>{{ $quotation->price }} {{ $quotation->currency }}</td>
                            <td>{{ $quotation->moq ?: '-' }}</td>
                            <td>{{ $quotation->lead_time_days ? $quotation->lead_time_days . ' ' . translate('days') : '-' }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($quotation->status) }}</span></td>
                            <td>{{ $quotation->message ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">{{ translate('No quotations found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
