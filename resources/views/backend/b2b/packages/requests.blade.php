@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h3 class="h3">
                {{ !empty($forceSupplierFeaturedPackage) ? translate('Supplier Featured Package Requests') : translate('B2B Package Requests') }}
            </h3>
            @if (!empty($pageDescription))
                <p class="text-muted mb-0">{{ $pageDescription }}</p>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Company') }}</th>
                        <th>{{ translate('Package') }}</th>
                        <th>{{ translate('Requester') }}</th>
                        <th>{{ translate('Amount') }}</th>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('Payment Ref') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Note') }}</th>
                        <th class="text-right">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        <tr>
                            <td>{{ $request->company?->company_name ?: '-' }}</td>
                            <td>{{ $request->package?->name ?: '-' }}</td>
                            <td>{{ $request->requester?->name ?: '-' }}</td>
                            <td>{{ single_price($request->amount) }}</td>
                            <td>
                                {{ ($request->request_type ?? 'membership') === 'supplier_featured' ? translate('Supplier Featured') : translate('Membership') }}
                            </td>
                            <td>
                                <div>{{ $request->payment_reference ?: '-' }}</div>
                                @if ($request->payment_notes)
                                    <small class="text-muted">{{ $request->payment_notes }}</small>
                                @endif
                            </td>
                            <td>{{ ucfirst($request->status) }}</td>
                            <td>{{ $request->note ?: ($request->rejection_note ?: '-') }}</td>
                            <td class="text-right">
                                @if ($request->status === 'pending')
                                    <form action="{{ route($approveRoute ?? 'admin.b2b.package-requests.approve', $request->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button type="submit" class="btn btn-soft-success btn-sm">{{ translate('Approve') }}</button>
                                    </form>
                                    <form action="{{ route($rejectRoute ?? 'admin.b2b.package-requests.reject', $request->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <input type="hidden" name="rejection_note" value="{{ translate('Package request rejected by admin.') }}">
                                        <button type="submit" class="btn btn-soft-danger btn-sm">{{ translate('Reject') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">{{ translate('No package requests found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="aiz-pagination mt-4">{{ $requests->links() }}</div>
    </div>
</div>
@endsection
