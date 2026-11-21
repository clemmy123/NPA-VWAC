@extends('components.main-layout')
@section('title', __('Thematic Areas'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Thematic Areas') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Thematic Areas') }}</li>
            </ol>
        </nav>
    </div>
    @can('thematic-area.create')
    <a href="{{ route('thematic-areas.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Thematic Area') }}</a>
    @endcan
</div>

<form method="GET" action="{{ route('thematic-areas.index') }}" class="filter-card report-filters mb-3" id="thematic-area-filters">
    <div class="rf-field flex-grow-1">
        <label for="project_id">{{ __('Project') }}</label>
        <select name="project_id" id="project_id" class="form-control select2" required>
            <option value="">{{ __('Select a project to view thematic areas…') }}</option>
            @foreach ($projects as $project)
            <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
</form>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Project') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($thematicAreas as $thematicArea)
            <tr>
                <td>{{ ($thematicAreas->firstItem() ?? 1) + $loop->index }}</td>
                <td><a href="{{ route('thematic-areas.show', $thematicArea) }}">{{ $thematicArea->localized_name }}</a></td>
                <td>{{ $thematicArea->project?->name ?? '—' }}</td>
                <td>
                    <span class="s-badge {{ match ($thematicArea->status) {
                        'active' => 's-active',
                        'completed' => 's-received',
                        'closed' => 's-inactive',
                        'inactive' => 's-inactive',
                        default => 's-default',
                    } }}">{{ __(ucfirst($thematicArea->status)) }}</span>
                </td>
                <td>
<x-row-actions>
                    <x-row-action href="{{ route('thematic-areas.show', $thematicArea) }}" label="{{ __('View') }}" icon="mdi-eye-outline" tone="sky" />
                    @can('thematic-area.update')
                    <x-row-action href="{{ route('thematic-areas.edit', $thematicArea) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
                    @endcan
                    @can('thematic-area.update')
                    @if ($thematicArea->status === 'active')
                    <x-row-action action="{{ route('thematic-areas.disable', $thematicArea) }}" method="PATCH" label="{{ __('Disable') }}" icon="mdi-cancel" tone="danger" confirm="{{ __('Disable this thematic area?') }}" />
                    @endif
                    @endcan
</x-row-actions>
</td>
            </tr>
            @empty
            <tr>
                <td colspan="5">
                    <div class="tbl-empty">
                        <i class="mdi mdi-sitemap-outline"></i>
                        <p>{{ $selectedProjectId ? __('No thematic areas found for this project.') : __('Select a project above to view its thematic areas.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($thematicAreas->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $thematicAreas->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($thematicAreas as $thematicArea)
    <div class="mob-card">
        <div class="mob-card-top">
            <a href="{{ route('thematic-areas.show', $thematicArea) }}" class="fw-600">{{ $thematicArea->localized_name }}</a>
            <span class="s-badge {{ match ($thematicArea->status) {
                'active' => 's-active',
                'completed' => 's-received',
                'closed' => 's-inactive',
                'inactive' => 's-inactive',
                default => 's-default',
            } }}">{{ __(ucfirst($thematicArea->status)) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-folder-outline"></i> {{ $thematicArea->project?->name ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
<x-row-actions>
            <x-row-action href="{{ route('thematic-areas.show', $thematicArea) }}" label="{{ __('View') }}" icon="mdi-eye-outline" tone="sky" />
            @can('thematic-area.update')
            <x-row-action href="{{ route('thematic-areas.edit', $thematicArea) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
            @endcan
            @can('thematic-area.update')
            @if ($thematicArea->status === 'active')
            <x-row-action action="{{ route('thematic-areas.disable', $thematicArea) }}" method="PATCH" label="{{ __('Disable') }}" icon="mdi-cancel" tone="danger" confirm="{{ __('Disable this thematic area?') }}" />
            @endif
            @endcan
</x-row-actions>
</div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-sitemap-outline"></i>
        <p>{{ $selectedProjectId ? __('No thematic areas found for this project.') : __('Select a project above to view its thematic areas.') }}</p>
    </div>
    @endforelse

    @if ($thematicAreas->hasPages())
    <div class="mt-3">
        {{ $thematicAreas->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var project = document.getElementById('project_id');
    function submitFilter() {
        document.getElementById('thematic-area-filters').submit();
    }
    if (window.jQuery) jQuery(project).on('change select2:select', submitFilter);
    else project.addEventListener('change', submitFilter);
});
</script>
@endpush
