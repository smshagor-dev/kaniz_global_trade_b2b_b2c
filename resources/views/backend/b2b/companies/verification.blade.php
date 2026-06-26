@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Company Verification Queue') }}</h1>
                <p class="text-muted mb-0">{{ translate('Default view shows pending companies. Open each submission to review all documents and approve or reject.') }}</p>
            </div>
            <div class="col-md-6 text-right">
                <a href="{{ route('admin.b2b.verification-requirements.index') }}" class="btn btn-soft-info mr-2">
                    {{ translate('Verification Requirements') }}
                </a>
                <a href="{{ route('admin.b2b.companies.index') }}" class="btn btn-soft-primary">
                    {{ translate('Company List') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $companyStats['pending'] }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Pending') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $companyStats['approved'] }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Approved') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $companyStats['rejected'] }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Rejected') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $requirements->count() }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Requirement Types') }}</h3>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dashboard-box bg-white">
                <h1 class="fs-30 fw-600 text-dark mb-1">{{ $companyStats['premium_verified'] ?? 0 }}</h1>
                <h3 class="fs-13 fw-600 text-secondary mb-0">{{ translate('Premium Verified') }}</h3>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header row gutters-5 align-items-center">
            <div class="col-md-4">
                <form method="GET" action="{{ route('admin.b2b.companies.verification') }}">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ translate('Search company, email or user') }}">
                    </div>
                </form>
            </div>
            <div class="col-md-3">
                <form method="GET" action="{{ route('admin.b2b.companies.verification') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <select class="form-control aiz-selectpicker" name="verification_status" onchange="this.form.submit()">
                        <option value="pending" @selected($verificationStatus === 'pending')>{{ translate('Pending') }}</option>
                        <option value="approved" @selected($verificationStatus === 'approved')>{{ translate('Approved') }}</option>
                        <option value="rejected" @selected($verificationStatus === 'rejected')>{{ translate('Rejected') }}</option>
                    </select>
                </form>
            </div>
            <div class="col-md-2">
                <form method="GET" action="{{ route('admin.b2b.companies.verification') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="verification_status" value="{{ $verificationStatus }}">
                    <select class="form-control aiz-selectpicker" name="premium_verified" onchange="this.form.submit()">
                        <option value="">{{ translate('All Verification Tiers') }}</option>
                        <option value="1" @selected(request('premium_verified') === '1')>{{ translate('Premium Verified') }}</option>
                        <option value="0" @selected(request('premium_verified') === '0')>{{ translate('Standard Verification') }}</option>
                    </select>
                </form>
            </div>
            <div class="col-md-3 text-md-right">
                <h5 class="mb-0 h6">{{ ucfirst($verificationStatus) }} {{ translate('Company Submissions') }}</h5>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Company') }}</th>
                            <th>{{ translate('Owner') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Core Documents') }}</th>
                            <th>{{ translate('Extra Submissions') }}</th>
                            <th>{{ translate('Bank Check') }}</th>
                            <th>{{ translate('Submitted') }}</th>
                            <th class="text-right">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            @php
                                $submissionMap = $company->verificationSubmissions->keyBy('b2b_verification_requirement_id');
                                $matchingRequirements = $requirements->filter(fn ($requirement) => $requirement->appliesTo($company->company_type));
                                $completedRequirements = $matchingRequirements->filter(function ($requirement) use ($submissionMap) {
                                    $submission = $submissionMap->get($requirement->id);
                                    return $submission && ($submission->value_text || $submission->value_file);
                                })->count();
                                $coreDocs = collect([$company->trade_license_file, $company->tax_document_file])->filter()->count();
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-600">{{ $company->company_name }}</div>
                                    <small class="text-muted">{{ $company->business_email }}</small>
                                    @if ($company->premium_verified)
                                        <div><span class="badge badge-inline badge-success mt-1">{{ translate('Premium Verified') }}</span></div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $company->user?->name ?: '-' }}</div>
                                    <small class="text-muted">{{ $company->user?->email ?: '-' }}</small>
                                </td>
                                <td>{{ ucfirst($company->company_type) }}</td>
                                <td>{{ $coreDocs }}/2</td>
                                <td>{{ $completedRequirements }}/{{ $matchingRequirements->count() }}</td>
                                <td>
                                    @if ($company->bank_check_file)
                                        <span class="badge badge-inline badge-success">{{ translate('Uploaded') }}</span>
                                    @else
                                        <span class="badge badge-inline badge-warning">{{ translate('Missing') }}</span>
                                    @endif
                                </td>
                                <td>{{ $company->updated_at ? $company->updated_at->format('d M Y') : '-' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.b2b.companies.verification.show', $company->id) }}" class="btn btn-primary btn-sm">
                                        {{ translate('See Submission') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">{{ translate('No company submissions found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-4">
                {{ $companies->links() }}
            </div>
        </div>
    </div>
@endsection
