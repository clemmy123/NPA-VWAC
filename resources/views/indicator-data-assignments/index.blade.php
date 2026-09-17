@extends('components.main-layout')
@section('title', 'Indicator Data Assignments')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Indicator Data Assignments</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Data Assignments</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('indicator-data-assignments.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Assignment</a>
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>Indicator</th><th>User</th><th>Organization</th><th>Location</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($assignments as $assignment)
            <tr>
                <td>{{ ($assignments->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $assignment->indicator?->name ?? '—' }}</td>
                <td>{{ $assignment->user?->name ?? '—' }}</td>
                <td>{{ $assignment->organization?->name ?? '—' }}</td>
                <td>
                    @if ($assignment->location_level)
                        {{ ucfirst(str_replace('_', ' ', $assignment->location_level)) }}:
                        {{ $assignment->locationName() ?? '#'.$assignment->location_id }}
                    @else
                        <span class="text-muted">All locations</span>
                    @endif
                </td>
                <td><span class="s-badge {{ $assignment->is_active ? 's-active' : 's-inactive' }}">{{ $assignment->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <a href="{{ route('indicator-data-assignments.edit', $assignment) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('indicator-data-assignments.destroy', $assignment) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this assignment?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-account-arrow-right-outline"></i>
                        <p>No assignments found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($assignments->hasPages())
    <div class="px-3 py-2 border-top">{{ $assignments->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($assignments as $assignment)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $assignment->indicator?->name ?? '—' }}</span>
            <span class="s-badge {{ $assignment->is_active ? 's-active' : 's-inactive' }}">{{ $assignment->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-account-outline"></i> {{ $assignment->user?->name ?? '—' }}</span>
            <span><i class="mdi mdi-bank-outline"></i> {{ $assignment->organization?->name ?? '—' }}</span>
            <span><i class="mdi mdi-map-marker-outline"></i>
                @if ($assignment->location_level)
                    {{ ucfirst(str_replace('_', ' ', $assignment->location_level)) }}: {{ $assignment->locationName() ?? '#'.$assignment->location_id }}
                @else
                    All locations
                @endif
            </span>
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('indicator-data-assignments.edit', $assignment) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('indicator-data-assignments.destroy', $assignment) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this assignment?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-account-arrow-right-outline"></i>
        <p>No assignments found.</p>
    </div>
    @endforelse
    @if ($assignments->hasPages())
    <div class="mt-3">{{ $assignments->links() }}</div>
    @endif
</div>
@endsection
