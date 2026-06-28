@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Negotiation Timeline') }}</h1>
                <p class="mb-0 text-muted">{{ $negotiation->rfq?->title ?: ($negotiation->purchaseOrder?->po_number ?: '-') }}</p>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('b2b.negotiations.index') }}" class="btn btn-soft-primary rounded-0">{{ translate('Back') }}</a>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border mb-3">
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

    <div class="card rounded-0 shadow-none border">
        <div class="card-header">{{ translate('Send Message') }}</div>
        <div class="card-body">
            <form action="{{ route('b2b.negotiations.messages.store', $negotiation->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <textarea name="message" class="form-control rounded-0" rows="4" placeholder="{{ translate('Write your message') }}"></textarea>
                </div>
                <div class="form-group">
                    <input type="file" name="attachment" class="form-control rounded-0">
                    <small class="text-muted">{{ translate('Allowed: image, pdf, doc, excel') }}</small>
                </div>
                <button type="submit" class="btn btn-primary rounded-0">{{ translate('Send') }}</button>
            </form>
        </div>
    </div>
@endsection
