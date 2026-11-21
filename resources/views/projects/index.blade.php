@extends('components.main-layout')
@section('title', __('Projects'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Projects') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Projects') }}</li>
            </ol>
        </nav>
    </div>
    @can('project.create')
    <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Project') }}</a>
    @endcan
</div>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Start Date') }}</th>
                <th>{{ __('End Date') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Actions') }}</th>
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
                    } }}">{{ __(ucfirst($project->status)) }}</span>
                </td>
                <td>
<x-row-actions>
                    <x-row-action href="{{ route('projects.show', $project) }}" label="{{ __('View') }}" icon="mdi-eye-outline" tone="sky" />
                    @can('project.update')
                    <x-row-action href="{{ route('projects.edit', $project) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
                    @endcan
                    @can('project.delete')
                    <x-row-action action="{{ route('projects.destroy', $project) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete project ":name"?', ['name' => $project->name]) }}" />
                    @endcan
</x-row-actions>
</td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-folder-open-outline"></i>
                        <p>{{ __('No projects found.') }}</p>
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
            } }}">{{ __(ucfirst($project->status)) }}</span>
        </div>
        <div class="mob-card-body">
            <p class="mob-card-title"><span class="ref-pill">{{ $project->code }}</span></p>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-start"></i> {{ $project->start_date?->format('d M Y') ?? '—' }}</span>
            <span><i class="mdi mdi-calendar-end"></i> {{ $project->end_date?->format('d M Y') ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
<x-row-actions>
            <x-row-action href="{{ route('projects.show', $project) }}" label="{{ __('View') }}" icon="mdi-eye-outline" tone="sky" />
            @can('project.update')
            <x-row-action href="{{ route('projects.edit', $project) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
            @endcan
            @can('project.delete')
            <x-row-action action="{{ route('projects.destroy', $project) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete project ":name"?', ['name' => $project->name]) }}" />
            @endcan
</x-row-actions>
</div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-folder-open-outline"></i>
        <p>{{ __('No projects found.') }}</p>
    </div>
    @endforelse

    @if ($projects->hasPages())
    <div class="mt-3">
        {{ $projects->links() }}
    </div>
    @endif
</div>
@endsection
