@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Role Permissions') }}</h1>
                <p class="mb-0 text-muted">{{ $company->company_name }}</p>
            </div>
            <div class="col-md-4 text-md-right">
                @if ($canManageRoles)
                    <a href="{{ route('b2b.company.members.index') }}" class="btn btn-primary rounded-0">{{ translate('Manage Team Roles') }}</a>
                @endif
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-body">
            <p class="mb-2 text-muted">
                {{ translate('These are the available role permissions for your own company workspace. Each company can create and assign roles only inside its own team.') }}
            </p>
            @if (!$canManageRoles)
                <div class="alert alert-soft-warning mb-0">
                    {{ translate('You can view role permissions, but only the company owner or admin can create custom roles.') }}
                </div>
            @elseif (!$canEditOrDeleteRoles)
                <div class="alert alert-soft-warning mb-0">
                    {{ translate('Only the company owner can edit or delete custom roles.') }}
                </div>
            @endif
        </div>
    </div>

    @if ($canManageRoles)
        <div class="card rounded-0 shadow-none border mb-4">
            <div class="card-header">{{ translate('Create Custom Role') }}</div>
            <div class="card-body">
                <form action="{{ route('b2b.company.roles.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>{{ translate('Role Name') }}</label>
                        <input type="text" name="name" class="form-control rounded-0" placeholder="{{ translate('Ex: Export Coordinator') }}" required>
                    </div>
                    <div class="row">
                        @foreach ($permissionOptions as $permissionKey => $permissionLabel)
                            <div class="col-md-4">
                                <label class="aiz-checkbox mb-3">
                                    <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}">
                                    <span class="aiz-square-check"></span>
                                    <span>{{ translate($permissionLabel) }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary rounded-0">{{ translate('Create Role') }}</button>
                </form>
            </div>
        </div>
    @endif

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-header">{{ translate('Role Permission Matrix') }}</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Role') }}</th>
                            <th>{{ translate('Permissions') }}</th>
                            <th class="text-right">{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roleMatrix as $roleKey => $role)
                            <tr>
                                <td class="fw-600">{{ translate($role['label']) }}</td>
                                <td>
                                    {{ collect($role['permissions'])->map(fn ($permission) => translate($permission))->implode(', ') }}
                                </td>
                                <td class="text-right">
                                    @if (($role['type'] ?? 'system') === 'custom')
                                        @if ($canEditOrDeleteRoles)
                                            <a href="#custom-role-{{ $role['custom_role_id'] }}" class="btn btn-soft-primary btn-sm rounded-0 mr-2">
                                                {{ translate('Edit') }}
                                            </a>
                                            <form action="{{ route('b2b.company.roles.delete', $role['custom_role_id']) }}" method="POST" class="d-inline-block">
                                                @csrf
                                                <button type="submit" class="btn btn-soft-danger btn-sm rounded-0">
                                                    {{ translate('Delete') }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="badge badge-inline badge-soft-secondary">{{ translate('View Only') }}</span>
                                        @endif
                                    @else
                                        <span class="badge badge-inline badge-soft-info">{{ translate('System Role') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($canManageRoles)
        <div class="card rounded-0 shadow-none border mb-4">
            <div class="card-header">{{ translate('Custom Roles') }}</div>
            <div class="card-body">
                @forelse ($customRoles as $customRole)
                    <div class="border rounded p-3 mb-3" id="custom-role-{{ $customRole->id }}">
                        @if ($canEditOrDeleteRoles)
                            <form action="{{ route('b2b.company.roles.update', $customRole->id) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>{{ translate('Role Name') }}</label>
                                    <input type="text" name="name" class="form-control rounded-0" value="{{ $customRole->name }}" required>
                                </div>
                                <div class="row">
                                    @foreach ($permissionOptions as $permissionKey => $permissionLabel)
                                        <div class="col-md-4">
                                            <label class="aiz-checkbox mb-3">
                                                <input type="checkbox" name="permissions[]" value="{{ $permissionKey }}" @checked((bool) data_get($customRole->permissions, $permissionKey, false))>
                                                <span class="aiz-square-check"></span>
                                                <span>{{ translate($permissionLabel) }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary rounded-0 mr-2 mb-2">{{ translate('Update Role') }}</button>
                            </form>
                            <form action="{{ route('b2b.company.roles.delete', $customRole->id) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-soft-danger rounded-0">{{ translate('Delete Role') }}</button>
                            </form>
                        @else
                            <div class="form-group">
                                <label>{{ translate('Role Name') }}</label>
                                <input type="text" class="form-control rounded-0" value="{{ $customRole->name }}" disabled>
                            </div>
                            <div class="row">
                                @foreach ($permissionOptions as $permissionKey => $permissionLabel)
                                    <div class="col-md-4">
                                        <label class="aiz-checkbox mb-3">
                                            <input type="checkbox" value="{{ $permissionKey }}" @checked((bool) data_get($customRole->permissions, $permissionKey, false)) disabled>
                                            <span class="aiz-square-check"></span>
                                            <span>{{ translate($permissionLabel) }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ translate('No custom roles created yet.') }}</p>
                @endforelse
            </div>
        </div>
    @endif

    <div class="card rounded-0 shadow-none border">
        <div class="card-header">{{ translate('Current Team Role Assignments') }}</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Name') }}</th>
                            <th>{{ translate('Email') }}</th>
                            <th>{{ translate('Role') }}</th>
                            <th>{{ translate('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr>
                                <td>{{ $member->user?->name ?: '-' }}</td>
                                <td>{{ $member->user?->email ?: '-' }}</td>
                                <td>{{ $member->role_label }}</td>
                                <td>{{ ucfirst($member->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">{{ translate('No team members found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
