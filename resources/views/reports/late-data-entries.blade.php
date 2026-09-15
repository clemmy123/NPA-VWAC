@extends('components.main-layout')
@section('title', 'Late Data Entries')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Late Data Entries</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Late Data Entries</li>
            </ol>
        </nav>
    </div>
</div>

@if (! $financialYear)
<div class="table-card">
    <div class="tbl-empty">
        <i class="mdi mdi-calendar-remove-outline"></i>
        <p>No financial year is configured yet.</p>
    </div>
</div>
@else
<p class="text-muted mb-3">
    Assigned indicators with no submitted or approved data for a reporting period that has already closed,
    for {{ $financialYear->name }}.
</p>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Indicator</th>
                <th>Assigned To</th>
                <th>Reporting Period</th>
                <th>Days Late</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lateEntries as $late)
            <tr>
                <td>{{ $late['indicator']?->name ?? '—' }}</td>
                <td>
                    {{ $late['user']?->name ?? '—' }}
                    @if ($late['organization'])
                    <span class="text-muted small d-block">{{ $late['organization']->name }}</span>
                    @endif
                </td>
                <td>{{ $late['period']->name }}</td>
                <td><span class="s-badge s-inactive">{{ $late['days_late'] }} day{{ $late['days_late'] === 1 ? '' : 's' }}</span></td>
            </tr>
            @empty
            <tr>
                <td colspan="4">
                    <div class="tbl-empty">
                        <i class="mdi mdi-check-circle-outline"></i>
                        <p>Nothing is late — everyone is up to date.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($lateEntries as $late)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $late['indicator']?->name ?? '—' }}</span>
            <span class="s-badge s-inactive">{{ $late['days_late'] }} day{{ $late['days_late'] === 1 ? '' : 's' }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-account-outline"></i> {{ $late['user']?->name ?? $late['organization']?->name ?? '—' }}</span>
            <span><i class="mdi mdi-calendar-outline"></i> {{ $late['period']->name }}</span>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-check-circle-outline"></i>
        <p>Nothing is late — everyone is up to date.</p>
    </div>
    @endforelse
</div>
@endif
@endsection
