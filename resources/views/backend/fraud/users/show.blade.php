@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mb-4">
    <div class="row align-items-center">
        <div class="col"><h1 class="h3">{{ translate('Fraud Profile') }}</h1></div>
        <div class="col-auto"><a href="{{ route('admin.fraud.users') }}" class="btn btn-soft-primary">{{ translate('Back') }}</a></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ $user->name }} <small class="text-muted">{{ $user->email }}</small></h5></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3"><strong>{{ translate('Risk Score') }}</strong></div>
                    <div class="col-md-9">{{ $check->final_score ?? 0 }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>{{ translate('Risk Level') }}</strong></div>
                    <div class="col-md-9"><span class="badge badge-inline badge-warning">{{ ucfirst($check->risk_level ?? 'safe') }}</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>{{ translate('Status') }}</strong></div>
                    <div class="col-md-9">{{ ucfirst(str_replace('_', ' ', $check->status ?? 'pending')) }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>{{ translate('Summary') }}</strong></div>
                    <div class="col-md-9">{{ $check->summary ?: '-' }}</div>
                </div>
                <h6>{{ translate('Rule Reasons') }}</h6>
                <ul class="mb-0">
                    @forelse (($check->reasons ?? []) as $reason)
                        <li>{{ $reason['message'] ?? ($reason['code'] ?? '-') }} @if(isset($reason['score'])) ({{ $reason['score'] }}) @endif</li>
                    @empty
                        <li>{{ translate('No fraud reasons found.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ translate('Documents') }}</h5></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>{{ translate('Type') }}</th><th>{{ translate('Status') }}</th><th>{{ translate('Action') }}</th></tr></thead>
                    <tbody>
                        @forelse ($documents as $document)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</td>
                                <td>{{ ucfirst($document->status) }}</td>
                                <td>
                                    <a href="{{ route('admin.fraud.documents.download', $document->id) }}" class="btn btn-soft-dark btn-sm">{{ translate('Download') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">{{ translate('No documents found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ translate('Reports') }}</h5></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>{{ translate('Reporter') }}</th><th>{{ translate('Type') }}</th><th>{{ translate('Message') }}</th><th>{{ translate('Status') }}</th></tr></thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr>
                                <td>{{ $report->reporter?->name }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $report->report_type)) }}</td>
                                <td>{{ $report->message ?: '-' }}</td>
                                <td>{{ ucfirst($report->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ translate('No reports found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ translate('Actions') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.fraud.users.run-check', $user->id) }}" method="POST" class="mb-2">@csrf<button class="btn btn-primary btn-block">{{ translate('Run Check') }}</button></form>
                <form action="{{ route('admin.fraud.users.run-ai-check', $user->id) }}" method="POST" class="mb-2">@csrf<button class="btn btn-soft-info btn-block">{{ translate('Run AI Check') }}</button></form>
                <form action="{{ route('admin.fraud.users.approve', $user->id) }}" method="POST" class="mb-2">@csrf<button class="btn btn-soft-success btn-block">{{ translate('Approve User') }}</button></form>
                <form action="{{ route('admin.fraud.users.restrict', $user->id) }}" method="POST" class="mb-2">@csrf<button class="btn btn-soft-warning btn-block">{{ translate('Restrict User') }}</button></form>
                <form action="{{ route('admin.fraud.users.block', $user->id) }}" method="POST" class="mb-2">@csrf<button class="btn btn-soft-danger btn-block">{{ translate('Block User') }}</button></form>
                <form action="{{ route('admin.fraud.users.unblock', $user->id) }}" method="POST">@csrf<button class="btn btn-soft-secondary btn-block">{{ translate('Unblock User') }}</button></form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ translate('IP & Device Logs') }}</h5></div>
            <div class="card-body">
                @forelse ($deviceLogs as $log)
                    <div class="border rounded p-2 mb-2">
                        <div class="small"><strong>{{ $log->ip_address ?: '-' }}</strong></div>
                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($log->user_agent, 90) }}</div>
                        <div class="small text-muted">{{ optional($log->login_at)->format('d M Y H:i') }}</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ translate('No device logs found.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
