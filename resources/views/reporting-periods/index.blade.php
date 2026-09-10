@extends('components.main-layout')
@section('title', 'Reporting Periods')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Reporting Periods</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item active">Reporting Periods</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('reporting-periods.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Period</a>
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>Financial Year</th><th>Code</th><th>Name</th><th>Type</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($reportingPeriods as $reportingPeriod)
            <tr>
                <td>{{ ($reportingPeriods->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $reportingPeriod->financialYear?->name ?? '—' }}</td>
                <td><span class="ref-pill">{{ $reportingPeriod->code }}</span></td>
                <td>{{ $reportingPeriod->name }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $reportingPeriod->period_type)) }}</td>
                <td><span class="s-badge {{ $reportingPeriod->is_active ? 's-active' : 's-inactive' }}">{{ $reportingPeriod->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <a href="{{ route('reporting-periods.edit', $reportingPeriod) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('reporting-periods.destroy', $reportingPeriod) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this reporting period?');">
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
                        <i class="mdi mdi-calendar-week-outline"></i>
                        <p>No reporting periods found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($reportingPeriods->hasPages())
    <div class="px-3 py-2 border-top">{{ $reportingPeriods->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($reportingPeriods as $reportingPeriod)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $reportingPeriod->name }}</span>
            <span class="s-badge {{ $reportingPeriod->is_active ? 's-active' : 's-inactive' }}">{{ $reportingPeriod->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-range-outline"></i> {{ $reportingPeriod->financialYear?->name ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('reporting-periods.edit', $reportingPeriod) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('reporting-periods.destroy', $reportingPeriod) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this reporting period?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-calendar-week-outline"></i>
        <p>No reporting periods found.</p>
    </div>
    @endforelse
    @if ($reportingPeriods->hasPages())
    <div class="mt-3">{{ $reportingPeriods->links() }}</div>
    @endif
</div>
@endsection
