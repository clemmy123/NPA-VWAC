@extends('components.main-layout')
@section('title', $thematicArea->localized_name)

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ $thematicArea->localized_name }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">{{ __('Projects') }}</a></li>
                @if ($thematicArea->project)
                <li class="breadcrumb-item"><a href="{{ route('projects.show', $thematicArea->project) }}">{{ $thematicArea->project->name }}</a></li>
                @endif
                <li class="breadcrumb-item active">{{ $thematicArea->localized_name }}</li>
            </ol>
        </nav>
    </div>
    @can('thematic-area.update')
    <a href="{{ route('thematic-areas.edit', $thematicArea) }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-pencil-outline"></i> {{ __('Edit Thematic Area') }}</a>
    @endcan
</div>

<div class="chart-card mb-4">
    <div class="row">
        <div class="col-md-4 mb-2"><span class="text-muted small">{{ __('Project') }}</span><div>{{ $thematicArea->project?->name ?? '—' }}</div></div>
        <div class="col-md-4 mb-2"><span class="text-muted small">{{ __('Status') }}</span><div>
            <span class="s-badge {{ match ($thematicArea->status) {
                'active' => 's-active', 'completed' => 's-received', 'closed', 'inactive' => 's-inactive', default => 's-default',
            } }}">{{ __(ucfirst($thematicArea->status)) }}</span>
        </div></div>
        @if ($thematicArea->description)
        <div class="col-12 mt-2"><span class="text-muted small">{{ __('Description') }}</span><p class="mb-0">{{ $thematicArea->description }}</p></div>
        @endif
    </div>
</div>

{{-- Thematic Area Coordinators --}}
@can('thematic-area.assign-manager')
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">{{ __('Thematic Area Coordinators') }}</h5>
    <div class="d-flex gap-2">
    @unless ($managers->contains('id', auth()->id()))
    <form method="POST" action="{{ route('thematic-areas.managers.store', $thematicArea) }}">
        @csrf
        <input type="hidden" name="user_id" value="{{ auth()->id() }}">
        <input type="hidden" name="can_collect" value="1">
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-account-plus-outline"></i> {{ __('Assign me') }}</button>
    </form>
    @endunless
    <button type="button" class="btn btn-primary btn-sm" data-quick-add="tpl-add-ta-manager" data-quick-add-title="{{ __('Assign Thematic Area Coordinator to ":name"', ['name' => $thematicArea->localized_name]) }}">
        <i class="mdi mdi-plus"></i> {{ __('Assign Manager') }}
    </button>
    </div>
</div>

<div class="table-card mb-4">
    <table class="table mb-0">
        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Collects data') }}</th><th>{{ __('Actions') }}</th></tr></thead>
        <tbody>
            @forelse ($managers as $manager)
            <tr>
                <td>{{ $manager->name }}</td>
                <td>{{ $manager->email }}</td>
                <td>{{ $manager->pivot->can_collect ? __('Yes') : __('No') }}</td>
                <td>
<x-row-actions>
                    <x-row-action :action="route('thematic-areas.managers.store', $thematicArea)" method="POST" :label="$manager->pivot->can_collect ? __('Stop collecting data') : __('Allow collecting data')" :icon="$manager->pivot->can_collect ? 'mdi-database-off-outline' : 'mdi-database-plus-outline'" :fields="['user_id' => $manager->id, 'can_collect' => $manager->pivot->can_collect ? 0 : 1]" redirect />
                    <x-row-action action="{{ route('thematic-areas.managers.destroy', [$thematicArea, $manager]) }}" method="DELETE" label="{{ __('Remove') }}" icon="mdi-close" tone="danger" confirm="{{ __('Remove ":name" as a manager of this thematic area?', ['name' => $manager->name]) }}" redirect />
</x-row-actions>
</td>
            </tr>
            @empty
            <tr><td colspan="4"><div class="tbl-empty"><i class="mdi mdi-account-outline"></i><p>{{ __('No thematic managers assigned yet.') }}</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<template id="tpl-add-ta-manager">
    <form method="POST" action="{{ route('thematic-areas.managers.store', $thematicArea) }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        <div class="mb-3">
            <label for="user_id" class="form-label">{{ __('User') }}</label>
            <select name="user_id" id="user_id" class="form-control select2" required>
                <option value="">{{ __('Select a Thematic Area Coordinator…') }}</option>
                @foreach ($assignableManagers as $user)
                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            <div class="form-check mt-2">
                <input type="hidden" name="can_collect" value="0">
                <input type="checkbox" class="form-check-input" name="can_collect" id="can_collect" value="1" checked>
                <label class="form-check-label" for="can_collect">{{ __('Can collect data for this thematic area') }}</label>
            </div>
            <div class="form-text">{{ __('Only users with the "Thematic Area Coordinator" role are listed. Assign the role first under Users if it\'s missing.') }}</div>
        </div>
        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline"></i> {{ __('Assign') }}</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        </div>
    </form>
</template>
@endcan

{{-- Indicators --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">{{ __('Indicators') }}</h5>
    @can('indicator.create')
    <button type="button" class="btn btn-primary btn-sm" data-quick-add="tpl-add-indicator" data-quick-add-title="{{ __('Add Indicator to ":name"', ['name' => $thematicArea->localized_name]) }}">
        <i class="mdi mdi-plus"></i> {{ __('Add Indicator') }}
    </button>
    @endcan
</div>

<div class="table-card d-none d-md-block mb-4">
    <table class="table mb-0">
        <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Frequency') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead>
        <tbody>
            @forelse ($indicators as $indicator)
            <tr>
                <td><span class="ref-pill">{{ $indicator->code }}</span></td>
                <td><a href="{{ route('indicators.show', $indicator) }}">{{ $indicator->name }}</a></td>
                <td>{{ $indicator->reporting_frequency ? __(ucfirst($indicator->reporting_frequency)) : '—' }}</td>
                <td>
                    <span class="s-badge {{ match ($indicator->status) {
                        'active' => 's-active', 'completed' => 's-received', 'closed', 'inactive' => 's-inactive', default => 's-default',
                    } }}">{{ __(ucfirst($indicator->status)) }}</span>
                </td>
                <td>
<x-row-actions>
                    @can('indicator.update')
                    <x-row-action href="{{ route('indicators.edit', $indicator) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
                    @endcan
                    @if ($indicator->status === 'active')
                    <x-row-action action="{{ route('indicators.disable', $indicator) }}" method="PATCH" label="{{ __('Disable') }}" icon="mdi-cancel" tone="danger" confirm="{{ __('Disable this indicator?') }}" redirect />
                    @endif
</x-row-actions>
</td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="tbl-empty"><i class="mdi mdi-chart-box-outline"></i><p>{{ __('No indicators yet.') }}</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-md-none mb-4">
    @forelse ($indicators as $indicator)
    <div class="mob-card">
        <div class="mob-card-top">
            <a href="{{ route('indicators.show', $indicator) }}" class="fw-600">{{ $indicator->name }}</a>
            <span class="s-badge {{ match ($indicator->status) {
                'active' => 's-active', 'completed' => 's-received', 'closed', 'inactive' => 's-inactive', default => 's-default',
            } }}">{{ __(ucfirst($indicator->status)) }}</span>
        </div>
        <div class="mob-card-body"><p class="mob-card-title"><span class="ref-pill">{{ $indicator->code }}</span></p></div>
        <div class="mob-card-footer">
<x-row-actions>
            @can('indicator.update')
            <x-row-action href="{{ route('indicators.edit', $indicator) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
            @endcan
            @if ($indicator->status === 'active')
            <x-row-action action="{{ route('indicators.disable', $indicator) }}" method="PATCH" label="{{ __('Disable') }}" icon="mdi-cancel" tone="danger" confirm="{{ __('Disable this indicator?') }}" redirect />
            @endif
</x-row-actions>
</div>
    </div>
    @empty
    <div class="tbl-empty"><i class="mdi mdi-chart-box-outline"></i><p>{{ __('No indicators yet.') }}</p></div>
    @endforelse
</div>

{{-- Interventions --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">{{ __('Interventions') }}</h5>
    @can('intervention.create')
    <button type="button" class="btn btn-primary btn-sm" data-quick-add="tpl-add-intervention" data-quick-add-title="{{ __('Add Intervention to ":name"', ['name' => $thematicArea->localized_name]) }}">
        <i class="mdi mdi-plus"></i> {{ __('Add Intervention') }}
    </button>
    @endcan
</div>

<div class="table-card d-none d-md-block mb-4">
    <table class="table mb-0">
        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Linked Indicators') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead>
        <tbody>
            @forelse ($interventions as $intervention)
            <tr>
                <td>{{ $intervention->name }}</td>
                <td>{{ $intervention->indicators->pluck('name')->implode(', ') ?: '—' }}</td>
                <td>
                    <span class="s-badge {{ match ($intervention->status) {
                        'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
                    } }}">{{ __(ucfirst($intervention->status)) }}</span>
                </td>
                <td>
<x-row-actions>
                    @can('intervention.update')
                    <x-row-action href="{{ route('interventions.edit', $intervention) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
                    @endcan
                    @can('intervention.delete')
                    <x-row-action action="{{ route('interventions.destroy', $intervention) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete intervention ":name"?', ['name' => $intervention->name]) }}" redirect />
                    @endcan
</x-row-actions>
</td>
            </tr>
            @empty
            <tr><td colspan="4"><div class="tbl-empty"><i class="mdi mdi-rocket-launch-outline"></i><p>{{ __('No interventions yet.') }}</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-md-none mb-4">
    @forelse ($interventions as $intervention)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $intervention->name }}</span>
            <span class="s-badge {{ match ($intervention->status) {
                'active' => 's-active', 'completed' => 's-received', 'closed' => 's-inactive', default => 's-default',
            } }}">{{ __(ucfirst($intervention->status)) }}</span>
        </div>
        <div class="mob-card-meta"><span>{{ $intervention->indicators->pluck('name')->implode(', ') ?: '—' }}</span></div>
        <div class="mob-card-footer">
<x-row-actions>
            @can('intervention.update')
            <x-row-action href="{{ route('interventions.edit', $intervention) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
            @endcan
            @can('intervention.delete')
            <x-row-action action="{{ route('interventions.destroy', $intervention) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete intervention ":name"?', ['name' => $intervention->name]) }}" redirect />
            @endcan
</x-row-actions>
</div>
    </div>
    @empty
    <div class="tbl-empty"><i class="mdi mdi-rocket-launch-outline"></i><p>{{ __('No interventions yet.') }}</p></div>
    @endforelse
</div>

@can('indicator.create')
<template id="tpl-add-indicator">
    <form method="POST" action="{{ route('indicators.store') }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        @include('indicators.form', [
            'indicator' => new \App\Models\Indicator(['thematic_area_id' => $thematicArea->id]),
            'statusOptions' => $indicatorStatusOptions,
            'projects' => collect([$thematicArea->project])->filter(),
            'thematicAreas' => collect([$thematicArea]),
            'measurementTypes' => $measurementTypes,
            'unitsOfMeasure' => $unitsOfMeasure,
        ])
        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline"></i> {{ __('Save') }}</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        </div>
    </form>
</template>
@endcan

@can('intervention.create')
<template id="tpl-add-intervention">
    <form method="POST" action="{{ route('interventions.store') }}">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        @include('interventions.form', [
            'intervention' => new \App\Models\Intervention(['thematic_area_id' => $thematicArea->id]),
            'statusOptions' => $interventionStatusOptions,
            'thematicAreas' => collect([$thematicArea]),
            'indicators' => $allIndicators,
        ])
        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline"></i> {{ __('Save') }}</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        </div>
    </form>
</template>
@endcan
@endsection
