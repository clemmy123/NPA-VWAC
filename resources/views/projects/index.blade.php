@extends('components.main-layout')
@section('title', 'Projects')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Projects</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Projects</li>
            </ol>
        </nav>
    </div>
    @can('project.create')
    <a href="{{ route('projects.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Project</a>
    @endcan
</div>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($projects as $project)
            <tr>
                <td>{{ ($projects->firstItem() ?? 1) + $loop->index }}</td>
                <td><span class="ref-pill">{{ $project->code }}</span></td>
                <td><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></td>
                <td>{{ $project->start_date?->format('d M Y') ?? '—' }}</td>
                <td>{{ $project->end_date?->format('d M Y') ?? '—' }}</td>
                <td>
                    <span class="s-badge {{ match ($project->status) {
                        'active' => 's-active',
                        'completed' => 's-received',
                        'closed' => 's-inactive',
                        default => 's-default',
                    } }}">{{ ucfirst($project->status) }}</span>
                </td>
                <td>
                    <a href="{{ route('projects.show', $project) }}" class="btn-icon" title="View"><i class="mdi mdi-eye-outline"></i></a>
                    @can('project.update')
                    <a href="{{ route('projects.edit', $project) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    @endcan
                    @can('project.delete')
                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete project &quot;{{ $project->name }}&quot;?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                    @endcan
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-folder-open-outline"></i>
                        <p>No projects found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($projects->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $projects->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($projects as $project)
    <div class="mob-card">
        <div class="mob-card-top">
            <a href="{{ route('projects.show', $project) }}" class="fw-600">{{ $project->name }}</a>
            <span class="s-badge {{ match ($project->status) {
                'active' => 's-active',
                'completed' => 's-received',
                'closed' => 's-inactive',
                default => 's-default',
            } }}">{{ ucfirst($project->status) }}</span>
        </div>
        <div class="mob-card-body">
            <p class="mob-card-title"><span class="ref-pill">{{ $project->code }}</span></p>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-start"></i> {{ $project->start_date?->format('d M Y') ?? '—' }}</span>
            <span><i class="mdi mdi-calendar-end"></i> {{ $project->end_date?->format('d M Y') ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('projects.show', $project) }}" class="btn-icon" title="View">
                <i class="mdi mdi-eye-outline"></i>
            </a>
            @can('project.update')
            <a href="{{ route('projects.edit', $project) }}" class="btn-icon" title="Edit">
                <i class="mdi mdi-pencil-outline"></i>
            </a>
            @endcan
            @can('project.delete')
            <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete project &quot;{{ $project->name }}&quot;?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-folder-open-outline"></i>
        <p>No projects found.</p>
    </div>
    @endforelse

    @if ($projects->hasPages())
    <div class="mt-3">
        {{ $projects->links() }}
    </div>
    @endif
</div>
@endsection
