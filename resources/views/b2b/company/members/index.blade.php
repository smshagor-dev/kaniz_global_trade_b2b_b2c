@extends('b2b.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Team & Role Permissions') }}</h1>
                <p class="mb-0 text-muted">{{ $company->company_name }}</p>
            </div>
            <div class="col-md-5 text-md-right">
                @if ($canInvite)
                    <a href="{{ route('b2b.company.members.invite') }}" class="btn btn-primary rounded-0">{{ translate('Invite Member') }}</a>
                @endif
            </div>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-body border-bottom bg-light">
            <p class="mb-0 text-muted">
                {{ translate('Manage team members, assign roles, and control what each supplier or buyer team member can access from this page.') }}
            </p>
            @if (empty($assignableRoles))
                <p class="mb-0 mt-2 text-warning">
                    {{ translate('Team members must use custom roles. Create a role first from the Role Permissions page.') }}
                </p>
            @endif
        </div>
    </div>

    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-header">{{ translate('Members') }}</div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Name') }}</th>
                        <th>{{ translate('Email') }}</th>
                        <th>{{ translate('Role') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Joined At') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>{{ $member->user?->name ?: '-' }}</td>
                            <td>{{ $member->user?->email ?: '-' }}</td>
                            <td>{{ $member->role_label }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($member->status) }}</span></td>
                            <td>{{ $member->joined_at ? $member->joined_at->format('d M, Y h:i A') : '-' }}</td>
                            <td class="text-right">
                                @if ($canInvite && $member->role !== 'owner')
                                    @if (!empty($assignableRoles))
                                        <form action="{{ route('b2b.company.members.update-role', $member->id) }}" method="POST" class="d-inline-block mb-2">
                                            @csrf
                                            <select name="role_selection" class="form-control form-control-sm" onchange="this.form.submit()">
                                                @foreach ($assignableRoles as $role)
                                                    <option value="{{ $role['value'] }}" @selected(($role['custom_role_id'] && (int) $member->custom_role_id === (int) $role['custom_role_id']) || (!$role['custom_role_id'] && $member->role === $role['value']))>{{ translate($role['label']) }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @endif
                                    @if ($member->status !== 'suspended')
                                        <form action="{{ route('b2b.company.members.suspend', $member->id) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <button type="submit" class="btn btn-soft-warning btn-sm">{{ translate('Suspend') }}</button>
                                        </form>
                                    @endif
                                    @if ($member->status !== 'removed')
                                        <form action="{{ route('b2b.company.members.remove', $member->id) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <button type="submit" class="btn btn-soft-danger btn-sm">{{ translate('Remove') }}</button>
                                        </form>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No company members found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card rounded-0 shadow-none border">
        <div class="card-header">{{ translate('Invitations') }}</div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Email') }}</th>
                        <th>{{ translate('Role') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Expires At') }}</th>
                        <th>{{ translate('Invitation Link') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invitations as $invitation)
                        <tr>
                            <td>{{ $invitation->email }}</td>
                            <td>{{ $invitation->role_label }}</td>
                            <td><span class="badge badge-inline badge-secondary">{{ ucfirst($invitation->status) }}</span></td>
                            <td>{{ $invitation->expires_at ? $invitation->expires_at->format('d M, Y h:i A') : '-' }}</td>
                            <td>
                                <a href="{{ route('b2b.company.invitations.accept', $invitation->token) }}" target="_blank">
                                    {{ translate('Open Invitation') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">{{ translate('No invitations found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
