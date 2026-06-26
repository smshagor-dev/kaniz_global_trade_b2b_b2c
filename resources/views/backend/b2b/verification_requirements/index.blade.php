@extends('backend.layouts.app')

@section('content')
    @php
        $typeLabels = array_combine($fieldTypes, array_map(fn ($type) => ucfirst($type), $fieldTypes));
    @endphp

    <div class="aiz-titlebar text-left pb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Verification Requirements') }}</h1>
            </div>
            <div class="col-md-6 text-right">
                <a href="{{ route('admin.b2b.companies.verification') }}" class="btn btn-soft-primary">
                    {{ translate('Back to Verification') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Add Requirement') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.b2b.verification-requirements.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>{{ translate('Label') }}</label>
                            <input type="text" class="form-control" name="label" value="{{ old('label') }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Slug') }}</label>
                            <input type="text" class="form-control" name="slug" value="{{ old('slug') }}" placeholder="owner-passport">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Field Type') }}</label>
                            <select class="form-control aiz-selectpicker" name="field_type" required>
                                @foreach ($typeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('field_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Placeholder') }}</label>
                            <input type="text" class="form-control" name="placeholder" value="{{ old('placeholder') }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Help Text') }}</label>
                            <textarea class="form-control" name="help_text" rows="3">{{ old('help_text') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Company Types') }}</label>
                            <select class="form-control aiz-selectpicker" name="company_types[]" multiple data-selected-text-format="count">
                                @foreach ($companyTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Sort Order') }}</label>
                            <input type="number" class="form-control" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                        </div>
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="is_required" value="1" @checked(old('is_required'))>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Required') }}</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="aiz-checkbox">
                                <input type="checkbox" name="is_active" value="1" checked>
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Active') }}</span>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ translate('Create Requirement') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Configured Requirements') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table aiz-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ translate('Requirement') }}</th>
                                    <th>{{ translate('Type') }}</th>
                                    <th>{{ translate('Applies To') }}</th>
                                    <th>{{ translate('Flags') }}</th>
                                    <th>{{ translate('Sort') }}</th>
                                    <th class="text-right">{{ translate('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requirements as $requirement)
                                    <tr>
                                        <td>
                                            <div class="fw-600">{{ $requirement->label }}</div>
                                            <small class="text-muted">{{ $requirement->slug }}</small>
                                            @if ($requirement->help_text)
                                                <div class="text-muted mt-1">{{ $requirement->help_text }}</div>
                                            @endif
                                        </td>
                                        <td>{{ ucfirst($requirement->field_type) }}</td>
                                        <td>
                                            @if ($requirement->company_types)
                                                {{ collect($requirement->company_types)->map(fn ($type) => $companyTypes[$type] ?? ucfirst($type))->implode(', ') }}
                                            @else
                                                {{ translate('All company types') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($requirement->is_required)
                                                <span class="badge badge-inline badge-danger">{{ translate('Required') }}</span>
                                            @endif
                                            @if ($requirement->is_active)
                                                <span class="badge badge-inline badge-success">{{ translate('Active') }}</span>
                                            @else
                                                <span class="badge badge-inline badge-secondary">{{ translate('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $requirement->sort_order }}</td>
                                        <td class="text-right">
                                            <button class="btn btn-soft-primary btn-sm" type="button" data-toggle="collapse" data-target="#edit-requirement-{{ $requirement->id }}">
                                                {{ translate('Edit') }}
                                            </button>
                                            <form action="{{ route('admin.b2b.verification-requirements.delete', $requirement->id) }}" method="POST" class="d-inline-block">
                                                @csrf
                                                <button type="submit" class="btn btn-soft-danger btn-sm">{{ translate('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="edit-requirement-{{ $requirement->id }}">
                                        <td colspan="6" class="bg-light">
                                            <form action="{{ route('admin.b2b.verification-requirements.update', $requirement->id) }}" method="POST">
                                                @csrf
                                                <div class="row">
                                                    <div class="col-md-4 form-group">
                                                        <label>{{ translate('Label') }}</label>
                                                        <input type="text" class="form-control" name="label" value="{{ $requirement->label }}" required>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>{{ translate('Slug') }}</label>
                                                        <input type="text" class="form-control" name="slug" value="{{ $requirement->slug }}">
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>{{ translate('Field Type') }}</label>
                                                        <select class="form-control aiz-selectpicker" name="field_type" required>
                                                            @foreach ($typeLabels as $value => $label)
                                                                <option value="{{ $value }}" @selected($requirement->field_type === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>{{ translate('Placeholder') }}</label>
                                                        <input type="text" class="form-control" name="placeholder" value="{{ $requirement->placeholder }}">
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>{{ translate('Sort Order') }}</label>
                                                        <input type="number" class="form-control" name="sort_order" value="{{ $requirement->sort_order }}" min="0">
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>{{ translate('Company Types') }}</label>
                                                        <select class="form-control aiz-selectpicker" name="company_types[]" multiple data-selected-text-format="count">
                                                            @foreach ($companyTypes as $value => $label)
                                                                <option value="{{ $value }}" @selected(in_array($value, $requirement->company_types ?? [], true))>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-8 form-group">
                                                        <label>{{ translate('Help Text') }}</label>
                                                        <textarea class="form-control" name="help_text" rows="3">{{ $requirement->help_text }}</textarea>
                                                    </div>
                                                    <div class="col-md-2 form-group">
                                                        <label class="aiz-checkbox mt-4">
                                                            <input type="checkbox" name="is_required" value="1" @checked($requirement->is_required)>
                                                            <span class="aiz-square-check"></span>
                                                            <span>{{ translate('Required') }}</span>
                                                        </label>
                                                    </div>
                                                    <div class="col-md-2 form-group">
                                                        <label class="aiz-checkbox mt-4">
                                                            <input type="checkbox" name="is_active" value="1" @checked($requirement->is_active)>
                                                            <span class="aiz-square-check"></span>
                                                            <span>{{ translate('Active') }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm">{{ translate('Save Changes') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ translate('No verification requirements configured yet') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
