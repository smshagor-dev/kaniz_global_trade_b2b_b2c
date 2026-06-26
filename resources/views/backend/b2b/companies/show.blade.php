@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('B2B Company Details') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                <a href="{{ route('admin.b2b.companies.index') }}" class="btn btn-soft-primary">{{ translate('Back to list') }}</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 h6">{{ $company->company_name }}</h5>
            <span class="badge badge-inline
                @if($company->verification_status === 'approved') badge-success
                @elseif($company->verification_status === 'rejected') badge-danger
                @else badge-warning @endif">
                {{ ucfirst($company->verification_status) }}
            </span>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    @if ($company->logo)
                        <img src="{{ asset($company->logo) }}" class="img-fluid border p-2" alt="{{ $company->company_name }}">
                    @else
                        <div class="border p-4 text-center text-muted">{{ translate('No logo uploaded') }}</div>
                    @endif
                </div>
                <div class="col-md-9">
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('User') }}</div><div class="col-md-8">{{ $company->user?->name }} ({{ $company->user?->email }})</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Company Type') }}</div><div class="col-md-8">{{ ucfirst($company->company_type) }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Legal Name') }}</div><div class="col-md-8">{{ $company->legal_name ?: '-' }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Registration Number') }}</div><div class="col-md-8">{{ $company->registration_number ?: '-' }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Tax Number') }}</div><div class="col-md-8">{{ $company->tax_number ?: '-' }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Business Email') }}</div><div class="col-md-8">{{ $company->business_email }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Phone') }}</div><div class="col-md-8">{{ $company->phone }}</div></div>
                </div>
            </div>

            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Country') }}</div><div class="col-md-9">{{ $company->country }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('City') }}</div><div class="col-md-9">{{ $company->city ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Address') }}</div><div class="col-md-9">{{ $company->address ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Website') }}</div><div class="col-md-9">
                @if ($company->website)
                    <a href="{{ $company->website }}" target="_blank">{{ $company->website }}</a>
                @else
                    -
                @endif
            </div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Description') }}</div><div class="col-md-9">{{ $company->description ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('B2B Package') }}</div><div class="col-md-9">{{ $company->b2bPackage?->name ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Package Expiry') }}</div><div class="col-md-9">{{ $company->package_expires_at ? $company->package_expires_at->format('d M Y') : '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Premium Verification') }}</div><div class="col-md-9">
                @if ($company->premium_verified)
                    <span class="badge badge-inline badge-success">{{ translate('Premium Verified') }}</span>
                    <span class="ml-2">{{ $company->premiumVerificationPackage?->name ?: '-' }}</span>
                @else
                    -
                @endif
            </div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Premium Verified At') }}</div><div class="col-md-9">{{ $company->premium_verified_at ? $company->premium_verified_at->format('d M Y h:i A') : '-' }}</div></div>
            @if ($company->isSupplierSide())
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Public Slug') }}</div><div class="col-md-9">{{ $company->public_slug ?: '-' }}</div></div>
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Public Profile') }}</div><div class="col-md-9">
                    @if ($company->public_profile_enabled && $company->public_slug)
                        <span class="badge badge-inline badge-success">{{ translate('Enabled') }}</span>
                        <a href="{{ route('b2b.suppliers.show', $company->public_slug) }}" target="_blank" class="ml-2">{{ translate('View Profile') }}</a>
                    @else
                        <span class="badge badge-inline badge-secondary">{{ translate('Disabled') }}</span>
                    @endif
                </div></div>
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Response Metrics') }}</div><div class="col-md-9">{{ $company->response_rate ? $company->response_rate . '%' : '-' }} / {{ $company->response_time_hours ? $company->response_time_hours . 'h' : '-' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-secondary">{{ translate('Factory Capability') }}</div><div class="col-md-9">{{ $company->production_capacity ?: '-' }}<br>{{ $company->factory_location ?: '-' }}</div></div>
            @endif
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Trade License') }}</div><div class="col-md-9">
                @if ($company->trade_license_file)
                    <a href="{{ asset($company->trade_license_file) }}" target="_blank">{{ translate('Open trade license file') }}</a>
                @else
                    -
                @endif
            </div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Tax Document') }}</div><div class="col-md-9">
                @if ($company->tax_document_file)
                    <a href="{{ asset($company->tax_document_file) }}" target="_blank">{{ translate('Open tax document file') }}</a>
                @else
                    -
                @endif
            </div></div>
            <hr class="my-4">

            <h5 class="mb-3">{{ translate('Bank Account Verification') }}</h5>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Account Name') }}</div><div class="col-md-9">{{ $company->bank_account_name ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Account Number') }}</div><div class="col-md-9">{{ $company->bank_account_number ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Bank Name') }}</div><div class="col-md-9">{{ $company->bank_name ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Branch') }}</div><div class="col-md-9">{{ $company->bank_branch_name ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Branch Address') }}</div><div class="col-md-9">{{ $company->bank_branch_address ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Bank Country') }}</div><div class="col-md-9">{{ $company->bank_country ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('SWIFT Code') }}</div><div class="col-md-9">{{ $company->swift_code ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('IBAN') }}</div><div class="col-md-9">{{ $company->iban ?: '-' }}</div></div>
            <div class="row mb-4"><div class="col-md-3 text-secondary">{{ translate('Bank Check File') }}</div><div class="col-md-9">
                @if ($company->bank_check_file)
                    <a href="{{ asset($company->bank_check_file) }}" target="_blank">{{ translate('Open bank check file') }}</a>
                @else
                    -
                @endif
            </div></div>

            <h5 class="mb-3">{{ translate('Additional Verification Items') }}</h5>
            <div class="table-responsive mb-4">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Requirement') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Submitted Value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($company->verificationSubmissions as $submission)
                            <tr>
                                <td>{{ $submission->requirement?->label ?: '-' }}</td>
                                <td>{{ ucfirst($submission->requirement?->field_type ?: 'text') }}</td>
                                <td>
                                    @if ($submission->value_file)
                                        <a href="{{ asset($submission->value_file) }}" target="_blank">{{ translate('Open file') }}</a>
                                    @else
                                        {{ $submission->value_text ?: '-' }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">{{ translate('No additional verification items submitted') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Verified At') }}</div><div class="col-md-9">{{ $company->verified_at ?: '-' }}</div></div>
            <div class="row mb-4"><div class="col-md-3 text-secondary">{{ translate('Verified By') }}</div><div class="col-md-9">{{ $company->verifier?->name ?: '-' }}</div></div>

            @if ($company->verification_note)
                <div class="alert alert-info">
                    <strong>{{ translate('Verification Note') }}:</strong> {{ $company->verification_note }}
                </div>
            @endif

            <div class="d-flex flex-wrap">
                @if ($company->verification_status !== 'approved')
                    <form action="{{ route('admin.b2b.companies.approve', $company->id) }}" method="POST" class="mr-2 mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success">{{ translate('Approve Company') }}</button>
                    </form>
                @endif
            </div>

            <form action="{{ route('admin.b2b.companies.reject', $company->id) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="fw-700">{{ translate('Rejection Note') }}</label>
                    <textarea class="form-control" name="verification_note" rows="4" placeholder="{{ translate('Add a note for the company owner') }}">{{ old('verification_note', $company->verification_status === 'rejected' ? $company->verification_note : '') }}</textarea>
                </div>
                <button type="submit" class="btn btn-danger">{{ translate('Reject Company') }}</button>
            </form>

            @if ($company->isSupplierSide())
                <hr class="my-4">

                <h5 class="mb-3">{{ translate('Supplier Controls') }}</h5>
                <form action="{{ route('admin.b2b.companies.supplier-controls', $company->id) }}" method="POST" class="mb-4">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="verified_supplier_badge" value="1" @checked($company->verified_supplier_badge)>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Verified Supplier Badge') }}</span>
                            </label>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="featured_supplier" value="1" @checked($company->featured_supplier)>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Featured Supplier') }}</span>
                            </label>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>{{ translate('Profile Score') }}</label>
                            <input type="number" class="form-control" name="profile_score" min="0" max="100" value="{{ $company->profile_score }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ translate('Update Supplier Controls') }}</button>
                </form>

                <h5 class="mb-3">{{ translate('Supplier Categories') }}</h5>
                <div class="mb-4">
                    @forelse ($company->categories as $category)
                        <span class="badge badge-inline badge-secondary mb-2">{{ $category->getTranslation('name') }}</span>
                    @empty
                        <p class="text-muted mb-0">{{ translate('No categories attached') }}</p>
                    @endforelse
                </div>

                <h5 class="mb-3">{{ translate('Certifications') }}</h5>
                <div class="table-responsive mb-4">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ translate('Name') }}</th>
                                <th>{{ translate('Authority') }}</th>
                                <th>{{ translate('Dates') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('File') }}</th>
                                <th class="text-right">{{ translate('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($company->certifications as $certification)
                                <tr>
                                    <td>{{ $certification->name }}</td>
                                    <td>{{ $certification->issuing_authority ?: '-' }}</td>
                                    <td>{{ optional($certification->issue_date)->format('d M Y') ?: '-' }} / {{ optional($certification->expiry_date)->format('d M Y') ?: '-' }}</td>
                                    <td>{{ ucfirst($certification->verification_status) }}</td>
                                    <td>
                                        @if ($certification->file)
                                            <a href="{{ asset($certification->file) }}" target="_blank">{{ translate('Open') }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <form action="{{ route('admin.b2b.certifications.approve', $certification->id) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <button type="submit" class="btn btn-soft-success btn-sm">{{ translate('Approve') }}</button>
                                        </form>
                                        <form action="{{ route('admin.b2b.certifications.reject', $certification->id) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <button type="submit" class="btn btn-soft-danger btn-sm">{{ translate('Reject') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">{{ translate('No certifications found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h5 class="mb-3">{{ translate('Wholesale Products') }}</h5>
                <div class="row">
                    @forelse ($company->wholesaleProducts as $product)
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-600 mb-2">{{ $product->getTranslation('name') }}</div>
                                <div class="text-muted">{{ home_discounted_base_price($product) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted">{{ translate('No wholesale products found') }}</div>
                    @endforelse
                </div>
            @endif

            <hr class="my-4">

            <h5 class="mb-3">{{ translate('Company Members') }}</h5>
            <div class="table-responsive mb-4">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Name') }}</th>
                            <th>{{ translate('Email') }}</th>
                            <th>{{ translate('Role') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Joined At') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($company->members as $member)
                            <tr>
                                <td>{{ $member->user?->name ?: '-' }}</td>
                                <td>{{ $member->user?->email ?: '-' }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $member->role)) }}</td>
                                <td>{{ ucfirst($member->status) }}</td>
                                <td>{{ $member->joined_at ? $member->joined_at->format('d M, Y h:i A') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">{{ translate('No members found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h5 class="mb-3">{{ translate('Company Invitations') }}</h5>
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Email') }}</th>
                            <th>{{ translate('Role') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Expires At') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($company->invitations as $invitation)
                            <tr>
                                <td>{{ $invitation->email }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $invitation->role)) }}</td>
                                <td>{{ ucfirst($invitation->status) }}</td>
                                <td>{{ $invitation->expires_at ? $invitation->expires_at->format('d M, Y h:i A') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">{{ translate('No invitations found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
