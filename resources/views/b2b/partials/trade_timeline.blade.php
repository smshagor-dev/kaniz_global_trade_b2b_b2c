<div class="card rounded-0 shadow-none border mb-4">
    <div class="card-header">{{ translate('Trade Timeline') }}</div>
    <div class="card-body">
        @forelse ($timeline as $entry)
            <div class="d-flex mb-3">
                <div class="pr-3">
                    <span class="badge badge-inline badge-soft-primary">{{ $entry['label'] }}</span>
                </div>
                <div>
                    <div class="fw-600">{{ $entry['status'] }}</div>
                    <div class="text-muted small">{{ optional($entry['date'])->format('d M Y H:i') }}</div>
                    @if (!empty($entry['detail']))
                        <div class="small">{{ $entry['detail'] }}</div>
                    @endif
                </div>
            </div>
        @empty
            <p class="mb-0 text-muted">{{ translate('No timeline events available yet.') }}</p>
        @endforelse
    </div>
</div>
