@extends('components.main-layout')
@section('title', 'Organizations')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Organizations</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item active">Organizations</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('organizations.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Organization</a>
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>Name</th><th>Type</th><th>Code</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($organizations as $organization)
            <tr>
                <td>{{ ($organizations->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $organization->name }}</td>
                <td>{{ $organization->organizationType?->name ?? '—' }}</td>
                <td>{{ $organization->code ?? '—' }}</td>
                <td><span class="s-badge {{ $organization->is_active ? 's-active' : 's-inactive' }}">{{ $organization->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <a href="{{ route('organizations.edit', $organization) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('organizations.destroy', $organization) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this organization?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="tbl-empty">
                        <i class="mdi mdi-bank-outline"></i>
                        <p>No organizations found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($organizations->hasPages())
    <div class="px-3 py-2 border-top">{{ $organizations->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($organizations as $organization)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $organization->name }}</span>
            <span class="s-badge {{ $organization->is_active ? 's-active' : 's-inactive' }}">{{ $organization->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-shape-outline"></i> {{ $organization->organizationType?->name ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('organizations.edit', $organization) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('organizations.destroy', $organization) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this organization?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-bank-outline"></i>
        <p>No organizations found.</p>
    </div>
    @endforelse
    @if ($organizations->hasPages())
    <div class="mt-3">{{ $organizations->links() }}</div>
    @endif
</div>
@endsection
