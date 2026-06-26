@php
    $company = $company ?? null;
    $renderCard = $renderCard ?? true;
    $renderForm = $renderForm ?? true;
    $verificationRequirements = $verificationRequirements ?? collect();
    $submissionMap = $company?->verificationSubmissions?->keyBy('b2b_verification_requirement_id') ?? collect();
    $companyTypes = [
        'buyer' => translate('Buyer'),
        'supplier' => translate('Supplier'),
        'manufacturer' => translate('Manufacturer'),
        'distributor' => translate('Distributor'),
        'wholesaler' => translate('Wholesaler'),
        'retailer' => translate('Retailer'),
    ];
    $fieldTypeLabels = [
        'text' => 'text',
        'textarea' => 'textarea',
        'email' => 'email',
        'phone' => 'text',
        'url' => 'url',
        'number' => 'number',
        'date' => 'date',
    ];
@endphp

@if ($renderCard)
    <div class="card rounded-0 shadow-none border">
        <div class="card-header pt-4 border-bottom-0">
            <h5 class="mb-0 fs-18 fw-700 text-dark">{{ $title }}</h5>
            <p class="text-muted mb-0 mt-2">{{ translate('Company legal profile, banking details, and verification documents stay together on one page for faster review.') }}</p>
        </div>
        <div class="card-body">
@endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if ($renderForm)
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
            @csrf
        @endif
            <div class="border rounded p-3 mb-4">
                <h6 class="fw-700 mb-3">{{ translate('Company Identity') }}</h6>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Company Name') }} <span class="text-danger">*</span></label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="company_name" value="{{ old('company_name', $company?->company_name) }}" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Company Type') }} <span class="text-danger">*</span></label>
                    <div class="col-md-9">
                        <select class="form-control aiz-selectpicker" name="company_type" data-live-search="true" required>
                            <option value="">{{ translate('Select Company Type') }}</option>
                            @foreach ($companyTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('company_type', $company?->company_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Legal Name') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="legal_name" value="{{ old('legal_name', $company?->legal_name) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Registration Number') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="registration_number" value="{{ old('registration_number', $company?->registration_number) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Tax Number') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="tax_number" value="{{ old('tax_number', $company?->tax_number) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Country') }} <span class="text-danger">*</span></label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="country" value="{{ old('country', $company?->country) }}" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('City') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="city" value="{{ old('city', $company?->city) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Address') }}</label>
                    <div class="col-md-9">
                        <textarea class="form-control rounded-0" name="address" rows="3">{{ old('address', $company?->address) }}</textarea>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Website') }}</label>
                    <div class="col-md-9">
                        <input type="url" class="form-control rounded-0" name="website" value="{{ old('website', $company?->website) }}" placeholder="https://example.com">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Phone') }} <span class="text-danger">*</span></label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="phone" value="{{ old('phone', $company?->phone) }}" required>
                    </div>
                </div>
                <div class="form-group row mb-0">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Business Email') }} <span class="text-danger">*</span></label>
                    <div class="col-md-9">
                        <input type="email" class="form-control rounded-0" name="business_email" value="{{ old('business_email', $company?->business_email) }}" required>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-4">
                <h6 class="fw-700 mb-3">{{ translate('Business Overview') }}</h6>
                <div class="form-group row mb-0">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Description') }}</label>
                    <div class="col-md-9">
                        <textarea class="form-control rounded-0" name="description" rows="4">{{ old('description', $company?->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-700 mb-1">{{ translate('Bank Account Verification') }}</h6>
                        <p class="text-muted mb-0">{{ translate('Collect beneficiary details and one bank check or bank statement copy on the same page for finance review.') }}</p>
                    </div>
                    <span class="badge badge-inline badge-soft-info">{{ translate('Alibaba-style bank check') }}</span>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Account Name') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="bank_account_name" value="{{ old('bank_account_name', $company?->bank_account_name) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Account Number') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="bank_account_number" value="{{ old('bank_account_number', $company?->bank_account_number) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Bank Name') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="bank_name" value="{{ old('bank_name', $company?->bank_name) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Branch Name') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="bank_branch_name" value="{{ old('bank_branch_name', $company?->bank_branch_name) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Branch Address') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="bank_branch_address" value="{{ old('bank_branch_address', $company?->bank_branch_address) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Bank Country') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="bank_country" value="{{ old('bank_country', $company?->bank_country) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('SWIFT Code') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="swift_code" value="{{ old('swift_code', $company?->swift_code) }}">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('IBAN') }}</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control rounded-0" name="iban" value="{{ old('iban', $company?->iban) }}">
                    </div>
                </div>
                <div class="form-group row mb-0">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Bank Check / Bank Statement') }}</label>
                    <div class="col-md-9">
                        <input type="file" class="form-control rounded-0" name="bank_check_file" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted d-block mt-2">{{ translate('Upload one page bank confirmation, void cheque, or account statement showing beneficiary and bank name.') }}</small>
                        @if ($company?->bank_check_file)
                            <small class="text-muted d-block mt-2">
                                <a href="{{ asset($company->bank_check_file) }}" target="_blank">{{ translate('View current bank check file') }}</a>
                            </small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-4">
                <h6 class="fw-700 mb-3">{{ translate('Core Verification Documents') }}</h6>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Company Logo') }}</label>
                    <div class="col-md-9">
                        <input type="file" class="form-control rounded-0" name="logo" accept=".jpg,.jpeg,.png">
                        @if ($company?->logo)
                            <small class="text-muted d-block mt-2">
                                <a href="{{ asset($company->logo) }}" target="_blank">{{ translate('View current logo') }}</a>
                            </small>
                        @endif
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Trade License File') }}</label>
                    <div class="col-md-9">
                        <input type="file" class="form-control rounded-0" name="trade_license_file" accept=".pdf,.jpg,.jpeg,.png">
                        @if ($company?->trade_license_file)
                            <small class="text-muted d-block mt-2">
                                <a href="{{ asset($company->trade_license_file) }}" target="_blank">{{ translate('View current trade license file') }}</a>
                            </small>
                        @endif
                    </div>
                </div>
                <div class="form-group row mb-0">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Tax Document File') }}</label>
                    <div class="col-md-9">
                        <input type="file" class="form-control rounded-0" name="tax_document_file" accept=".pdf,.jpg,.jpeg,.png">
                        @if ($company?->tax_document_file)
                            <small class="text-muted d-block mt-2">
                                <a href="{{ asset($company->tax_document_file) }}" target="_blank">{{ translate('View current tax document file') }}</a>
                            </small>
                        @endif
                    </div>
                </div>
            </div>

            @if ($verificationRequirements->count())
                <div class="border rounded p-3 mb-4">
                    <h6 class="fw-700 mb-3">{{ translate('Additional Verification Requirements') }}</h6>
                    <p class="text-muted">{{ translate('Admin can configure extra documents or information such as business licenses, importer codes, owner ID, export permits, factory audits, or proof of address.') }}</p>
                    @foreach ($verificationRequirements as $requirement)
                        @php
                            $submission = $submissionMap->get($requirement->id);
                            $selectedTypes = $requirement->company_types ?? [];
                        @endphp
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-2">
                                <div class="fw-600">{{ $requirement->label }} @if($requirement->is_required)<span class="text-danger">*</span>@endif</div>
                                @if (!empty($selectedTypes))
                                    <small class="text-muted">{{ translate('For') }}: {{ collect($selectedTypes)->map(fn ($type) => $companyTypes[$type] ?? ucfirst($type))->implode(', ') }}</small>
                                @endif
                            </div>
                            @if ($requirement->help_text)
                                <div class="text-muted mb-2">{{ $requirement->help_text }}</div>
                            @endif

                            @if ($requirement->field_type === 'file')
                                <input type="file" class="form-control rounded-0" name="requirement_file[{{ $requirement->id }}]" accept=".pdf,.jpg,.jpeg,.png">
                                @if ($submission?->value_file)
                                    <small class="text-muted d-block mt-2">
                                        <a href="{{ asset($submission->value_file) }}" target="_blank">{{ translate('View uploaded file') }}</a>
                                    </small>
                                @endif
                            @elseif ($requirement->field_type === 'textarea')
                                <textarea class="form-control rounded-0" name="requirement_text[{{ $requirement->id }}]" rows="4" placeholder="{{ $requirement->placeholder }}">{{ old('requirement_text.' . $requirement->id, $submission?->value_text) }}</textarea>
                            @else
                                <input
                                    type="{{ $fieldTypeLabels[$requirement->field_type] ?? 'text' }}"
                                    class="form-control rounded-0"
                                    name="requirement_text[{{ $requirement->id }}]"
                                    value="{{ old('requirement_text.' . $requirement->id, $submission?->value_text) }}"
                                    placeholder="{{ $requirement->placeholder }}"
                                >
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="form-group mb-0 text-right">
                <button type="submit" class="btn btn-primary rounded-0 w-200px mt-3">{{ $submitText }}</button>
            </div>
        @if ($renderForm)
        </form>
        @endif
@if ($renderCard)
        </div>
    </div>
@endif
