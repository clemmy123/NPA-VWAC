@extends('components.main-layout')
@section('title', __('New Data Collection'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('New Data Collection') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicator-data-entries.index') }}">{{ __('Data Collections') }}</a></li>
                <li class="breadcrumb-item active">{{ __('New') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <form action="{{ route('indicator-data-entries.start') }}" method="POST">
        @csrf
        <div class="row g-3">
        <div class="col-lg-4">
            <label for="project_id" class="form-label">{{ __('Project') }}</label>
            <select name="project_id" id="project_id" class="form-control @error('project_id') is-invalid @enderror" data-no-select2 required>
                <option value="">{{ __('Select a project…') }}</option>
                @foreach ($projects as $project)
                @php($canCollectProject = $collectableProjectIds->contains((int) $project->id))
                <option value="{{ $project->id }}" @disabled(! $canCollectProject) @selected((int) old('project_id') === $project->id && $canCollectProject)>
                    {{ $project->code ? $project->code.' · ' : '' }}{{ $project->name }}{{ $canCollectProject ? '' : ' — '.__('No assigned active indicator') }}
                </option>
                @endforeach
            </select>
            @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">{{ __('Projects without an assigned active indicator are shown for clarity but cannot start a collection.') }}</div>
        </div>

        <div class="col-lg-4">
            <label for="thematic_area_id" class="form-label">{{ __('Thematic Area') }}</label>
            <select name="thematic_area_id" id="thematic_area_id" class="form-control @error('thematic_area_id') is-invalid @enderror" data-no-select2 required>
                <option value="">{{ __('Select a project first…') }}</option>
                @foreach ($thematicAreas as $thematicArea)
                <option value="{{ $thematicArea->id }}" data-project-id="{{ $thematicArea->project_id }}" @selected((int) old('thematic_area_id') === $thematicArea->id)>{{ $thematicArea->name }}</option>
                @endforeach
            </select>
            @error('thematic_area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-lg-4">
            <label for="indicator_id" class="form-label">{{ __('Indicator') }}</label>
            <select name="indicator_id" id="indicator_id" class="form-control @error('indicator_id') is-invalid @enderror" data-no-select2 required>
                <option value="">{{ __('Select a thematic area first…') }}</option>
                @foreach ($indicators as $indicator)
                <option value="{{ $indicator->id }}" data-thematic-area-id="{{ $indicator->thematic_area_id }}" @selected((int) old('indicator_id') === $indicator->id)>{{ $indicator->code ? $indicator->code.' · ' : '' }}{{ $indicator->name }}</option>
                @endforeach
            </select>
            @error('indicator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <p class="text-muted small mt-1">{{ __('The indicator-specific collection fields will appear after this draft is started.') }}</p>
        </div>
        </div>

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-arrow-right"></i> {{ __('Start Collection') }}</button>
            <a href="{{ route('indicator-data-entries.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var project = document.getElementById('project_id');
    var thematic = document.getElementById('thematic_area_id');
    var indicator = document.getElementById('indicator_id');
    var thematicOptions = Array.from(thematic.querySelectorAll('option[data-project-id]'))
        .map(function (option) { return option.cloneNode(true); });
    var indicatorOptions = Array.from(indicator.querySelectorAll('option[data-thematic-area-id]'))
        .map(function (option) { return option.cloneNode(true); });
    var initialThematicId = @json((string) old('thematic_area_id', ''));
    var initialIndicatorId = @json((string) old('indicator_id', ''));

    function filterThematicAreas() {
        var projectId = project.value;
        var selectedId = thematic.value || initialThematicId;
        thematic.replaceChildren(new Option(
            projectId ? @json(__('Select a thematic area…')) : @json(__('Select a project first…')),
            ''
        ));
        thematicOptions.forEach(function (option) {
            if (option.dataset.projectId === projectId) thematic.appendChild(option.cloneNode(true));
        });
        thematic.disabled = projectId === '';
        if (Array.from(thematic.options).some(function (option) { return option.value === selectedId; })) {
            thematic.value = selectedId;
        }
        initialThematicId = '';
        filterIndicators();
    }

    function filterIndicators() {
        var thematicId = thematic.value;
        var selectedId = indicator.value || initialIndicatorId;
        indicator.replaceChildren(new Option(
            thematicId ? @json(__('Select an indicator…')) : @json(__('Select a thematic area first…')),
            ''
        ));
        indicatorOptions.forEach(function (option) {
            if (option.dataset.thematicAreaId === thematicId) {
                indicator.appendChild(option.cloneNode(true));
            }
        });
        indicator.disabled = thematicId === '';
        if (Array.from(indicator.options).some(function (option) { return option.value === selectedId; })) {
            indicator.value = selectedId;
        }
        initialIndicatorId = '';
    }

    project.addEventListener('change', filterThematicAreas);
    thematic.addEventListener('change', filterIndicators);
    filterThematicAreas();
});
</script>
@endpush
