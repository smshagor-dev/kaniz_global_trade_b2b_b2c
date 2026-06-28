@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar mb-4">
    <div class="row align-items-center">
        <div class="col"><h1 class="h3">{{ translate('Fraud Users') }}</h1></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-3 mb-2"><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ translate('Search name/email/company') }}"></div>
            <div class="col-md-2 mb-2">
                <select name="user_type" class="form-control aiz-selectpicker">
                    <option value="">{{ translate('All types') }}</option>
                    <option value="supplier" @selected(request('user_type') === 'supplier')>{{ translate('Supplier') }}</option>
                    <option value="buyer" @selected(request('user_type') === 'buyer')>{{ translate('Buyer') }}</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <select name="risk_level" class="form-control aiz-selectpicker">
                    <option value="">{{ translate('All risk levels') }}</option>
                    @foreach (['safe', 'low', 'medium', 'high', 'critical', 'blocked'] as $level)
                        <option value="{{ $level }}" @selected(request('risk_level') === $level)>{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <select name="status" class="form-control aiz-selectpicker">
                    <option value="">{{ translate('All statuses') }}</option>
                    @foreach (['approved', 'pending', 'needs_review', 'restricted', 'rejected', 'blocked'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2 text-right"><button class="btn btn-primary">{{ translate('Filter') }}</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>{{ translate('User') }}</th>
                    <th>{{ translate('Company') }}</th>
                    <th>{{ translate('Type') }}</th>
                    <th>{{ translate('Score') }}</th>
                    <th>{{ translate('Risk') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th class="text-right">{{ translate('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($checks as $check)
                    <tr>
                        <td>{{ $check->user?->name }}<br><small class="text-muted">{{ $check->user?->email }}</small></td>
                        <td>{{ $check->user?->b2bCompany?->company_name ?: '-' }}</td>
                        <td>{{ ucfirst($check->user_type) }}</td>
                        <td>{{ $check->final_score ?? $check->risk_score }}</td>
                        <td><span class="badge badge-inline badge-{{ in_array($check->risk_level, ['high', 'critical', 'blocked'], true) ? 'danger' : (in_array($check->risk_level, ['medium'], true) ? 'warning' : 'success') }}">{{ ucfirst($check->risk_level) }}</span></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $check->status)) }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.fraud.users.show', $check->user_id) }}" class="btn btn-soft-primary btn-sm">{{ translate('View') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ translate('No fraud checks found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $checks->links() }}</div>
</div>
@endsection
