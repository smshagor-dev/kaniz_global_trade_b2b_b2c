@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('B2B Audit Logs') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Time') }}</th>
                        <th>{{ translate('Actor') }}</th>
                        <th>{{ translate('Company') }}</th>
                        <th>{{ translate('Event') }}</th>
                        <th>{{ translate('Target') }}</th>
                        <th>{{ translate('Description') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $auditLog)
                        <tr>
                            <td>{{ $auditLog->created_at->format('d M, Y h:i A') }}</td>
                            <td>{{ $auditLog->actor?->name ?: '-' }}</td>
                            <td>{{ $auditLog->actorCompany?->company_name ?: '-' }}</td>
                            <td>{{ $auditLog->event_type }}</td>
                            <td>{{ class_basename($auditLog->auditable_type) }} #{{ $auditLog->auditable_id }}</td>
                            <td>{{ $auditLog->description ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No audit logs found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">{{ $auditLogs->links() }}</div>
        </div>
    </div>
@endsection
