@extends('components.main-layout')
@section('title', __('Data Collections'))

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
        <h4 class="page-title">{{ __('Data Collections') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Data Collections') }}</li>
            </ol>
        </nav>
    </div>
    @if ($historyIndicator)
    <a href="{{ route('indicator-data-entries.index') }}" class="ws-btn ws-btn-soft show-all-btn">{{ __('Show all') }}</a>
    @endif
</div>

<div class="filter-card report-filters mb-3{{ $historyIndicator ? ' d-none' : '' }}" id="collection-workspace-filters">
    <div class="rf-field">
        <label for="workspace_project_id">{{ __('Project') }}</label>
        <select id="workspace_project_id" class="form-control select2">
            <option value="">{{ __('Select project…') }}</option>
            @foreach ($projects as $project)
            <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="rf-field">
        <label for="workspace_thematic_id">{{ __('Thematic Area') }}</label>
        <select id="workspace_thematic_id" class="form-control select2" disabled>
            <option value="">{{ __('Select project first…') }}</option>
            @foreach ($thematicAreas as $area)
            <option value="{{ $area->id }}" data-project-id="{{ $area->project_id }}">{{ $area->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row g-3 mb-4{{ $historyIndicator ? ' d-none' : '' }}" id="indicator-workspace">
    @foreach ($indicators as $indicator)
    @php
        $indicatorEntries = $workspaceEntries->where('indicator_id', $indicator->id)->sortByDesc('entry_date');
        $editableEntry = $indicatorEntries->first(fn ($item) => in_array($item->status, ['draft', 'rejected'], true) && (int) $item->entered_by === (int) auth()->id());
        $collectionCount = $indicatorEntries->where('status', '!=', 'draft')->count();
        $approvedCount = $indicatorEntries->where('status', 'approved')->count();
        $latestEntry = $indicatorEntries->first();
    @endphp
    <div class="col-xl-4 col-md-6 indicator-workspace-item d-none"
         data-project-id="{{ $indicator->thematicArea?->project_id }}"
         data-thematic-id="{{ $indicator->thematic_area_id }}">
        <div class="chart-card workspace-card h-100 mb-0">
            <div class="d-flex justify-content-between gap-2 align-items-start">
                <div>
                    <span class="ref-pill">{{ $indicator->code }}</span>
                    <h6 class="mt-2 mb-1">{{ $indicator->name }}</h6>
                    <div class="text-muted small">{{ $indicator->thematicArea?->name }}</div>
                </div>
                <span class="s-badge {{ $latestEntry ? ($statusBadge[$latestEntry->status] ?? 's-default') : 's-default' }}">
                    {{ $latestEntry ? \App\Support\UiCopy::entryStatus($latestEntry->status) : __('Not collected') }}
                </span>
            </div>
            <div class="workspace-card-meta">
                <span><strong>{{ $collectionCount }}</strong> {{ __('collections') }}</span>
                <span><strong>{{ $approvedCount }}</strong> {{ __('approved') }}</span>
            </div>
            <div class="workspace-card-actions">
                <a href="{{ route('indicator-data-entries.index', ['indicator_id' => $indicator->id]) }}#collection-records" class="ws-btn ws-btn-soft">
                    <i class="mdi mdi-chart-line"></i> {{ __('View history') }}
                </a>
                @can('indicator-data.create')
                <button type="button" class="ws-btn ws-btn-primary js-collect-indicator"
                        data-project-id="{{ $indicator->thematicArea?->project_id }}"
                        data-thematic-id="{{ $indicator->thematic_area_id }}"
                        data-indicator-id="{{ $indicator->id }}"
                        data-edit-url="{{ $editableEntry ? route('indicator-data-entries.edit', ['indicator_data_entry' => $editableEntry, 'embedded' => 1]) : '' }}">
                    <i class="mdi mdi-clipboard-plus-outline"></i> {{ $editableEntry ? __('Continue collection') : __('Collect data') }}
                </button>
                @endcan
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="modal fade" id="collectionEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="min-height:85vh">
            <div class="modal-header"><h5 class="modal-title">{{ __('Data Collection') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button></div>
            <div class="modal-body p-0"><iframe id="collection-editor-frame" title="{{ __('Data collection editor') }}" style="width:100%;height:76vh;border:0"></iframe></div>
        </div>
    </div>
</div>

<div id="collection-records">
<div class="collection-records-toolbar">
    <div>
        <h5 class="mb-0">{{ __('Collection records') }}</h5>
        @if ($historyIndicator)
        <div class="text-muted mt-1">{{ $historyIndicator->name }}</div>
        @endif
    </div>
    @if ($historyIndicator)
    <a href="{{ route('indicator-data-entries.index') }}" class="ws-btn ws-btn-soft show-all-btn">{{ __('Show all') }}</a>
    @endif
</div>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('Indicator') }}</th>
                <th>{{ __('Financial Year') }}</th>
                <th>{{ __('Entry Date') }}</th>
                <th>{{ __('Entered By') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
            <tr>
                <td>{{ ($entries->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $entry->indicator?->name ?? '—' }}</td>
                <td>{{ $entry->financialYear?->name ?? '—' }}</td>
                <td>{{ $entry->entry_date?->format('d M Y') ?? '—' }}</td>
                <td>{{ $entry->enteredBy?->name ?? '—' }}</td>
                <td><span class="s-badge {{ $statusBadge[$entry->status] ?? 's-default' }}">{{ \App\Support\UiCopy::entryStatus($entry->status) }}</span></td>
                <td>
                    @include('indicator-data-entries.partials.row-actions', ['entry' => $entry, 'showEdit' => true])
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-clipboard-text-outline"></i>
                        <p>{{ __('No data collections found.') }}</p>
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
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $entry->indicator?->name ?? '—' }}</span>
            <span class="s-badge {{ $statusBadge[$entry->status] ?? 's-default' }}">{{ \App\Support\UiCopy::entryStatus($entry->status) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-outline"></i> {{ $entry->entry_date?->format('d M Y') ?? '—' }}</span>
            <span><i class="mdi mdi-account-outline"></i> {{ $entry->enteredBy?->name ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
            @include('indicator-data-entries.partials.row-actions', ['entry' => $entry, 'showEdit' => true])
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-clipboard-text-outline"></i>
        <p>{{ __('No data collections found.') }}</p>
    </div>
    @endforelse

    @if ($entries->hasPages())
    <div class="mt-3">
        {{ $entries->links() }}
    </div>
    @endif
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var project = document.getElementById('workspace_project_id');
    var thematic = document.getElementById('workspace_thematic_id');
    var thematicOptions = Array.from(thematic.querySelectorAll('option[data-project-id]')).map(function (option) { return option.cloneNode(true); });
    var cards = Array.from(document.querySelectorAll('.indicator-workspace-item'));
    var editorModal = document.getElementById('collectionEditorModal');
    var editorFrame = document.getElementById('collection-editor-frame');

    function showCards() {
        cards.forEach(function (card) {
            card.classList.toggle('d-none', !thematic.value || card.dataset.thematicId !== thematic.value);
        });
    }

    function filterThematics() {
        thematic.replaceChildren(new Option(project.value ? @json(__('Select thematic area…')) : @json(__('Select project first…')), ''));
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
                if (!response.ok) throw new Error(@json(__('Unable to start collection')));
                return response.json();
            }).then(function (data) {
                button.dataset.editUrl = data.edit_url;
                button.innerHTML = '<i class="mdi mdi-clipboard-plus-outline"></i> ' + @json(__('Continue collection'));
                openEditor(data.edit_url);
            }).catch(function () {
                window.showToast ? window.showToast('danger', @json(__('The collection could not be started. Please try again.'))) : alert(@json(__('The collection could not be started.')));
            }).finally(function () {
                button.disabled = false;
            });
        });
    });

    editorModal.addEventListener('hidden.bs.modal', function () {
        editorFrame.src = 'about:blank';

        var successMessage = window._pendingCollectionSuccess;
        window._pendingCollectionSuccess = null;

        if (successMessage && typeof window.showAppMessage === 'function') {
            window.showAppMessage('success', @json(__('Success')), successMessage).then(function () {
                window.location.reload();
            });
            return;
        }

        window.location.reload();
    });
    window.addEventListener('message', function (event) {
        if (event.origin === window.location.origin && event.data && event.data.type === 'collection-submitted') {
            if (typeof event.data.message === 'string' && event.data.message !== '') {
                window._pendingCollectionSuccess = event.data.message;
            }
            bootstrap.Modal.getOrCreateInstance(editorModal).hide();
        }
    });

    function scrollToCollectionRecords() {
        if (window.location.hash !== '#collection-records') {
            return;
        }

        var records = document.getElementById('collection-records');
        if (records) {
            records.scrollIntoView();
        }
    }

    if (!project.value && project.options.length > 1) {
        project.value = project.options[1].value;
        if (window.jQuery) jQuery(project).trigger('change.select2');
    }
    filterThematics();
    scrollToCollectionRecords();
});
</script>
@endpush
