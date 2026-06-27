@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4 d-flex justify-content-between align-items-center">
        <h1 class="fs-20 fw-700 text-dark mb-0">{{ translate('AI Notifications') }}</h1>
        <a href="{{ route('b2b.ai.notifications', ['refresh' => 1]) }}" class="btn btn-primary rounded-0">{{ translate('Generate Notifications') }}</a>
    </div>
    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead><tr><th>{{ translate('Date') }}</th><th>{{ translate('Severity') }}</th><th>{{ translate('Title') }}</th><th>{{ translate('Audience') }}</th></tr></thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr>
                            <td>{{ $notification->created_at }}</td>
                            <td>{{ ucfirst($notification->severity) }}</td>
                            <td><div>{{ $notification->title }}</div><small class="text-muted">{{ $notification->body }}</small></td>
                            <td>{{ ucfirst($notification->audience_type) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">{{ translate('No AI notifications yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-3">{{ $notifications->links() }}</div>
        </div>
    </div>
@endsection
