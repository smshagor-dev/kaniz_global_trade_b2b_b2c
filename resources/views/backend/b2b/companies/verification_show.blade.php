@extends('backend.layouts.app')

@section('content')
    @php
        $submissionMap = $company->verificationSubmissions->keyBy('b2b_verification_requirement_id');
    @endphp

    <div class="aiz-titlebar text-left pb-3">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1 class="h3">{{ translate('Company Submission Review') }}</h1>
                <p class="text-muted mb-0">{{ $company->company_name }} - {{ ucfirst($company->verification_status) }}</p>
            </div>
            <div class="col-md-5 text-right">
                <a href="{{ route('admin.b2b.companies.verification', ['verification_status' => request('verification_status', 'pending')]) }}" class="btn btn-soft-primary">
                    {{ translate('Back to Queue') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 h6">{{ translate('Submission Summary') }}</h5>
            <span class="badge badge-inline
                @if($company->verification_status === 'approved') badge-success
                @elseif($company->verification_status === 'rejected') badge-danger
                @else badge-warning @endif">
                {{ ucfirst($company->verification_status) }}
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="row mb-2"><div class="col-5 text-secondary">{{ translate('Company') }}</div><div class="col-7">{{ $company->company_name }}</div></div>
                    <div class="row mb-2"><div class="col-5 text-secondary">{{ translate('Owner') }}</div><div class="col-7">{{ $company->user?->name ?: '-' }}</div></div>
                    <div class="row mb-2"><div class="col-5 text-secondary">{{ translate('Owner Email') }}</div><div class="col-7">{{ $company->user?->email ?: '-' }}</div></div>
                    <div class="row mb-2"><div class="col-5 text-secondary">{{ translate('Company Type') }}</div><div class="col-7">{{ ucfirst($company->company_type) }}</div></div>
                </div>
                <div class="col-md-6">
                    <div class="row mb-2"><div class="col-5 text-secondary">{{ translate('Business Email') }}</div><div class="col-7">{{ $company->business_email }}</div></div>
                    <div class="row mb-2"><div class="col-5 text-secondary">{{ translate('Phone') }}</div><div class="col-7">{{ $company->phone }}</div></div>
                    <div class="row mb-2"><div class="col-5 text-secondary">{{ translate('Country') }}</div><div class="col-7">{{ $company->country }}</div></div>
                    <div class="row mb-2"><div class="col-5 text-secondary">{{ translate('Premium Tier') }}</div><div class="col-7">
                        @if ($company->premium_verified)
                            <span class="badge badge-inline badge-success">{{ translate('Premium Verified') }}</span>
                        @else
                            <span class="badge badge-inline badge-secondary">{{ translate('Standard Verification') }}</span>
                        @endif
                    </div></div>
                    <div class="row mb-2"><div class="col-5 text-secondary">{{ translate('Last Update') }}</div><div class="col-7">{{ $company->updated_at ? $company->updated_at->format('d M Y h:i A') : '-' }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Core Company Documents') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Item') }}</th>
                            <th>{{ translate('Submitted Value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>{{ translate('Legal Name') }}</td><td>{{ $company->legal_name ?: '-' }}</td></tr>
                        <tr><td>{{ translate('Registration Number') }}</td><td>{{ $company->registration_number ?: '-' }}</td></tr>
                        <tr><td>{{ translate('Tax Number') }}</td><td>{{ $company->tax_number ?: '-' }}</td></tr>
                        <tr>
                            <td>{{ translate('Trade License File') }}</td>
                            <td>@if ($company->trade_license_file)<a href="{{ asset($company->trade_license_file) }}" target="_blank">{{ translate('Open file') }}</a>@else - @endif</td>
                        </tr>
                        <tr>
                            <td>{{ translate('Tax Document File') }}</td>
                            <td>@if ($company->tax_document_file)<a href="{{ asset($company->tax_document_file) }}" target="_blank">{{ translate('Open file') }}</a>@else - @endif</td>
                        </tr>
                        <tr><td>{{ translate('Address') }}</td><td>{{ $company->address ?: '-' }}</td></tr>
                        <tr><td>{{ translate('Website') }}</td><td>{{ $company->website ?: '-' }}</td></tr>
                        <tr><td>{{ translate('Description') }}</td><td>{{ $company->description ?: '-' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Bank Account Submission') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <tbody>
                        <tr><td width="30%">{{ translate('Account Name') }}</td><td>{{ $company->bank_account_name ?: '-' }}</td></tr>
                        <tr><td>{{ translate('Account Number') }}</td><td>{{ $company->bank_account_number ?: '-' }}</td></tr>
                        <tr><td>{{ translate('Bank Name') }}</td><td>{{ $company->bank_name ?: '-' }}</td></tr>
                        <tr><td>{{ translate('Branch Name') }}</td><td>{{ $company->bank_branch_name ?: '-' }}</td></tr>
                        <tr><td>{{ translate('Branch Address') }}</td><td>{{ $company->bank_branch_address ?: '-' }}</td></tr>
                        <tr><td>{{ translate('Bank Country') }}</td><td>{{ $company->bank_country ?: '-' }}</td></tr>
                        <tr><td>{{ translate('SWIFT Code') }}</td><td>{{ $company->swift_code ?: '-' }}</td></tr>
                        <tr><td>{{ translate('IBAN') }}</td><td>{{ $company->iban ?: '-' }}</td></tr>
                        <tr>
                            <td>{{ translate('Bank Check / Statement') }}</td>
                            <td>@if ($company->bank_check_file)<a href="{{ asset($company->bank_check_file) }}" target="_blank">{{ translate('Open file') }}</a>@else - @endif</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Additional Verification Submissions') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Requirement') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Submission') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requirements as $requirement)
                            @php $submission = $submissionMap->get($requirement->id); @endphp
                            <tr>
                                <td>{{ $requirement->label }}</td>
                                <td>{{ ucfirst($requirement->field_type) }}</td>
                                <td>
                                    @if ($submission?->value_file)
                                        <a href="{{ asset($submission->value_file) }}" target="_blank">{{ translate('Open file') }}</a>
                                    @elseif ($submission?->value_text)
                                        {{ $submission->value_text }}
                                    @else
                                        <span class="text-muted">{{ translate('Not submitted') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">{{ translate('No additional requirements configured') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Certifications') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Certification') }}</th>
                            <th>{{ translate('Authority') }}</th>
                            <th>{{ translate('File') }}</th>
                            <th>{{ translate('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($company->certifications as $certification)
                            <tr>
                                <td>{{ $certification->name }}</td>
                                <td>{{ $certification->issuing_authority ?: '-' }}</td>
                                <td>@if ($certification->file)<a href="{{ asset($certification->file) }}" target="_blank">{{ translate('Open file') }}</a>@else - @endif</td>
                                <td>{{ ucfirst($certification->verification_status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">{{ translate('No certifications found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($company->verification_note)
        <div class="alert alert-info">
            <strong>{{ translate('Previous Note') }}:</strong> {{ $company->verification_note }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Verification Decision') }}</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap mb-3">
                @if ($company->verification_status !== 'approved')
                    <form action="{{ route('admin.b2b.companies.approve', $company->id) }}" method="POST" class="mr-2 mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success">{{ translate('Approve') }}</button>
                    </form>
                @endif
            </div>

            <form action="{{ route('admin.b2b.companies.reject', $company->id) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="fw-700">{{ translate('Reject Note') }}</label>
                    <textarea class="form-control" name="verification_note" rows="4" placeholder="{{ translate('Add rejection reason for this submission') }}">{{ old('verification_note', $company->verification_status === 'rejected' ? $company->verification_note : '') }}</textarea>
                </div>
                <button type="submit" class="btn btn-danger">{{ translate('Reject') }}</button>
            </form>
        </div>
    </div>
@endsection
