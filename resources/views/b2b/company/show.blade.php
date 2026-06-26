@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('My B2B Company') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                @if (!empty($availableCompanies) && $availableCompanies->count() > 1)
                    <form action="{{ route('b2b.company.switch') }}" method="POST" class="d-inline-block mr-2">
                        @csrf
                        <select name="company_id" class="form-control d-inline-block w-auto" onchange="this.form.submit()">
                            @foreach ($availableCompanies as $availableCompany)
                                <option value="{{ $availableCompany->id }}" @selected((int) $company->id === (int) $availableCompany->id)>
                                    {{ $availableCompany->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
                @if (!empty($canInviteMembers))
                    <a href="{{ route('b2b.company.members.index') }}" class="btn btn-soft-primary rounded-0 mr-2">{{ translate('Company Team') }}</a>
                @endif
                @if (!empty($canManageCompany))
                    <a href="{{ route('b2b.company.edit') }}" class="btn btn-primary rounded-0">{{ translate('Edit Company Profile') }}</a>
                @endif
                <a href="{{ route('b2b.premium-verifications.index') }}" class="btn btn-soft-success rounded-0 ml-2">{{ translate('Premium Verification') }}</a>
                @if (!empty($canManageSupplierProfile))
                    <a href="{{ route('seller.b2b.company.public-profile') }}" class="btn btn-soft-primary rounded-0 ml-2">{{ translate('Supplier Public Profile') }}</a>
                @endif
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-header pt-4 border-bottom-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Company Overview') }}</h5>
            <span class="badge badge-inline
                @if($company->verification_status === 'approved') badge-success
                @elseif($company->verification_status === 'rejected') badge-danger
                @else badge-warning @endif">
                {{ ucfirst($company->verification_status) }}
            </span>
        </div>
        <div class="card-body">
            @if ($company->verification_status === 'rejected' && $company->verification_note)
                <div class="alert alert-danger">
                    <strong>{{ translate('Verification Note') }}:</strong> {{ $company->verification_note }}
                </div>
            @elseif ($company->verification_status === 'pending')
                <div class="alert alert-warning">
                    {{ translate('Your company verification is pending. You cannot create wholesale products until it is approved.') }}
                </div>
            @endif

            <div class="row mb-4">
                <div class="col-md-3">
                    @if ($company->logo)
                        <img src="{{ asset($company->logo) }}" class="img-fluid border p-2" alt="{{ $company->company_name }}">
                    @else
                        <div class="border p-4 text-center text-muted">{{ translate('No logo uploaded') }}</div>
                    @endif
                </div>
                <div class="col-md-9">
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Company Name') }}</div><div class="col-md-8">{{ $company->company_name }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Company Type') }}</div><div class="col-md-8">{{ ucfirst($company->company_type) }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Legal Name') }}</div><div class="col-md-8">{{ $company->legal_name ?: '-' }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Registration Number') }}</div><div class="col-md-8">{{ $company->registration_number ?: '-' }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Tax Number') }}</div><div class="col-md-8">{{ $company->tax_number ?: '-' }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Business Email') }}</div><div class="col-md-8">{{ $company->business_email }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Phone') }}</div><div class="col-md-8">{{ $company->phone }}</div></div>
                    <div class="row mb-2"><div class="col-md-4 text-secondary">{{ translate('Website') }}</div><div class="col-md-8">
                        @if ($company->website)
                            <a href="{{ $company->website }}" target="_blank">{{ $company->website }}</a>
                        @else
                            -
                        @endif
                    </div></div>
                </div>
            </div>

            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Country') }}</div><div class="col-md-9">{{ $company->country }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('City') }}</div><div class="col-md-9">{{ $company->city ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Address') }}</div><div class="col-md-9">{{ $company->address ?: '-' }}</div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Description') }}</div><div class="col-md-9">{{ $company->description ?: '-' }}</div></div>
            @if ($company->isSupplierSide())
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Public Profile Status') }}</div><div class="col-md-9">
                    @if ($company->public_profile_enabled)
                        <span class="badge badge-inline badge-success">{{ translate('Enabled') }}</span>
                        @if ($company->public_slug)
                            <a href="{{ route('b2b.suppliers.show', $company->public_slug) }}" target="_blank" class="ml-2">{{ translate('View Public Profile') }}</a>
                        @endif
                    @else
                        <span class="badge badge-inline badge-secondary">{{ translate('Disabled') }}</span>
                    @endif
                </div></div>
                <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Supplier Badge') }}</div><div class="col-md-9">
                    @include('frontend.partials.supplier_badge', ['company' => $company])
                    @if ($company->premium_verified)
                        <span class="badge badge-inline badge-success">{{ translate('Premium Verified') }}</span>
                    @endif
                    @if ($company->featured_supplier)
                        <span class="badge badge-inline badge-warning">{{ translate('Featured Supplier') }}</span>
                    @endif
                </div></div>
            @endif
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Premium Verification') }}</div><div class="col-md-9">
                @if ($company->premium_verified)
                    <span class="badge badge-inline badge-success">{{ translate('Active') }}</span>
                    <span class="ml-2 text-muted">{{ $company->premiumVerificationPackage?->name ?: translate('Premium Verification Package') }}</span>
                @else
                    <span class="badge badge-inline badge-secondary">{{ translate('Inactive') }}</span>
                @endif
            </div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Trade License') }}</div><div class="col-md-9">
                @if ($company->trade_license_file)
                    <a href="{{ asset($company->trade_license_file) }}" target="_blank">{{ translate('View trade license file') }}</a>
                @else
                    -
                @endif
            </div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Tax Document') }}</div><div class="col-md-9">
                @if ($company->tax_document_file)
                    <a href="{{ asset($company->tax_document_file) }}" target="_blank">{{ translate('View tax document file') }}</a>
                @else
                    -
                @endif
            </div></div>
            <div class="row mb-2"><div class="col-md-3 text-secondary">{{ translate('Verified At') }}</div><div class="col-md-9">{{ $company->verified_at ?: '-' }}</div></div>
        </div>
    </div>
@endsection
