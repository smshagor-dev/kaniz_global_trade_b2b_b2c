@extends('backend.layouts.app')

@section('content')
<div class="row pt-4">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ translate('Enterprise Search Settings') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.search.settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>{{ translate('Active Provider') }}</label>
                        <select class="form-control aiz-selectpicker" name="search_provider">
                            @foreach ($providers as $item)
                                <option value="{{ $item }}" @selected($provider === $item)>{{ ucfirst($item) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Index Name') }}</label>
                        <input type="text" class="form-control" name="search_index_name" value="{{ $indexName }}">
                    </div>
                    <button type="submit" class="btn btn-primary">{{ translate('Save Settings') }}</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ translate('Index Management') }}</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.search.reindex') }}" method="POST" class="mb-3">
                    @csrf
                    <div class="form-group">
                        <label>{{ translate('Entity') }}</label>
                        <select class="form-control aiz-selectpicker" name="entity">
                            @foreach ($entityOptions as $entityOption)
                                <option value="{{ $entityOption }}" @selected($entityOption === 'all')>{{ ucfirst(str_replace('_', ' ', $entityOption)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Single Model ID') }}</label>
                        <input type="number" class="form-control" name="id" placeholder="{{ translate('Optional') }}">
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Chunk Size') }}</label>
                        <input type="number" class="form-control" name="chunk" value="100" min="1" max="1000">
                    </div>
                    <div class="form-group">
                        <label class="aiz-switch aiz-switch-success mb-0"><input type="checkbox" name="queue" value="1" checked><span></span></label>
                        <span class="ml-2">{{ translate('Queue indexing jobs') }}</span>
                    </div>
                    <div class="form-group">
                        <label class="aiz-switch aiz-switch-success mb-0"><input type="checkbox" name="dry_run" value="1"><span></span></label>
                        <span class="ml-2">{{ translate('Dry run only') }}</span>
                    </div>
                    <div class="form-group">
                        <label class="aiz-switch aiz-switch-success mb-0"><input type="checkbox" name="resume" value="1"><span></span></label>
                        <span class="ml-2">{{ translate('Resume latest incomplete run') }}</span>
                    </div>
                    <div class="form-group">
                        <label>{{ translate('Specific Run ID') }}</label>
                        <input type="number" class="form-control" name="run" placeholder="{{ translate('Optional') }}">
                    </div>
                    <button type="submit" class="btn btn-primary">{{ translate('Run Reindex') }}</button>
                </form>

                <form action="{{ route('admin.search.retry-failures') }}" method="POST">
                    @csrf
                    <input type="hidden" name="run" value="{{ optional($latestRun)->id }}">
                    <button type="submit" class="btn btn-soft-warning">{{ translate('Retry Failed Jobs') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row">
            <div class="col-md-4"><div class="card mb-4"><div class="card-body"><h6 class="text-muted mb-2">{{ translate('Provider Health') }}</h6><h3 class="mb-1">{{ ($health['ok'] ?? false) ? translate('Healthy') : translate('Fallback Active') }}</h3><small>{{ ($health['provider'] ?? $provider) }} | {{ $indexName }}</small></div></div></div>
            <div class="col-md-4"><div class="card mb-4"><div class="card-body"><h6 class="text-muted mb-2">{{ translate('Indexed Documents') }}</h6><h3 class="mb-1">{{ $documentCounts->sum('total') }}</h3><small>{{ translate('Across all entities') }}</small></div></div></div>
            <div class="col-md-4"><div class="card mb-4"><div class="card-body"><h6 class="text-muted mb-2">{{ translate('Open Failures') }}</h6><h3 class="mb-1">{{ $failures->total() }}</h3><small>{{ translate('Needs retry or inspection') }}</small></div></div></div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ translate('Last Reindex Run') }}</h5></div>
            <div class="card-body">
                @if ($latestRun)
                    <div class="row">
                        <div class="col-md-3"><strong>{{ translate('Run ID') }}:</strong> #{{ $latestRun->id }}</div>
                        <div class="col-md-3"><strong>{{ translate('Status') }}:</strong> {{ ucfirst(str_replace('_', ' ', $latestRun->status)) }}</div>
                        <div class="col-md-3"><strong>{{ translate('Indexed Count') }}:</strong> {{ max($latestRun->processed_models - $latestRun->failed_models, 0) }}</div>
                        <div class="col-md-3"><strong>{{ translate('Failed Count') }}:</strong> {{ $latestRun->failed_models }}</div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3"><strong>{{ translate('Entity') }}:</strong> {{ ucfirst(str_replace('_', ' ', $latestRun->entity)) }}</div>
                        <div class="col-md-3"><strong>{{ translate('Chunk Size') }}:</strong> {{ $latestRun->chunk_size }}</div>
                        <div class="col-md-3"><strong>{{ translate('Queued Chunks') }}:</strong> {{ $latestRun->queued_chunks }}</div>
                        <div class="col-md-3"><strong>{{ translate('Started') }}:</strong> {{ optional($latestRun->started_at)->diffForHumans() }}</div>
                    </div>
                @else
                    <p class="mb-0">{{ translate('No reindex runs recorded yet.') }}</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ translate('Indexed Entity Breakdown') }}</h5>
                <a href="{{ route('admin.search.analytics') }}" class="btn btn-soft-primary btn-sm">{{ translate('View Analytics') }}</a>
            </div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('Entity') }}</th><th>{{ translate('Documents') }}</th></tr></thead>
                    <tbody>
                        @foreach ($documentCounts as $row)
                            <tr><td>{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $row->type)) }}</td><td>{{ $row->total }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ translate('Indexing Failures') }}</h5></div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('Entity') }}</th><th>{{ translate('Operation') }}</th><th>{{ translate('Provider') }}</th><th>{{ translate('Message') }}</th><th>{{ translate('Failed At') }}</th></tr></thead>
                    <tbody>
                        @forelse ($failures as $failure)
                            <tr>
                                <td>{{ class_basename($failure->model_type) }} #{{ $failure->model_id }}</td>
                                <td>{{ $failure->operation }}</td>
                                <td>{{ $failure->provider }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($failure->message, 100) }}</td>
                                <td>{{ optional($failure->failed_at)->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">{{ translate('No indexing failures found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="aiz-pagination mt-4">{{ $failures->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
