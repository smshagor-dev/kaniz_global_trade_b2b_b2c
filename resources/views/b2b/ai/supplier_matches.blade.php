@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <h1 class="fs-20 fw-700 text-dark">{{ translate('AI Supplier Matches') }}</h1>
        <p class="text-muted mb-0">{{ $rfq->title }}</p>
    </div>

    @if (!empty($summary['summary']))
        <div class="alert alert-info">{{ $summary['summary'] }}</div>
    @endif

    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Supplier') }}</th>
                        <th>{{ translate('Score') }}</th>
                        <th>{{ translate('Reasons') }}</th>
                        <th>{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matches as $match)
                        <tr>
                            <td>
                                <div>{{ $match['supplier']->company_name }}</div>
                                <small class="text-muted">{{ ucfirst($match['supplier']->company_type) }} · {{ $match['supplier']->country }}</small>
                            </td>
                            <td>{{ $match['score'] }}</td>
                            <td>{{ collect($match['reasons'])->implode(', ') }}</td>
                            <td>
                                <a href="{{ route('b2b.suppliers.show', $match['supplier']->public_slug) }}" class="btn btn-soft-primary btn-sm">{{ translate('View Supplier') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">{{ translate('No supplier matches found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
