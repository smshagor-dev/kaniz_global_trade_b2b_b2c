@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3">{{ translate('Negotiation Timeline') }}</h1>
                <p class="mb-0 text-muted">{{ $negotiation->rfq?->title ?: ($negotiation->purchaseOrder?->po_number ?: '-') }}</p>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('seller.b2b.negotiations.index') }}" class="btn btn-soft-primary">{{ translate('Back') }}</a>
            </div>
        </div>
    </div>

    <div class="card mb-3">
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

    <div class="card">
        <div class="card-header">{{ translate('Send Message') }}</div>
        <div class="card-body">
            <form action="{{ route('seller.b2b.negotiations.messages.store', $negotiation->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <textarea name="message" class="form-control" rows="4" placeholder="{{ translate('Write your message') }}"></textarea>
                </div>
                <div class="form-group">
                    <input type="file" name="attachment" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">{{ translate('Send') }}</button>
            </form>
        </div>
    </div>
@endsection
