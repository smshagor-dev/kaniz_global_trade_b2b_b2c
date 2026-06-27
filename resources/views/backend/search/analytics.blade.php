@extends('backend.layouts.app')

@section('content')
<div class="row pt-4">
    <div class="col-md-3"><div class="card mb-4"><div class="card-body"><h6 class="text-muted">{{ translate('Searches') }}</h6><h3>{{ $summary['searches'] }}</h3></div></div></div>
    <div class="col-md-3"><div class="card mb-4"><div class="card-body"><h6 class="text-muted">{{ translate('CTR') }}</h6><h3>{{ $summary['ctr'] }}%</h3></div></div></div>
    <div class="col-md-3"><div class="card mb-4"><div class="card-body"><h6 class="text-muted">{{ translate('Conversion') }}</h6><h3>{{ $summary['conversion_rate'] }}%</h3></div></div></div>
    <div class="col-md-3"><div class="card mb-4"><div class="card-body"><h6 class="text-muted">{{ translate('Abandonment') }}</h6><h3>{{ $summary['abandonment_rate'] }}%</h3></div></div></div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ translate('Popular Searches') }}</h5></div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('Keyword') }}</th><th>{{ translate('Count') }}</th></tr></thead>
                    <tbody>
                        @foreach ($summary['popular_searches'] as $row)
                            <tr><td>{{ $row->query }}</td><td>{{ $row->total }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ translate('Zero Result Searches') }}</h5></div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('Keyword') }}</th><th>{{ translate('Count') }}</th></tr></thead>
                    <tbody>
                        @foreach ($summary['zero_result_searches'] as $row)
                            <tr><td>{{ $row->query }}</td><td>{{ $row->total }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ translate('Top Filters') }}</h5></div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('Filter') }}</th><th>{{ translate('Count') }}</th></tr></thead>
                    <tbody>
                        @foreach ($summary['top_filters'] as $filter => $count)
                            <tr><td>{{ $filter }}</td><td>{{ $count }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ translate('Recent Index Failures') }}</h5></div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead><tr><th>{{ translate('Entity') }}</th><th>{{ translate('Provider') }}</th><th>{{ translate('Message') }}</th></tr></thead>
                    <tbody>
                        @foreach ($recentFailures as $failure)
                            <tr><td>{{ class_basename($failure->model_type) }} #{{ $failure->model_id }}</td><td>{{ $failure->provider }}</td><td>{{ \Illuminate\Support\Str::limit($failure->message, 80) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
