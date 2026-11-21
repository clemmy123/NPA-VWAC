@extends('components.main-layout')
@section('title', __('Indicator Baselines'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Indicator Baselines') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Baselines') }}</li>
            </ol>
        </nav>
    </div>
    @can('indicator.set-baseline')
    <a href="{{ route('indicator-baselines.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Baseline') }}</a>
    @endcan
</div>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('Indicator') }}</th>
                <th>{{ __('Financial Year') }}</th>
                <th>{{ __('Value') }}</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Source') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baselines as $baseline)
            <tr>
                <td>{{ ($baselines->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $baseline->indicator?->name ?? '—' }}</td>
                <td>{{ $baseline->financialYear?->name ?? '—' }}</td>
                <td>{{ \App\Support\DisplayNumber::format($baseline->baseline_value) }}</td>
                <td>{{ $baseline->baseline_date?->format('d M Y') ?? '—' }}</td>
                <td>{{ $baseline->organization?->name ?? '—' }}</td>
                <td>
<x-row-actions>
                    @can('indicator.set-baseline')
                    <x-row-action href="{{ route('indicator-baselines.edit', $baseline) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
                    <x-row-action action="{{ route('indicator-baselines.destroy', $baseline) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete this baseline?') }}" />
                    @endcan
</x-row-actions>
</td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-bullseye-arrow"></i>
                        <p>{{ __('No baselines found.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($baselines->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $baselines->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($baselines as $baseline)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $baseline->indicator?->name ?? '—' }}</span>
            <span class="s-badge s-default">{{ \App\Support\DisplayNumber::format($baseline->baseline_value) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-outline"></i> {{ $baseline->financialYear?->name ?? '—' }}</span>
            <span><i class="mdi mdi-file-document-outline"></i> {{ $baseline->organization?->name ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
<x-row-actions>
            @can('indicator.set-baseline')
            <x-row-action href="{{ route('indicator-baselines.edit', $baseline) }}" label="{{ __('Edit') }}" icon="mdi-pencil-outline" tone="amber" />
            <x-row-action action="{{ route('indicator-baselines.destroy', $baseline) }}" method="DELETE" label="{{ __('Delete') }}" icon="mdi-trash-can-outline" tone="danger" confirm="{{ __('Delete this baseline?') }}" />
            @endcan
</x-row-actions>
</div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-bullseye-arrow"></i>
        <p>{{ __('No baselines found.') }}</p>
    </div>
    @endforelse

    @if ($baselines->hasPages())
    <div class="mt-3">
        {{ $baselines->links() }}
    </div>
    @endif
</div>
@endsection
