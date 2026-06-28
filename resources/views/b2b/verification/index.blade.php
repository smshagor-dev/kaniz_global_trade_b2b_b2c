@extends('frontend.layouts.app')

@section('content')
<section class="py-4 bg-light">
    <div class="container">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h1 class="h4 mb-3">{{ translate('Verification & Trust Status') }}</h1>
                <div class="alert alert-{{ $trustStatus['tone'] === 'success' ? 'success' : ($trustStatus['tone'] === 'danger' ? 'danger' : 'warning') }} mb-0">
                    {{ $trustStatus['label'] }}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white"><h5 class="mb-0">{{ translate('Uploaded Documents') }}</h5></div>
                    <div class="card-body">
                        @forelse ($documents as $document)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</strong>
                                    <span class="badge badge-inline badge-{{ $document->status === 'approved' ? 'success' : ($document->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($document->status) }}</span>
                                </div>
                                @if ($document->rejection_reason)
                                    <div class="text-danger small mt-2">{{ $document->rejection_reason }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-0">{{ translate('Please upload business documents.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white"><h5 class="mb-0">{{ translate('Upload Document') }}</h5></div>
                    <div class="card-body">
                        <form action="{{ route('dashboard.verification.documents.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label>{{ translate('Document Type') }}</label>
                                <select name="document_type" class="form-control aiz-selectpicker" required>
                                    <option value="business_license">{{ translate('Business License') }}</option>
                                    <option value="tax_certificate">{{ translate('Tax Certificate') }}</option>
                                    <option value="bank_statement">{{ translate('Bank Statement') }}</option>
                                    <option value="company_registration">{{ translate('Company Registration') }}</option>
                                    <option value="address_proof">{{ translate('Address Proof') }}</option>
                                    <option value="other">{{ translate('Other') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Document File') }}</label>
                                <input type="file" name="document" class="form-control" required>
                            </div>
                            <button class="btn btn-primary btn-block">{{ translate('Upload Document') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
