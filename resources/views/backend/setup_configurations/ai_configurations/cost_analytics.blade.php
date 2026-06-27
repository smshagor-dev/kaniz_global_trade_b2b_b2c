@extends('backend.layouts.app')

@section('content')
<div class="row pt-4">
    <div class="col-md-4">
        <div class="card mb-4"><div class="card-body"><h6 class="text-muted mb-2">{{ translate('Total Cost') }}</h6><h3 class="mb-0">${{ number_format($summary['total_cost'], 4) }}</h3></div></div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4"><div class="card-body"><h6 class="text-muted mb-2">{{ translate('Total Requests') }}</h6><h3 class="mb-0">{{ $summary['total_requests'] }}</h3></div></div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4"><div class="card-body"><h6 class="text-muted mb-2">{{ translate('Success Rate') }}</h6><h3 class="mb-0">{{ $summary['success_rate'] }}%</h3></div></div>
    </div>

    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Cost Reports') }}</h5></div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Provider') }}</th>
                            <th>{{ translate('Model') }}</th>
                            <th>{{ translate('Requests') }}</th>
                            <th>{{ translate('Tokens') }}</th>
                            <th>{{ translate('Cost') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr>
                                <td>{{ optional($report->report_date)->format('d M Y') }}</td>
                                <td>{{ ucfirst($report->provider) }}</td>
                                <td>{{ $report->model }}</td>
                                <td>{{ $report->successful_requests }}/{{ $report->total_requests }}</td>
                                <td>{{ number_format($report->total_tokens) }}</td>
                                <td>${{ number_format($report->estimated_cost, 4) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">{{ translate('No cost reports available yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="aiz-pagination mt-4">{{ $reports->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
