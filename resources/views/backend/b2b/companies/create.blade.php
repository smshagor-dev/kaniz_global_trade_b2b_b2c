@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left pb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Add B2B Company') }}</h1>
            </div>
            <div class="col-md-6 text-right">
                <a href="{{ route('admin.b2b.companies.index') }}" class="btn btn-soft-primary">
                    {{ translate('Back to list') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-header pt-4 border-bottom-0">
            <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Company Profile') }}</h5>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.b2b.companies.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group row">
                    <label class="col-md-3 col-form-label fs-14">{{ translate('Owner User') }} <span class="text-danger">*</span></label>
                    <div class="col-md-9">
                        <select class="form-control aiz-selectpicker" name="user_id" data-live-search="true" required>
                            <option value="">{{ translate('Select User') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                    {{ $user->name }}{{ $user->email ? ' (' . $user->email . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @include('b2b.company._form', [
                    'title' => translate('Company Information'),
                    'action' => route('admin.b2b.companies.store'),
                    'submitText' => translate('Create Company'),
                    'verificationRequirements' => $verificationRequirements,
                    'renderCard' => false,
                    'renderForm' => false,
                ])
            </form>
        </div>
    </div>
@endsection
