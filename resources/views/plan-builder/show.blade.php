@extends('components.main-layout')
@section('title', $thematicArea->name)

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title" id="pbTitle">{{ $thematicArea->name }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('plan-builder.index') }}">{{ __('Plan Builder') }}</a></li>
                <li class="breadcrumb-item active" id="pbCrumb">{{ $thematicArea->name }}</li>
            </ol>
        </nav>
    </div>
    <div class="autosave-flag" id="pbAutosave"><span class="autosave-dot"></span>{{ __('All changes saved') }}</div>
</div>

@unless ($currentFinancialYear)
<div class="alert alert-warning mb-3">{{ __('No financial year is marked current — the baseline column is disabled until one is set in Settings.') }}</div>
@endunless
@if ($financialYears->isEmpty())
<div class="alert alert-warning mb-3">{{ __('No financial years exist yet — the target column is disabled until one is added in Settings.') }}</div>
@endif

<div class="chart-card mb-3">
    <input type="text" id="pbNameInput" class="pb-name-input" value="{{ $thematicArea->name }}" maxlength="255"
           {{ $canUpdateThematicArea ? '' : 'readonly' }}>
    <textarea id="pbDescInput" class="pb-desc-input" rows="1" placeholder="{{ __('Add a description…') }}"
              {{ $canUpdateThematicArea ? '' : 'readonly' }}>{{ $thematicArea->description }}</textarea>
</div>

<div class="table-card mb-0">
    <table class="table mb-0 pb-table">
        <thead>
            <tr>
                <th style="width:34%">{{ __('Indicator') }}</th>
                <th style="width:14%">{{ __('Unit') }}</th>
                <th style="width:14%">{{ __('Baseline (:year)', ['year' => $currentFinancialYear?->name ?? '—']) }}</th>
                <th style="width:14%">{{ __('Target') }}</th>
                <th style="width:56px"></th>
            </tr>
        </thead>
        <tbody id="pbIndicatorBody">
            @forelse ($indicators as $indicator)
            <tr class="pb-row"
                data-id="{{ $indicator->id }}"
                data-baseline-id="{{ $baselines[$indicator->id]->id ?? '' }}"
                data-target-id="{{ $targets[$indicator->id]->id ?? '' }}">
                <td>
                    <input type="text" class="pb-input pb-name" value="{{ $indicator->name }}" placeholder="{{ __('Indicator name…') }}"
                           {{ $canUpdateIndicator ? '' : 'readonly' }}>
                    <button type="button" class="pb-more-toggle"><i class="mdi mdi-chevron-down"></i> {{ __('More fields') }}</button>
                    <div class="pb-more-fields">
                        <div>
                            <label>{{ __('Measurement type') }}</label>
                            <select class="pb-input pb-measurement-type" {{ $canUpdateIndicator ? '' : 'disabled' }}>
                                <option value="">—</option>
                                @foreach ($measurementTypes as $type)
                                <option value="{{ $type->id }}" @selected($indicator->measurement_type_id === $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>{{ __('Reporting frequency') }}</label>
                            <select class="pb-input pb-frequency" {{ $canUpdateIndicator ? '' : 'disabled' }}>
                                <option value="">—</option>
                                @foreach ($reportingFrequencies as $freq)
                                <option value="{{ $freq }}" @selected($indicator->reporting_frequency === $freq)>{{ __(ucfirst($freq)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>{{ __('Baseline source (organization)') }}</label>
                            <select class="pb-input pb-baseline-org select2" {{ $canSetBaseline ? '' : 'disabled' }}>
                                <option value="">—</option>
                                @foreach ($organizations as $organization)
                                <option value="{{ $organization->id }}" @selected((isset($baselines[$indicator->id]) ? $baselines[$indicator->id]->organization_id : null) === $organization->id)>{{ $organization->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </td>
                <td>
                    <select class="pb-input pb-unit" {{ $canUpdateIndicator ? '' : 'disabled' }}>
                        <option value="">—</option>
                        @foreach ($unitsOfMeasure as $unit)
                        <option value="{{ $unit->id }}" @selected($indicator->unit_of_measure_id === $unit->id)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" step="any" class="pb-input pb-baseline" placeholder="0"
                           value="{{ isset($baselines[$indicator->id]) ? (float) $baselines[$indicator->id]->baseline_value : '' }}"
                           {{ $canSetBaseline && $currentFinancialYear ? '' : 'disabled' }}>
                </td>
                <td>
                    <input type="number" step="any" class="pb-input pb-target" placeholder="0"
                           value="{{ isset($targets[$indicator->id]) ? (float) $targets[$indicator->id]->target_value : '' }}"
                           {{ $canSetTarget && $financialYears->isNotEmpty() ? '' : 'disabled' }}>
                    <select class="pb-input pb-target-fy" {{ $canSetTarget && $financialYears->isNotEmpty() ? '' : 'disabled' }}>
                        @foreach ($financialYears as $fy)
                        <option value="{{ $fy->id }}" @selected((isset($targets[$indicator->id]) ? $targets[$indicator->id]->financial_year_id : $currentFinancialYear?->id) === $fy->id)>{{ $fy->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    @if ($canDeleteIndicator)
                    <button type="button" class="btn-icon danger pb-delete" title="{{ __('Delete indicator') }}"><i class="mdi mdi-trash-can-outline"></i></button>
                    @endif
                </td>
            </tr>
            @empty
            <tr id="pbEmptyRow">
                <td colspan="5">
                    <div class="tbl-empty">
                        <i class="mdi mdi-format-list-checks"></i>
                        <p>{{ __('No indicators yet — add the first one below.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @can('indicator.create')
    <div class="pb-add-row">
        <button type="button" class="pb-add-ghost-btn" id="pbAddIndicator"><i class="mdi mdi-plus"></i> {{ __('Add indicator') }}</button>
    </div>
    @endcan
</div>
@endsection

@push('styles')
<style>
    .pb-name-input {
        font-size: 1.05rem; font-weight: 700; color: var(--heading-dark); border: 1px solid transparent;
        border-radius: 6px; padding: 4px 6px; margin-left: -6px; width: 100%; background: transparent;
    }
    .pb-name-input:hover:not([readonly]) { background: var(--hover-bg); }
    .pb-name-input:focus { outline: none; border-color: var(--accent); background: var(--surface); }
    .pb-desc-input {
        margin-top: 4px; width: 100%; border: 1px solid transparent; border-radius: 6px; padding: 4px 6px; margin-left: -6px;
        font-size: .82rem; color: var(--muted-mid); resize: none; background: transparent; font-family: inherit;
    }
    .pb-desc-input:hover:not([readonly]) { background: var(--hover-bg); }
    .pb-desc-input:focus { outline: none; border-color: var(--accent); background: var(--surface); color: var(--input-text); }

    /* .table-card .table thead th forces white-space:nowrap + ellipsis
       (higher specificity than a lone .pb-table thead th would have, hence
       the .table-card prefix here) for short plain-text headers elsewhere;
       "Baseline (2026/27)" / "Target (2026/27)" are too long for a 14%-wide
       column at that size, so let these headers wrap onto a second line
       instead of ellipsing away the financial year. */
    .table-card .table.pb-table thead th {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
        line-height: 1.3;
    }

    /* The shared .table-card table rule clips/nowraps plain text cells for
       ellipsis truncation; this table's cells hold inputs/selects that must
       lay out normally (name input on its own line, "More fields" below it,
       the fields grid below that), so undo it here. */
    .pb-table td {
        vertical-align: top;
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
    }
    .pb-table .pb-name { display: block; }
    .pb-input {
        width: 100%; border: 1px solid var(--border-soft); border-radius: 6px; height: 32px; padding: 0 8px;
        font-size: .82rem; color: var(--input-text); background: var(--surface); font-family: inherit;
        font-variant-numeric: tabular-nums;
    }
    input.pb-input.pb-name { border-color: transparent; margin-left: -8px; width: calc(100% + 8px); font-weight: 600; color: var(--heading-dark); }
    input.pb-input.pb-name:hover:not([readonly]) { background: var(--hover-bg); }
    .pb-input:focus { outline: 2px solid rgba(var(--accent-rgb), .3); outline-offset: 1px; border-color: var(--accent); }
    .pb-input[readonly], .pb-input[disabled] { background: var(--surface-faint); color: var(--muted-mid); }
    /* Block, not the select's default inline-block, so it stacks under the
       target input instead of being squeezed onto the same line and clipped
       by the shared .table-card cell's white-space:nowrap/overflow:hidden
       (which only .pb-name and .pb-more-fields escape today via their own
       display:block/grid — same trick applied here). */
    .pb-target-fy { display: block; margin-top: 6px; height: 26px; font-size: .68rem; color: var(--muted-mid); }

    .pb-more-toggle {
        all: unset; display: inline-flex; align-items: center; gap: 2px; font-size: .68rem; color: var(--accent-text);
        cursor: pointer; padding: 3px 4px 0;
    }
    .pb-more-toggle:hover { text-decoration: underline; }
    .pb-more-fields { display: none; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
    .pb-more-fields.open { display: grid; }
    .pb-more-fields label { display: block; font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted-icon); margin-bottom: 3px; }

    /* .table-card intentionally has no overflow:hidden (it would clip action
       dropdowns), so any child with its own background/border must round its
       own corners to match the card — otherwise its square corners visibly
       poke past the card's rounded ones. */
    .pb-add-row {
        padding: 12px 16px; border-top: 1px solid var(--divider-faint); background: var(--surface-faint);
        border-bottom-left-radius: var(--radius-lg); border-bottom-right-radius: var(--radius-lg);
    }
    .pb-table tbody tr:last-child td:first-child { border-bottom-left-radius: var(--radius-lg); }
    .pb-table tbody tr:last-child td:last-child { border-bottom-right-radius: var(--radius-lg); }
    .pb-add-ghost-btn {
        display: flex; align-items: center; gap: 6px; justify-content: center;
        font-size: .82rem; font-weight: 600; color: var(--muted-mid); cursor: pointer;
        padding: 9px 10px; border-radius: 6px; border: 1.5px dashed var(--border-strong); width: 100%; font-family: inherit;
    }
    .pb-add-ghost-btn:hover { border-color: var(--accent); color: var(--accent-text); background: var(--accent-bg); }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const THEMATIC_AREA_ID = {{ $thematicArea->id }};
    const FINANCIAL_YEAR_ID = @json($currentFinancialYear?->id);
    const FINANCIAL_YEARS = @json($financialYears->map(fn ($fy) => ['id' => $fy->id, 'name' => $fy->name])->values());
    const ORGANIZATIONS = @json($organizations->map(fn ($org) => ['id' => $org->id, 'name' => $org->name])->values());
    const CAN_UPDATE_INDICATOR = @json($canUpdateIndicator);
    const CAN_CREATE_INDICATOR = @json($canCreateIndicator);
    const CAN_SET_BASELINE = @json($canSetBaseline);
    const CAN_SET_TARGET = @json($canSetTarget);
    const CAN_DELETE_INDICATOR = @json($canDeleteIndicator);
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    const indicatorsUrl = @json(route('indicators.store'));
    const indicatorUrl = (id) => @json(route('indicators.store')) + '/' + id;
    const baselinesUrl = @json(route('indicator-baselines.store'));
    const baselineUrl = (id) => @json(route('indicator-baselines.store')) + '/' + id;
    const targetsUrl = @json(route('indicator-targets.store'));
    const targetUrl = (id) => @json(route('indicator-targets.store')) + '/' + id;

    const body = document.getElementById('pbIndicatorBody');
    const emptyRow = document.getElementById('pbEmptyRow');

    function autosaveFlag(state) {
        const flag = document.getElementById('pbAutosave');
        if (state === 'saving') {
            flag.style.color = 'var(--success-text)';
            flag.lastChild.textContent = ' ' + @json(__('Saving…'));
        } else if (state === 'error') {
            flag.style.color = 'var(--danger-text)';
            flag.lastChild.textContent = ' ' + @json(__('Could not save — check the field'));
        } else {
            flag.style.color = 'var(--muted-soft)';
            flag.lastChild.textContent = ' ' + @json(__('All changes saved'));
        }
    }

    async function api(url, method, payload) {
        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => null);
        if (!res.ok) {
            const message = data && data.message ? data.message : @json(__('Something went wrong.'));
            throw new Error(message);
        }
        return data;
    }

    function slugCode(name) {
        const base = name.toUpperCase().replace(/[^A-Z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40) || 'IND';
        return base + '-' + Date.now().toString(36).slice(-5).toUpperCase();
    }

    function fieldsFromRow(tr) {
        return {
            name: tr.querySelector('.pb-name').value.trim(),
            unit_of_measure_id: tr.querySelector('.pb-unit').value || null,
            measurement_type_id: tr.querySelector('.pb-measurement-type').value || null,
            reporting_frequency: tr.querySelector('.pb-frequency').value || null,
        };
    }

    async function syncIndicator(tr) {
        const fields = fieldsFromRow(tr);
        const id = tr.dataset.id;

        if (!id) {
            if (!fields.name) return;
            autosaveFlag('saving');
            try {
                const res = await api(indicatorsUrl, 'POST', Object.assign({
                    thematic_area_id: THEMATIC_AREA_ID,
                    code: slugCode(fields.name),
                }, fields));
                tr.dataset.id = res.data.id;
                autosaveFlag('saved');
                const baselineInput = tr.querySelector('.pb-baseline');
                const targetInput = tr.querySelector('.pb-target');
                if (baselineInput.value !== '') await syncBaseline(tr);
                if (targetInput.value !== '') await syncTarget(tr);
            } catch (e) {
                autosaveFlag('error');
                window.showToast && window.showToast('danger', e.message);
            }
            return;
        }

        if (!fields.name) return;
        autosaveFlag('saving');
        try {
            await api(indicatorUrl(id), 'PATCH', fields);
            autosaveFlag('saved');
        } catch (e) {
            autosaveFlag('error');
            window.showToast && window.showToast('danger', e.message);
        }
    }

    async function syncBaseline(tr) {
        if (!FINANCIAL_YEAR_ID || !tr.dataset.id) return;
        const value = tr.querySelector('.pb-baseline').value;
        if (value === '') return;
        const orgSelect = tr.querySelector('.pb-baseline-org');
        const organizationId = orgSelect ? (orgSelect.value || null) : null;
        autosaveFlag('saving');
        try {
            if (tr.dataset.baselineId) {
                await api(baselineUrl(tr.dataset.baselineId), 'PATCH', { baseline_value: value, organization_id: organizationId });
            } else {
                const res = await api(baselinesUrl, 'POST', {
                    indicator_id: tr.dataset.id,
                    financial_year_id: FINANCIAL_YEAR_ID,
                    baseline_value: value,
                    organization_id: organizationId,
                });
                tr.dataset.baselineId = res.data.id;
            }
            autosaveFlag('saved');
        } catch (e) {
            autosaveFlag('error');
            window.showToast && window.showToast('danger', e.message);
        }
    }

    async function syncTarget(tr) {
        if (!tr.dataset.id) return;
        const fySelect = tr.querySelector('.pb-target-fy');
        const financialYearId = fySelect ? fySelect.value : FINANCIAL_YEAR_ID;
        if (!financialYearId) return;
        const value = tr.querySelector('.pb-target').value;
        if (value === '') return;
        autosaveFlag('saving');
        try {
            if (tr.dataset.targetId) {
                await api(targetUrl(tr.dataset.targetId), 'PATCH', { financial_year_id: financialYearId, target_value: value });
            } else {
                const res = await api(targetsUrl, 'POST', {
                    indicator_id: tr.dataset.id,
                    financial_year_id: financialYearId,
                    target_value: value,
                });
                tr.dataset.targetId = res.data.id;
            }
            autosaveFlag('saved');
        } catch (e) {
            autosaveFlag('error');
            window.showToast && window.showToast('danger', e.message);
        }
    }

    async function deleteIndicator(tr) {
        const id = tr.dataset.id;
        if (!id) {
            tr.remove();
            maybeShowEmpty();
            return;
        }
        if (window.confirmAppAction) {
            if (! await window.confirmAppAction(@json(__('Delete this indicator? This also removes its baseline and target.')))) {
                return;
            }
        } else if (! confirm(@json(__('Delete this indicator? This also removes its baseline and target.')))) {
            return;
        }
        autosaveFlag('saving');
        try {
            await api(indicatorUrl(id), 'DELETE', {});
            tr.remove();
            autosaveFlag('saved');
            maybeShowEmpty();
        } catch (e) {
            autosaveFlag('error');
            window.showToast && window.showToast('danger', e.message);
        }
    }

    function maybeShowEmpty() {
        if (!body.querySelector('.pb-row') && !document.getElementById('pbEmptyRow')) {
            const tr = document.createElement('tr');
            tr.id = 'pbEmptyRow';
            tr.innerHTML = '<td colspan="5"><div class="tbl-empty"><i class="mdi mdi-format-list-checks"></i><p>{{ __('No indicators yet — add the first one below.') }}</p></div></td>';
            body.appendChild(tr);
        }
    }

    function wireRow(tr) {
        tr.querySelectorAll('.pb-name, .pb-unit, .pb-measurement-type, .pb-frequency').forEach((el) => {
            el.addEventListener('change', () => syncIndicator(tr));
        });
        const baseline = tr.querySelector('.pb-baseline');
        const baselineOrg = tr.querySelector('.pb-baseline-org');
        const target = tr.querySelector('.pb-target');
        const targetFy = tr.querySelector('.pb-target-fy');
        if (baseline) baseline.addEventListener('change', () => syncBaseline(tr));
        if (baselineOrg) baselineOrg.addEventListener('change', () => syncBaseline(tr));
        if (target) target.addEventListener('change', () => syncTarget(tr));
        if (targetFy) targetFy.addEventListener('change', () => syncTarget(tr));

        const moreToggle = tr.querySelector('.pb-more-toggle');
        const moreFields = tr.querySelector('.pb-more-fields');
        if (moreToggle && moreFields) {
            moreToggle.addEventListener('click', () => {
                const open = moreFields.classList.toggle('open');
                moreToggle.innerHTML = '<i class="mdi mdi-chevron-' + (open ? 'up' : 'down') + '"></i> ' + (open ? @json(__('Hide details')) : @json(__('More fields')));
            });
        }

        const deleteBtn = tr.querySelector('.pb-delete');
        if (deleteBtn) deleteBtn.addEventListener('click', () => deleteIndicator(tr));
    }

    body.querySelectorAll('.pb-row').forEach(wireRow);

    function addIndicatorRow() {
        if (emptyRow) emptyRow.remove();

        const tr = document.createElement('tr');
        tr.className = 'pb-row';
        tr.innerHTML = `
            <td>
                <input type="text" class="pb-input pb-name" placeholder="{{ __('Indicator name…') }}">
                <button type="button" class="pb-more-toggle"><i class="mdi mdi-chevron-down"></i> {{ __('More fields') }}</button>
                <div class="pb-more-fields">
                    <div>
                        <label>{{ __('Measurement type') }}</label>
                        <select class="pb-input pb-measurement-type">
                            <option value="">—</option>
                            @foreach ($measurementTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>{{ __('Reporting frequency') }}</label>
                        <select class="pb-input pb-frequency">
                            <option value="">—</option>
                            @foreach ($reportingFrequencies as $freq)
                            <option value="{{ $freq }}">{{ __(ucfirst($freq)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>{{ __('Baseline source (organization)') }}</label>
                        <select class="pb-input pb-baseline-org select2">
                            <option value="">—</option>
                            ${ORGANIZATIONS.map((org) => '<option value="' + org.id + '">' + org.name + '</option>').join('')}
                        </select>
                    </div>
                </div>
            </td>
            <td>
                <select class="pb-input pb-unit">
                    <option value="">—</option>
                    @foreach ($unitsOfMeasure as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" step="any" class="pb-input pb-baseline" placeholder="0" ${FINANCIAL_YEAR_ID && CAN_SET_BASELINE ? '' : 'disabled'}></td>
            <td>
                <input type="number" step="any" class="pb-input pb-target" placeholder="0" ${FINANCIAL_YEARS.length && CAN_SET_TARGET ? '' : 'disabled'}>
                <select class="pb-input pb-target-fy" ${FINANCIAL_YEARS.length && CAN_SET_TARGET ? '' : 'disabled'}>
                    ${FINANCIAL_YEARS.map((fy) => '<option value="' + fy.id + '"' + (fy.id === FINANCIAL_YEAR_ID ? ' selected' : '') + '>' + fy.name + '</option>').join('')}
                </select>
            </td>
            <td>${CAN_DELETE_INDICATOR ? '<button type="button" class="btn-icon danger pb-delete" title="{{ __('Delete indicator') }}"><i class="mdi mdi-trash-can-outline"></i></button>' : ''}</td>
        `;
        body.appendChild(tr);
        wireRow(tr);
        if (window.initAppSelect2In) {
            window.initAppSelect2In(tr);
        } else if (window.jQuery) {
            jQuery(tr).find('.select2').select2({ width: '100%' });
        }
        tr.querySelector('.pb-name').focus();
    }

    const addBtn = document.getElementById('pbAddIndicator');
    if (addBtn) addBtn.addEventListener('click', addIndicatorRow);

    const nameInput = document.getElementById('pbNameInput');
    const descInput = document.getElementById('pbDescInput');
    const thematicAreaUrl = @json(route('thematic-areas.update', $thematicArea));

    if (nameInput && !nameInput.readOnly) {
        nameInput.addEventListener('input', () => {
            document.getElementById('pbTitle').textContent = nameInput.value || @json(__('Untitled thematic area'));
            document.getElementById('pbCrumb').textContent = nameInput.value || @json(__('Untitled thematic area'));
        });
        nameInput.addEventListener('change', async () => {
            autosaveFlag('saving');
            try {
                await api(thematicAreaUrl, 'PATCH', { name: nameInput.value });
                autosaveFlag('saved');
            } catch (e) {
                autosaveFlag('error');
                window.showToast && window.showToast('danger', e.message);
            }
        });
    }
    if (descInput && !descInput.readOnly) {
        descInput.addEventListener('change', async () => {
            autosaveFlag('saving');
            try {
                await api(thematicAreaUrl, 'PATCH', { description: descInput.value });
                autosaveFlag('saved');
            } catch (e) {
                autosaveFlag('error');
                window.showToast && window.showToast('danger', e.message);
            }
        });
    }
})();
</script>
@endpush
