@extends('components.main-layout')
@section('title', __('Users'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Users') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Users') }}</li>
            </ol>
        </nav>
    </div>
    @can('user.create')
    <a href="{{ route('users.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New User') }}</a>
    @endcan
</div>

<form method="GET" action="{{ route('users.index') }}" class="filter-card report-filters" id="users-filters">
    <div class="rf-field rf-search">
        <label for="user_search">{{ __('Search') }}</label>
        <input type="search" id="user_search" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('Name or email…') }}">
    </div>
    <div class="rf-field">
        <label for="user_role">{{ __('Role') }}</label>
        <select name="role" id="user_role" class="form-control">
            <option value="">{{ __('All roles') }}</option>
            @foreach ($roles as $role)<option value="{{ $role }}" @selected(request('role') === $role)>{{ __($role) }}</option>@endforeach
        </select>
    </div>
    <div class="rf-field">
        <label for="user_org">{{ __('Organization') }}</label>
        <select name="organization_id" id="user_org" class="form-control">
            <option value="">{{ __('All organizations') }}</option>
            @foreach ($organizations as $organization)<option value="{{ $organization->id }}" @selected((int) request('organization_id') === $organization->id)>{{ $organization->name }}</option>@endforeach
        </select>
    </div>
    <div class="rf-field">
        <label for="user_status">{{ __('Status') }}</label>
        <select name="status" id="user_status" class="form-control">
            <option value="">{{ __('All statuses') }}</option>
            <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
            <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
        </select>
    </div>
    <div class="rf-field rf-actions">
        <button class="btn btn-dark btn-sm"><i class="mdi mdi-magnify"></i> {{ __('Filter') }}</button>
        @if (request()->hasAny(['search', 'role', 'organization_id', 'status']))
        <a href="{{ route('users.index') }}" class="btn btn-outline-dark btn-sm">{{ __('Clear') }}</a>
        @endif
    </div>
</form>

<div class="table-card d-none d-md-block">
    <table class="table mb-0 users-table">
        <thead>
            <tr><th>#</th><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Organization') }}</th><th>{{ __('Role') }}</th><th>{{ __('Sign-in') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
            <tr>
                <td>{{ ($users->firstItem() ?? 1) + $loop->index }}</td>
                <td>
                    <div class="usr-cell">
                        <span class="usr-avatar">{{ \Illuminate\Support\Str::of($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') }}</span>
                        <span class="usr-name">{{ $user->name }}</span>
                    </div>
                </td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->organization?->name ?? '—' }}</td>
                <td>@forelse ($user->roles as $role)<span class="usr-role usr-role-{{ \Illuminate\Support\Str::slug($role->name) }}">{{ __($role->name) }}</span>@empty — @endforelse</td>
                <td>{{ $user->auth_provider === 'local' ? __('Local') : __('Jumuishi SSO') }}</td>
                <td><span class="s-badge {{ $user->status === 'active' ? 's-active' : 's-inactive' }}">{{ __(ucfirst($user->status)) }}</span></td>
                <td>
                    @can('user.update')
                    <a href="{{ route('users.edit', $user) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                          data-confirm="{{ $user->status === 'active' ? __('Deactivate this user?') : __('Reactivate this user?') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon {{ $user->status === 'active' ? 'danger' : '' }}" title="{{ $user->status === 'active' ? __('Deactivate') : __('Reactivate') }}">
                            <i class="mdi {{ $user->status === 'active' ? 'mdi-account-off-outline' : 'mdi-account-check-outline' }}"></i>
                        </button>
                    </form>
                    @endcan
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8">
                    <div class="tbl-empty">
                        <i class="mdi mdi-account-group-outline"></i>
                        <p>{{ __('No users found.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($users->hasPages())
    <div class="px-3 py-2 border-top">{{ $users->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($users as $user)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $user->name }}</span>
            <span class="s-badge {{ $user->status === 'active' ? 's-active' : 's-inactive' }}">{{ __(ucfirst($user->status)) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-email-outline"></i> {{ $user->email }}</span>
            <span><i class="mdi mdi-shield-account-outline"></i> {{ $user->roles->pluck('name')->join(', ') ?: '—' }}</span>
        </div>
        <div class="mob-card-footer">
            @can('user.update')
            <a href="{{ route('users.edit', $user) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                  data-confirm="{{ $user->status === 'active' ? __('Deactivate this user?') : __('Reactivate this user?') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon {{ $user->status === 'active' ? 'danger' : '' }}" title="{{ $user->status === 'active' ? __('Deactivate') : __('Reactivate') }}">
                    <i class="mdi {{ $user->status === 'active' ? 'mdi-account-off-outline' : 'mdi-account-check-outline' }}"></i>
                </button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-account-group-outline"></i>
        <p>{{ __('No users found.') }}</p>
    </div>
    @endforelse
    @if ($users->hasPages())
    <div class="mt-3">{{ $users->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
@include('reports.partials.filter-selects-script', ['formId' => 'users-filters', 'autoSubmit' => true])
@endpush
