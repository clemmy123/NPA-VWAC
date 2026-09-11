@extends('components.main-layout')
@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Dashboard</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card mb-3">
    <p class="mb-3">Welcome, {{ auth()->user()->name }}.</p>

    <form method="GET" action="{{ route('dashboard') }}" class="row g-2 align-items-end">
        <div class="col-auto">
            <label for="financial_year_id" class="form-label mb-1">Financial Year</label>
            <select name="financial_year_id" id="financial_year_id" class="form-control" onchange="this.form.submit()">
                @forelse ($financialYears as $financialYear)
                <option value="{{ $financialYear->id }}" @selected($selectedFinancialYear?->id === $financialYear->id)>
                    {{ $financialYear->name }}{{ $financialYear->is_current ? ' (current)' : '' }}
                </option>
                @empty
                <option value="">No financial years yet</option>
                @endforelse
            </select>
        </div>
    </form>
</div>

@if (! $selectedFinancialYear)
<div class="chart-card">
    <p class="mb-0 text-muted">No financial year is set up yet — create one in Settings to see performance here.</p>
</div>
@elseif ($projects->isEmpty())
<div class="chart-card">
    <p class="mb-0 text-muted">No projects are visible to you yet.</p>
</div>
@else
@foreach ($projects as $project)
<div class="chart-card mb-3">
    <h5 class="mb-3">{{ $project->name }}</h5>

    @forelse ($project->thematicAreas as $thematicArea)
    <div class="mb-4">
        <h6 class="text-muted mb-2">{{ $thematicArea->name }}</h6>

        @if ($thematicArea->indicators->isEmpty())
        <p class="text-muted small mb-0">No indicators yet.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Indicator</th>
                        <th>Aggregation</th>
                        <th class="text-end">Target</th>
                        <th class="text-end">Actual (approved)</th>
                        <th class="text-end">Achievement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($thematicArea->indicators as $indicator)
                    @php($row = $performance[$indicator->id] ?? null)
                    <tr>
                        <td>{{ $indicator->name }}</td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst($row['aggregation_method'] ?? $indicator->aggregation_method) }}</span></td>
                        <td class="text-end">{{ $row['target_value'] === null ? '—' : rtrim(rtrim(number_format($row['target_value'], 2), '0'), '.') }}</td>
                        <td class="text-end">{{ $row['actual_value'] === null ? '—' : rtrim(rtrim(number_format($row['actual_value'], 2), '0'), '.') }}</td>
                        <td class="text-end">
                            @if ($row['achievement_percent'] === null)
                            <span class="text-muted">—</span>
                            @else
                            <span class="badge {{ $row['achievement_percent'] >= 100 ? 'bg-success' : ($row['achievement_percent'] >= 50 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ rtrim(rtrim(number_format($row['achievement_percent'], 2), '0'), '.') }}%
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @empty
    <p class="text-muted small mb-0">No thematic areas yet.</p>
    @endforelse
</div>
@endforeach
@endif
@endsection
