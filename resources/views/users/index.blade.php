@extends('components.main-layout')
@section('title', 'Users')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Users</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Users</li>
            </ol>
        </nav>
    </div>
    @can('user.create')
    <a href="{{ route('users.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New User</a>
    @endcan
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>Name</th><th>Email</th><th>Organization</th><th>Role</th><th>Sign-in</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
            <tr>
                <td>{{ ($users->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->organization?->name ?? '—' }}</td>
                <td>{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                <td>{{ $user->auth_provider === 'local' ? 'Local' : 'Jumuishi SSO' }}</td>
                <td><span class="s-badge {{ $user->status === 'active' ? 's-active' : 's-inactive' }}">{{ ucfirst($user->status) }}</span></td>
                <td>
                    @can('user.update')
                    <a href="{{ route('users.edit', $user) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('{{ $user->status === 'active' ? 'Deactivate' : 'Reactivate' }} this user?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon {{ $user->status === 'active' ? 'danger' : '' }}" title="{{ $user->status === 'active' ? 'Deactivate' : 'Reactivate' }}">
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
                        <p>No users found.</p>
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
            <span class="s-badge {{ $user->status === 'active' ? 's-active' : 's-inactive' }}">{{ ucfirst($user->status) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-email-outline"></i> {{ $user->email }}</span>
            <span><i class="mdi mdi-shield-account-outline"></i> {{ $user->roles->pluck('name')->join(', ') ?: '—' }}</span>
        </div>
        <div class="mob-card-footer">
            @can('user.update')
            <a href="{{ route('users.edit', $user) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('{{ $user->status === 'active' ? 'Deactivate' : 'Reactivate' }} this user?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon {{ $user->status === 'active' ? 'danger' : '' }}" title="{{ $user->status === 'active' ? 'Deactivate' : 'Reactivate' }}">
                    <i class="mdi {{ $user->status === 'active' ? 'mdi-account-off-outline' : 'mdi-account-check-outline' }}"></i>
                </button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-account-group-outline"></i>
        <p>No users found.</p>
    </div>
    @endforelse
    @if ($users->hasPages())
    <div class="mt-3">{{ $users->links() }}</div>
    @endif
</div>
@endsection
