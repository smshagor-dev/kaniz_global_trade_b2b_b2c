@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Invite Company Member') }}</h1>
                <p class="mb-0 text-muted">{{ $company->company_name }}</p>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="{{ route('b2b.company.members.index') }}" class="btn btn-soft-primary rounded-0">{{ translate('Back to Team') }}</a>
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-body">
            <form action="{{ route('b2b.company.members.send-invite') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>{{ translate('Email Address') }}</label>
                    <input type="email" name="email" class="form-control rounded-0" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                    <label>{{ translate('Role') }}</label>
                    <select name="role" class="form-control aiz-selectpicker rounded-0" required>
                        @foreach (['admin', 'procurement_manager', 'sales_manager', 'finance_manager', 'logistics_manager', 'viewer'] as $role)
                            <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary rounded-0">{{ translate('Send Invitation') }}</button>
            </form>
        </div>
    </div>
@endsection
