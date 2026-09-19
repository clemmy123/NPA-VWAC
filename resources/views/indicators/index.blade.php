@extends('components.main-layout')
@section('title', __('Indicators'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Indicators') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Indicators') }}</li>
            </ol>
        </nav>
    </div>
    @can('indicator.create')
    <a href="{{ route('indicators.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Indicator') }}</a>
    @endcan
</div>

<form method="GET" action="{{ route('indicators.index') }}" class="filter-card report-filters mb-3" id="indicator-filters">
    <div class="rf-field">
        <label for="project_id">{{ __('Project') }}</label>
        <select name="project_id" id="project_id" class="form-control select2" required>
            <option value="">{{ __('Select project…') }}</option>
            @foreach ($projects as $project)
            <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="rf-field">
        <label for="thematic_area_id">{{ __('Thematic Area') }}</label>
        <select name="thematic_area_id" id="thematic_area_id" class="form-control select2" required>
            <option value="">{{ __('Select thematic area…') }}</option>
            @foreach ($thematicAreas as $area)
            <option value="{{ $area->id }}" data-project-id="{{ $area->project_id }}" @selected($selectedThematicAreaId === $area->id)>{{ $area->name }}</option>
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
                <th>{{ __('Code') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Thematic Area') }}</th>
                <th>{{ __('Frequency') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($indicators as $indicator)
            <tr>
                <td>{{ ($indicators->firstItem() ?? 1) + $loop->index }}</td>
                <td><span class="ref-pill">{{ $indicator->code }}</span></td>
                <td><a href="{{ route('indicators.show', $indicator) }}">{{ $indicator->name }}</a></td>
                <td>{{ $indicator->thematicArea?->name ?? '—' }}</td>
                <td>{{ $indicator->reporting_frequency ? __(ucfirst($indicator->reporting_frequency)) : '—' }}</td>
                <td>
                    <span class="s-badge {{ match ($indicator->status) {
                        'active' => 's-active',
                        'completed' => 's-received',
                        'closed' => 's-inactive',
                        'inactive' => 's-inactive',
                        default => 's-default',
                    } }}">{{ __(ucfirst($indicator->status)) }}</span>
                </td>
                <td>
                    <a href="{{ route('indicators.show', $indicator) }}" class="btn-icon" title="{{ __('View') }}"><i class="mdi mdi-eye-outline"></i></a>
                    @can('indicator.update')
                    <a href="{{ route('indicators.edit', $indicator) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
                    @endcan
                    @can('indicator.update')
                    @if ($indicator->status === 'active')
                    <form action="{{ route('indicators.disable', $indicator) }}" method="POST" class="d-inline" data-confirm="{{ __('Disable this indicator?') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-icon danger" title="{{ __('Disable') }}"><i class="mdi mdi-cancel"></i></button>
                    </form>
                    @endif
                    @endcan
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-chart-box-outline"></i>
                        <p>{{ $selectedProjectId && $selectedThematicAreaId ? __('No indicators found for this thematic area.') : __('Select a project and thematic area above to view indicators.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($indicators->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $indicators->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($indicators as $indicator)
    <div class="mob-card">
        <div class="mob-card-top">
            <a href="{{ route('indicators.show', $indicator) }}" class="fw-600">{{ $indicator->name }}</a>
            <span class="s-badge {{ match ($indicator->status) {
                'active' => 's-active',
                'completed' => 's-received',
                'closed' => 's-inactive',
                'inactive' => 's-inactive',
                default => 's-default',
            } }}">{{ __(ucfirst($indicator->status)) }}</span>
        </div>
        <div class="mob-card-body">
            <p class="mob-card-title"><span class="ref-pill">{{ $indicator->code }}</span></p>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-sitemap-outline"></i> {{ $indicator->thematicArea?->name ?? '—' }}</span>
            <span><i class="mdi mdi-calendar-refresh"></i> {{ $indicator->reporting_frequency ? __(ucfirst($indicator->reporting_frequency)) : '—' }}</span>
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('indicators.show', $indicator) }}" class="btn-icon" title="{{ __('View') }}"><i class="mdi mdi-eye-outline"></i></a>
            @can('indicator.update')
            <a href="{{ route('indicators.edit', $indicator) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
            @endcan
            @can('indicator.update')
            @if ($indicator->status === 'active')
            <form action="{{ route('indicators.disable', $indicator) }}" method="POST" class="d-inline" data-confirm="{{ __('Disable this indicator?') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-icon danger" title="{{ __('Disable') }}"><i class="mdi mdi-cancel"></i></button>
            </form>
            @endif
            @endcan
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-chart-box-outline"></i>
        <p>{{ $selectedProjectId && $selectedThematicAreaId ? __('No indicators found for this thematic area.') : __('Select a project and thematic area above to view indicators.') }}</p>
    </div>
    @endforelse

    @if ($indicators->hasPages())
    <div class="mt-3">
        {{ $indicators->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var project = document.getElementById('project_id');
    var thematic = document.getElementById('thematic_area_id');
    var options = Array.from(thematic.querySelectorAll('option[data-project-id]')).map(function (option) { return option.cloneNode(true); });
    var initial = @json((string) $selectedThematicAreaId);
    function filterThematics() {
        var selected = thematic.value || initial;
        thematic.replaceChildren(new Option(project.value ? @json(__('Select thematic area…')) : @json(__('Select project first…')), ''));
        options.forEach(function (option) { if (option.dataset.projectId === project.value) thematic.appendChild(option.cloneNode(true)); });
        thematic.disabled = !project.value;
        if (Array.from(thematic.options).some(function (option) { return option.value === selected; })) thematic.value = selected;
        initial = '';
        if (window.jQuery) jQuery(thematic).trigger('change.select2');
    }
    function submitIndicatorFilter() {
        if (thematic.value) document.getElementById('indicator-filters').submit();
    }
    if (window.jQuery) {
        jQuery(project).on('change select2:select', filterThematics);
        jQuery(thematic).on('change select2:select', submitIndicatorFilter);
    } else {
        project.addEventListener('change', filterThematics);
        thematic.addEventListener('change', submitIndicatorFilter);
    }
    filterThematics();
});
</script>
@endpush
