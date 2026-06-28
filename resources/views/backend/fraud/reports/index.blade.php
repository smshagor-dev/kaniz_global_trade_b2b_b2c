@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mb-4"><h1 class="h3">{{ translate('Reported Users') }}</h1></div>
<div class="card">
    <div class="table-responsive">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>{{ translate('Reported User') }}</th>
                    <th>{{ translate('Reporter') }}</th>
                    <th>{{ translate('Type') }}</th>
                    <th>{{ translate('Message') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th class="text-right">{{ translate('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>{{ $report->reportedUser?->name }}</td>
                        <td>{{ $report->reporter?->name }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $report->report_type)) }}</td>
                        <td>{{ $report->message ?: '-' }}</td>
                        <td>{{ ucfirst($report->status) }}</td>
                        <td class="text-right">
                            <form action="{{ route('admin.fraud.reports.resolve', $report->id) }}" method="POST" class="d-inline-block">
                                @csrf
                                <input type="hidden" name="status" value="resolved">
                                <button class="btn btn-soft-success btn-sm">{{ translate('Resolve') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ translate('No reports found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $reports->links() }}</div>
</div>
@endsection
