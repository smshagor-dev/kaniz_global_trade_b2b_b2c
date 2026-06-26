@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <h1 class="h3">{{ translate('Negotiation Timeline') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">
            @foreach ($negotiation->messages as $message)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>{{ $message->sender?->name ?: ucfirst($message->sender_role) }}</strong>
                        <small class="text-muted">{{ $message->created_at->format('d M, Y h:i A') }}</small>
                    </div>
                    <div class="mb-2">
                        <span class="badge badge-inline badge-light">{{ ucfirst(str_replace('_', ' ', $message->message_type)) }}</span>
                    </div>
                    @if ($message->message)
                        <p class="mb-2">{{ $message->message }}</p>
                    @endif
                    @if ($message->attachment)
                        <a href="{{ asset($message->attachment) }}" target="_blank">{{ translate('Open Attachment') }}</a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection
