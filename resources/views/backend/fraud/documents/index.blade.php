@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mb-4"><h1 class="h3">{{ translate('Document Review') }}</h1></div>
<div class="card">
    <div class="table-responsive">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>{{ translate('User') }}</th>
                    <th>{{ translate('Type') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Uploaded') }}</th>
                    <th class="text-right">{{ translate('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr>
                        <td>{{ $document->user?->name }}<br><small>{{ $document->user?->email }}</small></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</td>
                        <td>{{ ucfirst($document->status) }}</td>
                        <td>{{ $document->created_at }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.fraud.documents.download', $document->id) }}" class="btn btn-soft-dark btn-sm">{{ translate('Download') }}</a>
                            <form action="{{ route('admin.fraud.documents.approve', $document->id) }}" method="POST" class="d-inline-block">@csrf<button class="btn btn-soft-success btn-sm">{{ translate('Approve') }}</button></form>
                            <form action="{{ route('admin.fraud.documents.reject', $document->id) }}" method="POST" class="d-inline-block">
                                @csrf
                                <input type="hidden" name="rejection_reason" value="Rejected during fraud review.">
                                <button class="btn btn-soft-danger btn-sm">{{ translate('Reject') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ translate('No documents found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $documents->links() }}</div>
</div>
@endsection
