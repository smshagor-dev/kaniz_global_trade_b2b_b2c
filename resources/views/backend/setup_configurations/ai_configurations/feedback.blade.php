@extends('backend.layouts.app')

@section('content')
<div class="row pt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('AI Feedback') }}</h5></div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Module') }}</th>
                            <th>{{ translate('Rating') }}</th>
                            <th>{{ translate('Feedback') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($feedback as $item)
                            <tr>
                                <td>{{ $item->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $item->module ?: '-' }}</td>
                                <td>{{ $item->rating ?: '-' }}</td>
                                <td>{{ $item->feedback ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">{{ translate('No AI feedback submitted yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="aiz-pagination mt-4">{{ $feedback->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
