@extends('components.main-layout')
@section('title', __('Interventions'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Interventions') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Interventions') }}</li>
            </ol>
        </nav>
    </div>
    @can('intervention.create')
    <a href="{{ route('interventions.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Intervention') }}</a>
    @endcan
</div>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Thematic Area') }}</th>
                <th>{{ __('Indicators') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($interventions as $intervention)
            <tr>
                <td>{{ ($interventions->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $intervention->name }}</td>
                <td>{{ $intervention->thematicArea?->name ?? '—' }}</td>
                <td>{{ $intervention->indicators->count() }}</td>
                <td>
                    <span class="s-badge {{ match ($intervention->status) {
                        'active' => 's-active',
                        'completed' => 's-received',
                        'closed' => 's-inactive',
                        default => 's-default',
                    } }}">{{ __(ucfirst($intervention->status)) }}</span>
                </td>
                <td>
<x-row-actions>
                    @can('intervention.update')
                    <x-row-action href="{{ route('interventions.edit', $intervention) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
                    @endcan
                    @can('intervention.delete')
                    <x-row-action action="{{ route('interventions.destroy', $intervention) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete intervention ":name"?', ['name' => $intervention->name]) }}" />
                    @endcan
</x-row-actions>
</td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="tbl-empty">
                        <i class="mdi mdi-hand-heart-outline"></i>
                        <p>{{ __('No interventions found.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($interventions->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $interventions->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($interventions as $intervention)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $intervention->name }}</span>
            <span class="s-badge {{ match ($intervention->status) {
                'active' => 's-active',
                'completed' => 's-received',
                'closed' => 's-inactive',
                default => 's-default',
            } }}">{{ __(ucfirst($intervention->status)) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-sitemap-outline"></i> {{ $intervention->thematicArea?->name ?? '—' }}</span>
            <span><i class="mdi mdi-chart-box-outline"></i> {{ $intervention->indicators->count() }} {{ __('indicators') }}</span>
        </div>
        <div class="mob-card-footer">
<x-row-actions>
            @can('intervention.update')
            <x-row-action href="{{ route('interventions.edit', $intervention) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
            @endcan
            @can('intervention.delete')
            <x-row-action action="{{ route('interventions.destroy', $intervention) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete intervention ":name"?', ['name' => $intervention->name]) }}" />
            @endcan
</x-row-actions>
</div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-hand-heart-outline"></i>
        <p>{{ __('No interventions found.') }}</p>
    </div>
    @endforelse

    @if ($interventions->hasPages())
    <div class="mt-3">
        {{ $interventions->links() }}
    </div>
    @endif
</div>
@endsection
