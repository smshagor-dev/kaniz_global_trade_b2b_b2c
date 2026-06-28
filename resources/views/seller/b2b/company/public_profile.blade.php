@extends('b2b.layouts.supplier')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Supplier Public Profile') }}</h1>
            </div>
        </div>
    </div>

    @if ($company->verification_status !== 'approved')
        <div class="alert alert-warning">{{ translate('Public profile publishing is available only for approved supplier-side companies.') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ translate('Public Profile Settings') }}</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('seller.b2b.company.public-profile.update') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Year Established') }}</label>
                        <input type="number" class="form-control" name="year_established" value="{{ old('year_established', $company->year_established) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Employee Count') }}</label>
                        <input type="text" class="form-control" name="employee_count" value="{{ old('employee_count', $company->employee_count) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Annual Revenue') }}</label>
                        <input type="text" class="form-control" name="annual_revenue" value="{{ old('annual_revenue', $company->annual_revenue) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Main Markets') }}</label>
                        <input type="text" class="form-control" name="main_markets" value="{{ old('main_markets', $company->main_markets) }}">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ translate('Business Scope') }}</label>
                        <textarea class="form-control" name="business_scope" rows="3">{{ old('business_scope', $company->business_scope) }}</textarea>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ translate('Production Capacity') }}</label>
                        <textarea class="form-control" name="production_capacity" rows="3">{{ old('production_capacity', $company->production_capacity) }}</textarea>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Export Percentage') }}</label>
                        <input type="number" step="0.01" class="form-control" name="export_percentage" value="{{ old('export_percentage', $company->export_percentage) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Factory Size') }}</label>
                        <input type="text" class="form-control" name="factory_size" value="{{ old('factory_size', $company->factory_size) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Factory Location') }}</label>
                        <input type="text" class="form-control" name="factory_location" value="{{ old('factory_location', $company->factory_location) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Quality Control') }}</label>
                        <textarea class="form-control" name="quality_control" rows="3">{{ old('quality_control', $company->quality_control) }}</textarea>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Lead Time Summary') }}</label>
                        <input type="text" class="form-control" name="lead_time_summary" value="{{ old('lead_time_summary', $company->lead_time_summary) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Response Rate') }}</label>
                        <input type="number" step="0.01" class="form-control" name="response_rate" value="{{ old('response_rate', $company->response_rate) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('Response Time (Hours)') }}</label>
                        <input type="number" class="form-control" name="response_time_hours" value="{{ old('response_time_hours', $company->response_time_hours) }}">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ translate('Supplier Categories') }}</label>
                        <div class="row">
                            @foreach ($categories as $category)
                                <div class="col-md-4 mb-2">
                                    <label class="aiz-checkbox">
                                        <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked($company->categories->pluck('id')->contains($category->id))>
                                        <span class="aiz-square-check"></span>
                                        <span>{{ $category->getTranslation('name') }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-12 form-group">
                        <label class="aiz-checkbox">
                            <input type="checkbox" name="public_profile_enabled" value="1" @checked($company->public_profile_enabled) @disabled($company->verification_status !== 'approved')>
                            <span class="aiz-square-check"></span>
                            <span>{{ translate('Enable Public Supplier Profile') }}</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ translate('Update Public Profile') }}</button>
                @if ($company->public_profile_enabled)
                    <a href="{{ route('b2b.suppliers.show', $company->public_slug) }}" target="_blank" class="btn btn-soft-primary ml-2">{{ translate('View Public Profile') }}</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ translate('Add Certification') }}</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('seller.b2b.company.public-profile.certifications.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Name') }}</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Issuing Authority') }}</label>
                        <input type="text" class="form-control" name="issuing_authority">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ translate('Certificate Number') }}</label>
                        <input type="text" class="form-control" name="certificate_number">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Issue Date') }}</label>
                        <input type="date" class="form-control" name="issue_date">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>{{ translate('Expiry Date') }}</label>
                        <input type="date" class="form-control" name="expiry_date">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ translate('File') }}</label>
                        <input type="file" class="form-control" name="file">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ translate('Add Certification') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ translate('Existing Certifications') }}</h5></div>
        <div class="card-body">
            @forelse ($certifications as $certification)
                <form method="POST" action="{{ route('seller.b2b.company.public-profile.certifications.update', $certification->id) }}" enctype="multipart/form-data" class="border rounded p-3 mb-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>{{ translate('Name') }}</label>
                            <input type="text" class="form-control" name="name" value="{{ $certification->name }}" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>{{ translate('Issuing Authority') }}</label>
                            <input type="text" class="form-control" name="issuing_authority" value="{{ $certification->issuing_authority }}">
                        </div>
                        <div class="col-md-2 form-group">
                            <label>{{ translate('Certificate Number') }}</label>
                            <input type="text" class="form-control" name="certificate_number" value="{{ $certification->certificate_number }}">
                        </div>
                        <div class="col-md-2 form-group">
                            <label>{{ translate('Issue Date') }}</label>
                            <input type="date" class="form-control" name="issue_date" value="{{ optional($certification->issue_date)->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2 form-group">
                            <label>{{ translate('Expiry Date') }}</label>
                            <input type="date" class="form-control" name="expiry_date" value="{{ optional($certification->expiry_date)->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ translate('Replace File') }}</label>
                            <input type="file" class="form-control" name="file">
                            @if ($certification->file)
                                <small><a href="{{ asset($certification->file) }}" target="_blank">{{ translate('View Current File') }}</a></small>
                            @endif
                        </div>
                        <div class="col-md-6 form-group d-flex align-items-end justify-content-between">
                            <span class="badge badge-inline
                                @if($certification->verification_status === 'approved') badge-success
                                @elseif($certification->verification_status === 'rejected') badge-danger
                                @else badge-warning @endif">
                                {{ ucfirst($certification->verification_status) }}
                            </span>
                            <div>
                                <button type="submit" class="btn btn-soft-primary">{{ translate('Update') }}</button>
                                <button type="submit" formaction="{{ route('seller.b2b.company.public-profile.certifications.delete', $certification->id) }}" class="btn btn-soft-danger" onclick="return confirm('{{ translate('Are you sure?') }}')">{{ translate('Delete') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            @empty
                <p class="mb-0 text-muted">{{ translate('No certifications added yet.') }}</p>
            @endforelse
        </div>
    </div>
@endsection
