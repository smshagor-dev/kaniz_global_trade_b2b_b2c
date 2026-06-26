@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('B2B Companies') }}</h1>
            </div>
            <div class="col-md-6 text-right">
                <a href="{{ route('admin.b2b.companies.create') }}" class="btn btn-primary">
                    {{ translate('Add Company') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header row gutters-5">
            <div class="col-md-4">
                <form method="GET" action="{{ route('admin.b2b.companies.index') }}">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ translate('Search company, email or user') }}">
                    </div>
                </form>
            </div>
            <div class="col-md-3">
                <form method="GET" action="{{ route('admin.b2b.companies.index') }}">
                    <select class="form-control aiz-selectpicker" name="verification_status" onchange="this.form.submit()">
                        <option value="">{{ translate('All Statuses') }}</option>
                        <option value="pending" @selected(request('verification_status') === 'pending')>{{ translate('Pending') }}</option>
                        <option value="approved" @selected(request('verification_status') === 'approved')>{{ translate('Approved') }}</option>
                        <option value="rejected" @selected(request('verification_status') === 'rejected')>{{ translate('Rejected') }}</option>
                    </select>
                </form>
            </div>
            <div class="col-md-3">
                <form method="GET" action="{{ route('admin.b2b.companies.index') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="verification_status" value="{{ request('verification_status') }}">
                    <select class="form-control aiz-selectpicker" name="company_type" onchange="this.form.submit()">
                        <option value="">{{ translate('All Types') }}</option>
                        @foreach (['buyer', 'supplier', 'manufacturer', 'distributor', 'wholesaler', 'retailer'] as $type)
                            <option value="{{ $type }}" @selected(request('company_type') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="col-md-2">
                <form method="GET" action="{{ route('admin.b2b.companies.index') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="verification_status" value="{{ request('verification_status') }}">
                    <input type="hidden" name="company_type" value="{{ request('company_type') }}">
                    <select class="form-control aiz-selectpicker" name="premium_verified" onchange="this.form.submit()">
                        <option value="">{{ translate('All Tiers') }}</option>
                        <option value="1" @selected(request('premium_verified') === '1')>{{ translate('Premium Verified') }}</option>
                        <option value="0" @selected(request('premium_verified') === '0')>{{ translate('Standard') }}</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Company') }}</th>
                        <th>{{ translate('User') }}</th>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('Country') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        <tr>
                            <td>
                                <div class="fw-600">{{ $company->company_name }}</div>
                                <small class="text-muted">{{ $company->business_email }}</small>
                                <div class="mt-1">
                                    @include('frontend.partials.supplier_badge', ['company' => $company])
                                    @if ($company->premium_verified)
                                        <span class="badge badge-inline badge-success">{{ translate('Premium') }}</span>
                                    @endif
                                    @if ($company->featured_supplier)
                                        <span class="badge badge-inline badge-warning">{{ translate('Featured') }}</span>
                                    @endif
                                    @if ($company->public_profile_enabled)
                                        <span class="badge badge-inline badge-info">{{ translate('Public') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>{{ $company->user?->name }}</div>
                                <small class="text-muted">{{ $company->user?->email }}</small>
                            </td>
                            <td>{{ ucfirst($company->company_type) }}</td>
                            <td>{{ $company->country }}</td>
                            <td>
                                <span class="badge badge-inline
                                    @if($company->verification_status === 'approved') badge-success
                                    @elseif($company->verification_status === 'rejected') badge-danger
                                    @else badge-warning @endif">
                                    {{ ucfirst($company->verification_status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.b2b.companies.show', $company->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No B2B companies found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-4">
                {{ $companies->links() }}
            </div>
        </div>
    </div>
@endsection
