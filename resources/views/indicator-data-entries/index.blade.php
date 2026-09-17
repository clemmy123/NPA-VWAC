@extends('components.main-layout')
@section('title', 'Data Collections')

@php
    $statusBadge = [
        'draft' => 's-default',
        'submitted' => 's-investigation',
        'pending_approval' => 's-investigation',
        'approved' => 's-active',
        'rejected' => 's-inactive',
    ];
@endphp

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Data Collections</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Data Collections</li>
            </ol>
        </nav>
    </div>
</div>

<div class="filter-card report-filters mb-3" id="collection-workspace-filters">
    <div class="rf-field">
        <label for="workspace_project_id">Project</label>
        <select id="workspace_project_id" class="form-control select2">
            <option value="">Select project…</option>
            @foreach ($projects as $project)
            <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="rf-field">
        <label for="workspace_thematic_id">Thematic Area</label>
        <select id="workspace_thematic_id" class="form-control select2" disabled>
            <option value="">Select project first…</option>
            @foreach ($thematicAreas as $area)
            <option value="{{ $area->id }}" data-project-id="{{ $area->project_id }}">{{ $area->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row g-3 mb-4" id="indicator-workspace">
    @foreach ($indicators as $indicator)
    @php
        $indicatorEntries = $workspaceEntries->where('indicator_id', $indicator->id)->sortByDesc('entry_date');
        $editableEntry = $indicatorEntries->first(fn ($item) => in_array($item->status, ['draft', 'rejected'], true) && (int) $item->entered_by === (int) auth()->id());
        $approvedCount = $indicatorEntries->where('status', 'approved')->count();
        $latestEntry = $indicatorEntries->first();
        $trendEntries = $indicatorEntries->where('status', 'approved')->whereNotNull('actual_value')->sortBy('entry_date')->values();
        $trendChart = base64_encode(json_encode([
            'labels' => $trendEntries->map(fn ($item) => $item->entry_date?->format('d M Y'))->all(),
            'values' => $trendEntries->pluck('actual_value')->map(fn ($value) => (float) $value)->all(),
        ]));
    @endphp
    <div class="col-xl-4 col-md-6 indicator-workspace-item d-none"
         data-project-id="{{ $indicator->thematicArea?->project_id }}"
         data-thematic-id="{{ $indicator->thematic_area_id }}">
        <div class="chart-card h-100 mb-0">
            <div class="d-flex justify-content-between gap-2 align-items-start">
                <div>
                    <span class="ref-pill">{{ $indicator->code }}</span>
                    <h6 class="mt-2 mb-1">{{ $indicator->name }}</h6>
                    <div class="text-muted small">{{ $indicator->thematicArea?->name }}</div>
                </div>
                <span class="s-badge {{ $latestEntry?->status === 'approved' ? 's-active' : 's-default' }}">
                    {{ $latestEntry ? ucfirst(str_replace('_', ' ', $latestEntry->status)) : 'Not collected' }}
                </span>
            </div>
            <div class="d-flex gap-3 my-3 small">
                <span><strong>{{ $indicatorEntries->count() }}</strong> collections</span>
                <span><strong>{{ $approvedCount }}</strong> approved</span>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#indicatorSummary{{ $indicator->id }}">
                    <i class="mdi mdi-chart-line"></i> View history
                </button>
                @can('indicator-data.create')
                <button type="button" class="btn btn-dark btn-sm js-collect-indicator"
                        data-project-id="{{ $indicator->thematicArea?->project_id }}"
                        data-thematic-id="{{ $indicator->thematic_area_id }}"
                        data-indicator-id="{{ $indicator->id }}"
                        data-edit-url="{{ $editableEntry ? route('indicator-data-entries.edit', ['indicator_data_entry' => $editableEntry, 'embedded' => 1]) : '' }}">
                    <i class="mdi mdi-clipboard-edit-outline"></i> {{ $editableEntry ? 'Continue collection' : 'Collect data' }}
                </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="modal fade" id="indicatorSummary{{ $indicator->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div><div class="small text-muted">{{ $indicator->code }}</div><h5 class="modal-title">{{ $indicator->name }}</h5></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><div class="dash-stat"><div><div class="dash-stat-label">Collections</div><div class="dash-stat-value">{{ $indicatorEntries->count() }}</div></div></div></div>
                        <div class="col-md-4"><div class="dash-stat"><div><div class="dash-stat-label">Approved</div><div class="dash-stat-value">{{ $approvedCount }}</div></div></div></div>
                        <div class="col-md-4"><div class="dash-stat"><div><div class="dash-stat-label">Latest result</div><div class="dash-stat-value">{{ $latestEntry?->actual_text ?: \App\Support\DisplayNumber::format($latestEntry?->actual_value) }}</div></div></div></div>
                    </div>
                    @if ($trendEntries->isNotEmpty())
                    <div class="chart-card mb-3">
                        <div class="chart-card-title">Approved result trend</div>
                        <div style="height:280px"><canvas class="js-indicator-trend" data-chart="{{ $trendChart }}"></canvas></div>
                    </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Date</th><th>Result</th><th>Collector</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                            @forelse ($indicatorEntries as $item)
                            <tr>
                                <td>{{ $item->entry_date?->format('d M Y') }}</td>
                                <td>{{ $item->actual_text ?: \App\Support\DisplayNumber::format($item->actual_value) }}</td>
                                <td>{{ $item->enteredBy?->name ?? '—' }}</td>
                                <td><span class="s-badge {{ $statusBadge[$item->status] ?? 's-default' }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                                <td class="text-end">
                                    @if (in_array($item->status, ['draft', 'rejected'], true) && (int) $item->entered_by === (int) auth()->id())
                                    <button type="button" class="btn-icon js-open-collection" data-url="{{ route('indicator-data-entries.edit', ['indicator_data_entry' => $item, 'embedded' => 1]) }}" title="Edit"><i class="mdi mdi-pencil-outline"></i></button>
                                    @endif
                                    @if (in_array($item->status, ['submitted', 'pending_approval'], true))
                                        @can('indicator-data.approve')
                                        <form action="{{ route('indicator-data-entries.approve', $item) }}" method="POST" class="d-inline">@csrf<button class="btn-icon" title="Approve"><i class="mdi mdi-check-circle-outline"></i></button></form>
                                        @endcan
                                        @can('indicator-data.return')
                                        <form action="{{ route('indicator-data-entries.return', $item) }}" method="POST" class="d-inline">@csrf<button class="btn-icon danger" title="Return"><i class="mdi mdi-undo"></i></button></form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No collection has been recorded for this indicator.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="modal fade" id="collectionEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="min-height:85vh">
            <div class="modal-header"><h5 class="modal-title">Data Collection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-0"><iframe id="collection-editor-frame" title="Data collection editor" style="width:100%;height:76vh;border:0"></iframe></div>
        </div>
    </div>
</div>

<h5 class="mb-3">Collection records</h5>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Indicator</th>
                <th>Financial Year</th>
                <th>Entry Date</th>
                <th>Actual Value</th>
                <th>Entered By</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
            @php($canEdit = in_array($entry->status, ['draft', 'rejected'], true))
            <tr>
                <td>{{ ($entries->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $entry->indicator?->name ?? '—' }}</td>
                <td>{{ $entry->financialYear?->name ?? '—' }}</td>
                <td>{{ $entry->entry_date?->format('d M Y') ?? '—' }}</td>
                <td>{{ $entry->actual_text ?: \App\Support\DisplayNumber::format($entry->actual_value) }}</td>
                <td>{{ $entry->enteredBy?->name ?? '—' }}</td>
                <td><span class="s-badge {{ $statusBadge[$entry->status] ?? 's-default' }}">{{ ucfirst($entry->status) }}</span></td>
                <td>
                    @if (in_array($entry->status, ['submitted', 'pending_approval'], true))
                    @can('indicator-data.approve')
                    <form action="{{ route('indicator-data-entries.approve', $entry) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Approve this collection?');">
                        @csrf
                        <button type="submit" class="btn-icon" title="Approve"><i class="mdi mdi-check-circle-outline"></i></button>
                    </form>
                    @endcan
                    @can('indicator-data.return')
                    <form action="{{ route('indicator-data-entries.return', $entry) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Return this collection to the submitter?');">
                        @csrf
                        <button type="submit" class="btn-icon danger" title="Return"><i class="mdi mdi-undo"></i></button>
                    </form>
                    @endcan
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8">
                    <div class="tbl-empty">
                        <i class="mdi mdi-clipboard-text-outline"></i>
                        <p>No data collections found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($entries->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $entries->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($entries as $entry)
    @php($canEdit = in_array($entry->status, ['draft', 'rejected'], true))
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $entry->indicator?->name ?? '—' }}</span>
            <span class="s-badge {{ $statusBadge[$entry->status] ?? 's-default' }}">{{ ucfirst($entry->status) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-outline"></i> {{ $entry->entry_date?->format('d M Y') ?? '—' }}</span>
            <span><i class="mdi mdi-account-outline"></i> {{ $entry->enteredBy?->name ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
            @if (in_array($entry->status, ['submitted', 'pending_approval'], true))
            @can('indicator-data.approve')
            <form action="{{ route('indicator-data-entries.approve', $entry) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Approve this collection?');">
                @csrf
                <button type="submit" class="btn-icon" title="Approve"><i class="mdi mdi-check-circle-outline"></i></button>
            </form>
            @endcan
            @can('indicator-data.return')
            <form action="{{ route('indicator-data-entries.return', $entry) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Return this collection to the submitter?');">
                @csrf
                <button type="submit" class="btn-icon danger" title="Return"><i class="mdi mdi-undo"></i></button>
            </form>
            @endcan
            @endif
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-clipboard-text-outline"></i>
        <p>No data collections found.</p>
    </div>
    @endforelse

    @if ($entries->hasPages())
    <div class="mt-3">
        {{ $entries->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('app-assets/libs/chart-js/Chart.bundle.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var project = document.getElementById('workspace_project_id');
    var thematic = document.getElementById('workspace_thematic_id');
    var thematicOptions = Array.from(thematic.querySelectorAll('option[data-project-id]')).map(function (option) { return option.cloneNode(true); });
    var cards = Array.from(document.querySelectorAll('.indicator-workspace-item'));
    var editorModal = document.getElementById('collectionEditorModal');
    var editorFrame = document.getElementById('collection-editor-frame');

    document.querySelectorAll('.js-indicator-trend').forEach(function (canvas) {
        var chart = JSON.parse(atob(canvas.dataset.chart));
        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: { labels: chart.labels, datasets: [{ label: 'Approved actual', data: chart.values, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.10)', pointBackgroundColor: '#2563eb', fill: true, lineTension: .25 }] },
            options: { responsive: true, maintainAspectRatio: false, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }], xAxes: [{ gridLines: { display: false } }] } }
        });
    });

    function showCards() {
        cards.forEach(function (card) {
            card.classList.toggle('d-none', !thematic.value || card.dataset.thematicId !== thematic.value);
        });
    }

    function filterThematics() {
        thematic.replaceChildren(new Option(project.value ? 'Select thematic area…' : 'Select project first…', ''));
        thematicOptions.forEach(function (option) {
            if (option.dataset.projectId === project.value) thematic.appendChild(option.cloneNode(true));
        });
        thematic.disabled = !project.value;
        if (project.value && thematic.options.length > 1) thematic.value = thematic.options[1].value;
        if (window.jQuery) jQuery(thematic).trigger('change.select2');
        showCards();
    }

    function openEditor(url) {
        editorFrame.src = url;
        bootstrap.Modal.getOrCreateInstance(editorModal).show();
    }

    if (window.jQuery) {
        jQuery(project).on('change select2:select', filterThematics);
        jQuery(thematic).on('change select2:select', showCards);
    } else {
        project.addEventListener('change', filterThematics);
        thematic.addEventListener('change', showCards);
    }

    document.querySelectorAll('.js-open-collection').forEach(function (button) {
        button.addEventListener('click', function () { openEditor(button.dataset.url); });
    });

    document.querySelectorAll('.js-collect-indicator').forEach(function (button) {
        button.addEventListener('click', function () {
            if (button.dataset.editUrl) {
                openEditor(button.dataset.editUrl);
                return;
            }

            button.disabled = true;
            fetch(@json(route('indicator-data-entries.start')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    project_id: button.dataset.projectId,
                    thematic_area_id: button.dataset.thematicId,
                    indicator_id: button.dataset.indicatorId
                })
            }).then(function (response) {
                if (!response.ok) throw new Error('Unable to start collection');
                return response.json();
            }).then(function (data) {
                button.dataset.editUrl = data.edit_url;
                button.innerHTML = '<i class="mdi mdi-clipboard-edit-outline"></i> Continue collection';
                openEditor(data.edit_url);
            }).catch(function () {
                window.showToast ? window.showToast('danger', 'The collection could not be started. Please try again.') : alert('The collection could not be started.');
            }).finally(function () {
                button.disabled = false;
            });
        });
    });

    editorModal.addEventListener('hidden.bs.modal', function () {
        editorFrame.src = 'about:blank';
        window.location.reload();
    });
    window.addEventListener('message', function (event) {
        if (event.origin === window.location.origin && event.data && event.data.type === 'collection-submitted') {
            bootstrap.Modal.getOrCreateInstance(editorModal).hide();
        }
    });

    if (!project.value && project.options.length > 1) {
        project.value = project.options[1].value;
        if (window.jQuery) jQuery(project).trigger('change.select2');
    }
    filterThematics();
});
</script>
@endpush
