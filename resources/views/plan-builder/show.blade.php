@extends('components.main-layout')
@section('title', $thematicArea->localized_name)

@section('content')
@php
    $statusLabels = [];
    foreach (['draft', 'pending_institution', 'pending_thematic', 'approved', 'returned'] as $planStatus) {
        $statusLabels[$planStatus] = __(ucfirst(str_replace('_', ' ', $planStatus)));
    }
    $statusLabels['approved'] = __('Open for collection');
    $statusLabels['returned'] = __('Reopened');
    $hasOpenRows = $plans->contains(fn ($plan) => $plan->status === 'approved');
@endphp
<div class="page-header">
    <div class="pb-header-title">
        <h4 class="page-title" id="pbTitle">{{ $thematicArea->localized_name }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('plan-builder.index') }}">{{ __('Plan Builder') }}</a></li>
                <li class="breadcrumb-item active" id="pbCrumb">{{ $thematicArea->localized_name }}</li>
            </ol>
        </nav>
    </div>
    <div class="pb-header-actions">
        @if ($planFinancialYear)
        <a class="btn btn-outline-dark btn-sm" href="{{ route('indicator-plans.index', [$thematicArea, 'financial_year_id' => $planFinancialYear->id]) }}">{{ __('Plan documents') }}</a>
        @endif
        @if ($canPlan && $planFinancialYear)
        @if ($hasOpenRows)
        <button type="button" class="btn btn-outline-dark btn-sm" id="pbUnlockPlan">{{ __('Reopen for editing') }}</button>
        @endif
        <button type="button" class="btn btn-primary btn-sm" id="pbSubmitPlan">{{ __('Open for data collection') }}</button>
        @endif
    </div>
</div>

@unless ($planFinancialYear)
<div class="alert alert-warning mb-3">{{ __('No financial years exist yet — add one in Settings before planning.') }}</div>
@endunless

<div class="chart-card mb-3 pb-hide-focus">
    <input type="text" id="pbNameInput" class="pb-name-input" value="{{ $thematicArea->localized_name }}" maxlength="255"
           {{ $canUpdateThematicArea ? '' : 'readonly' }}>
    <textarea id="pbDescInput" class="pb-desc-input" rows="1" placeholder="{{ __('Add a description…') }}"
              {{ $canUpdateThematicArea ? '' : 'readonly' }}>{{ $thematicArea->description }}</textarea>
</div>

<div class="pb-error-summary d-none" id="pbErrorSummary" role="alert"></div>

<form method="GET" class="pb-year-bar">
    <label for="pbPlanYear">{{ __('Plan financial year') }}</label>
    <select name="financial_year_id" id="pbPlanYear" class="pb-bar-select">
        @foreach ($financialYears as $fy)
        <option value="{{ $fy->id }}" @selected($planFinancialYear?->id === $fy->id)>{{ $fy->name }}</option>
        @endforeach
    </select>
    <span class="pb-year-hint">{{ __('Target and budget below are for this year.') }}</span>
    <div class="pb-toolbar-end">
        <div class="autosave-flag" id="pbAutosave"><span class="autosave-dot"></span>{{ __('All changes saved') }}</div>
        <button type="button" class="btn btn-outline-dark btn-sm" id="pbFocusToggle" title="{{ __('Give the table more room') }}">
            <i class="mdi mdi-arrow-expand-all"></i> <span>{{ __('Wide view') }}</span>
        </button>
    </div>
</form>

<div class="pb-sheet"
     id="pbSheet"
     data-thematic-area-id="{{ $thematicArea->id }}"
     data-financial-year-id="{{ $planFinancialYear?->id }}"
     data-can-create-indicator="{{ $canCreateIndicator ? 1 : 0 }}">
    <table class="pb-table">
        <thead>
            <tr>
                <th rowspan="2" class="pb-col-indicator">{{ __('Indicator') }}</th>
                <th rowspan="2" class="pb-col-acts">{{ __('Planned interventions') }}</th>
                <th colspan="2">{{ __('Measured as') }}</th>
                <th colspan="2">{{ __('Baseline') }}</th>
                <th rowspan="2">{{ __('Target') }}</th>
                <th colspan="2">{{ __('Budget estimate (TZS)') }}</th>
                <th colspan="2">{{ __('Institutions') }}</th>
                <th rowspan="2" class="pb-col-status">{{ __('Status') }}</th>
                <th rowspan="2" class="pb-col-actions"></th>
            </tr>
            <tr>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Unit') }}</th>
                <th>{{ __('Year') }}</th>
                <th>{{ __('Count') }}</th>
                <th>{{ __('Internal') }}</th>
                <th>{{ __('External') }}</th>
                <th>{{ __('Lead') }}</th>
                <th>{{ __('Partners') }}</th>
            </tr>
        </thead>
        <tbody id="pbIndicatorBody">
            @foreach ($indicators as $indicator)
                @include('plan-builder.row', ['indicator' => $indicator, 'plan' => $plans->get($indicator->id), 'missing' => $missingByIndicator[$indicator->id]])
            @endforeach
            <tr id="pbEmptyRow" class="{{ $indicators->isEmpty() ? '' : 'd-none' }}">
                <td colspan="13">
                    <div class="tbl-empty">
                        <i class="mdi mdi-format-list-checks"></i>
                        <p>{{ __('No indicators yet — add the first one below.') }}</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    @can('indicator.create')
    <div class="pb-add-row">
        <button type="button" class="pb-add-ghost-btn" id="pbAddIndicator"><i class="mdi mdi-plus"></i> {{ __('Add indicator') }}</button>
    </div>
    @endcan
</div>

<template id="pbRowTemplate">
    @include('plan-builder.row', ['indicator' => null, 'plan' => null, 'missing' => [__('not planned yet')]])
</template>
@endsection

@push('scripts')
<script>
(function () {
    const sheet = document.getElementById('pbSheet');
    const THEMATIC_AREA_ID = Number(sheet.dataset.thematicAreaId);
    const FINANCIAL_YEAR_ID = sheet.dataset.financialYearId ? Number(sheet.dataset.financialYearId) : null;
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    const indicatorsUrl = @json(route('indicators.store'));
    const indicatorUrl = (id) => indicatorsUrl + '/' + id;
    const interventionsUrl = @json(route('interventions.store'));
    const interventionUrl = (id) => interventionsUrl + '/' + id;
    const planUrl = (id) => @json(url('plan-builder/'.$thematicArea->id.'/indicators')) + '/' + id + '/plan';
    const submitUrl = @json(route('plan-builder.plans.submit', $thematicArea));
    const unlockUrl = @json(route('plan-builder.plans.unlock', $thematicArea));
    const thematicAreaUrl = @json(route('thematic-areas.update', $thematicArea));

    const body = document.getElementById('pbIndicatorBody');
    const emptyRow = document.getElementById('pbEmptyRow');
    const rowTemplate = document.getElementById('pbRowTemplate');

    /* ---------- autosave flag + api ---------- */
    let pending = 0;
    function autosaveFlag(state) {
        const flag = document.getElementById('pbAutosave');
        if (state === 'saving') {
            pending++;
            flag.style.color = 'var(--success-text)';
            flag.lastChild.textContent = ' ' + @json(__('Saving…'));
        } else if (state === 'error') {
            pending = Math.max(0, pending - 1);
            flag.style.color = 'var(--danger-text)';
            flag.lastChild.textContent = ' ' + @json(__('Could not save — check the field'));
        } else {
            pending = Math.max(0, pending - 1);
            if (pending === 0) {
                flag.style.color = 'var(--muted-soft)';
                flag.lastChild.textContent = ' ' + @json(__('All changes saved'));
            }
        }
    }

    async function api(url, method, payload) {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload || {}),
        });
        const data = await res.json().catch(() => null);
        if (!res.ok) {
            let message = data && data.message ? data.message : @json(__('Something went wrong.'));
            if (data && data.errors) {
                message = Object.values(data.errors).flat().join(' ');
            }
            const error = new Error(message);
            error.data = data;
            throw error;
        }
        return data ?? {};
    }

    async function tracked(work) {
        autosaveFlag('saving');
        try {
            const result = await work();
            autosaveFlag('saved');
            return result;
        } catch (e) {
            autosaveFlag('error');
            window.showToast && window.showToast('danger', e.message);
            return null;
        }
    }

    /* Saves for one row run strictly one after another so a slow request
       can never overwrite a newer one. */
    const queues = new WeakMap();
    function enqueue(tr, work) {
        const next = (queues.get(tr) || Promise.resolve()).then(work, work);
        queues.set(tr, next.catch(() => {}));
        return next;
    }

    /* ---------- number formatting (follows the row's measurement type) ---------- */
    function rules(tr, cell) {
        if (cell.classList.contains('pb-money')) return { decimals: 2, max: null };
        let decimals = null;
        let max = null;
        ['.pb-measurement-type', '.pb-unit'].forEach((selector) => {
            const option = tr.querySelector(selector).selectedOptions[0];
            const limits = option && option.dataset.limits ? JSON.parse(option.dataset.limits) : null;
            if (!limits) return;
            if (limits.decimals !== null) decimals = decimals === null ? limits.decimals : Math.min(decimals, limits.decimals);
            if (limits.max !== null) max = max === null ? limits.max : Math.min(max, limits.max);
        });
        return { decimals: decimals === null ? 4 : decimals, max };
    }

    function formatNumber(raw, rule) {
        let cleaned = raw.replace(/[^0-9.]/g, '');
        const firstDot = cleaned.indexOf('.');
        if (firstDot !== -1) {
            cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
        }
        if (rule.decimals === 0) cleaned = cleaned.split('.')[0];
        let [intPart, decPart] = cleaned.split('.');
        if (rule.max !== null && Number(cleaned || 0) > rule.max) {
            intPart = String(rule.max);
            decPart = undefined;
        }
        intPart = intPart.replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        if (decPart !== undefined) return intPart + '.' + decPart.slice(0, rule.decimals);
        return intPart;
    }

    const plainNumber = (cell) => cell.value.replace(/,/g, '');

    function wireNumberCell(tr, cell) {
        cell.addEventListener('input', () => {
            const before = cell.value;
            const caret = cell.selectionStart;
            const digitsBeforeCaret = before.slice(0, caret).replace(/[^0-9.]/g, '').length;
            cell.value = formatNumber(before, rules(tr, cell));
            let seen = 0, pos = 0;
            while (pos < cell.value.length && seen < digitsBeforeCaret) {
                if (/[0-9.]/.test(cell.value[pos])) seen++;
                pos++;
            }
            cell.setSelectionRange(pos, pos);
        });
        cell.addEventListener('change', () => savePlan(tr));
    }

    function reformatRow(tr) {
        tr.querySelectorAll('.pb-num').forEach((cell) => {
            if (cell.value !== '') cell.value = formatNumber(cell.value, rules(tr, cell));
        });
    }

    /* ---------- indicator fields ---------- */
    function indicatorFields(tr) {
        const fields = {
            name: tr.querySelector('.pb-name').value.trim(),
            unit_of_measure_id: tr.querySelector('.pb-unit').value || null,
            measurement_type_id: tr.querySelector('.pb-measurement-type').value || null,
            reporting_frequency: tr.querySelector('.pb-frequency').value || null,
        };
        tr.querySelectorAll('.pb-flag-input').forEach((box) => { fields[box.dataset.flag] = box.checked ? 1 : 0; });
        return fields;
    }

    function slugCode(name) {
        const base = name.toUpperCase().replace(/[^A-Z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40) || 'IND';
        return base + '-' + Date.now().toString(36).slice(-5).toUpperCase();
    }

    function syncIndicator(tr) {
        return enqueue(tr, async () => {
            const fields = indicatorFields(tr);
            if (!fields.name) return;
            if (!tr.dataset.id) {
                const res = await tracked(() => api(indicatorsUrl, 'POST', Object.assign({
                    thematic_area_id: THEMATIC_AREA_ID,
                    code: slugCode(fields.name),
                }, fields)));
                if (!res) return;
                tr.dataset.id = res.data.id;
                await flushNewRow(tr);
                return;
            }
            await tracked(() => api(indicatorUrl(tr.dataset.id), 'PATCH', fields));
        });
    }

    /* Plan cells / interventions typed before the indicator existed. */
    async function flushNewRow(tr) {
        for (const li of tr.querySelectorAll('.pb-act-item')) {
            await syncInterventionNow(tr, li);
        }
        if (hasPlanValues(tr)) await savePlanNow(tr);
    }

    /* ---------- plan cells ---------- */
    function hasPlanValues(tr) {
        return ['.pb-baseline-year', '.pb-baseline', '.pb-target', '.pb-domestic', '.pb-external', '.pb-lead']
            .some((sel) => tr.querySelector(sel).value !== '')
            || tr.querySelector('.pb-participants').selectedOptions.length > 0;
    }

    function planPayload(tr) {
        const number = (sel) => { const v = plainNumber(tr.querySelector(sel)); return v === '' ? null : v; };
        return {
            financial_year_id: FINANCIAL_YEAR_ID,
            baseline_financial_year_id: tr.querySelector('.pb-baseline-year').value || null,
            baseline_value: number('.pb-baseline'),
            annual_target: number('.pb-target'),
            domestic_budget: number('.pb-domestic'),
            external_budget: number('.pb-external'),
            lead_organization_id: tr.querySelector('.pb-lead').value || null,
            participating_organization_ids: Array.from(tr.querySelector('.pb-participants').selectedOptions).map((o) => Number(o.value)),
        };
    }

    async function savePlanNow(tr) {
        if (!FINANCIAL_YEAR_ID || !tr.dataset.id) return;
        const res = await tracked(() => api(planUrl(tr.dataset.id), 'POST', planPayload(tr)));
        if (res) applyPlanState(tr, res.data);
    }

    function savePlan(tr) {
        if (!tr.dataset.id) return Promise.resolve();
        return enqueue(tr, () => savePlanNow(tr));
    }

    function applyPlanState(tr, data) {
        const status = tr.querySelector('.pb-status');
        status.textContent = STATUS_LABELS[data.status] || data.status;
        const missing = tr.querySelector('.pb-missing');
        missing.classList.toggle('d-none', data.missing.length === 0);
        missing.title = @json(__('Still missing:')) + ' ' + data.missing.join(', ');
    }

    /* ---------- interventions ---------- */
    async function syncInterventionNow(tr, li) {
        const input = li.querySelector('.pb-act');
        const name = input.value.trim();
        const id = li.dataset.interventionId;

        if (!tr.dataset.id) return;  // flushed once the indicator exists

        if (!id) {
            if (!name) { li.remove(); return; }
            const res = await tracked(() => api(interventionsUrl, 'POST', {
                thematic_area_id: THEMATIC_AREA_ID,
                name,
                status: 'active',
                indicator_ids: [Number(tr.dataset.id)],
            }));
            if (res) li.dataset.interventionId = res.data.id;
        } else if (!name) {
            const ok = await tracked(() => api(interventionUrl(id), 'DELETE'));
            if (ok !== null) li.remove();
        } else {
            await tracked(() => api(interventionUrl(id), 'PATCH', { name }));
        }
        await savePlanNow(tr);
    }

    function syncIntervention(tr, li) {
        return enqueue(tr, () => syncInterventionNow(tr, li));
    }

    function addInterventionLine(tr) {
        const list = tr.querySelector('.pb-acts');
        const li = document.createElement('li');
        li.className = 'pb-act-item';
        li.innerHTML = '<textarea class="pb-cell pb-text pb-act" rows="1" maxlength="255" placeholder="' + @json(__('Intervention…')) + '"></textarea>'
            + '<button type="button" class="pb-act-remove" title="' + @json(__('Remove')) + '"><i class="mdi mdi-close"></i></button>';
        list.appendChild(li);
        wireInterventionLine(tr, li);
        autosize(li.querySelector('.pb-act'));
        li.querySelector('.pb-act').focus();
    }

    function wireInterventionLine(tr, li) {
        const input = li.querySelector('.pb-act');
        input.addEventListener('change', () => syncIntervention(tr, li));
        const remove = li.querySelector('.pb-act-remove');
        if (remove) {
            remove.addEventListener('click', () => {
                if (!li.dataset.interventionId) { li.remove(); return; }
                input.value = '';
                syncIntervention(tr, li);
            });
        }
    }

    /* ---------- auto-growing text cells (Enter = new line) ---------- */
    function autosize(el) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }
    body.addEventListener('input', (e) => { if (e.target.classList.contains('pb-text')) autosize(e.target); });
    window.addEventListener('resize', () => body.querySelectorAll('.pb-text').forEach(autosize));
    setTimeout(() => body.querySelectorAll('.pb-text').forEach(autosize), 0);

    /* ---------- delete ---------- */
    async function deleteIndicator(tr) {
        if (tr.dataset.id) {
            const message = @json(__('Delete this indicator? This also removes its baseline, target and plan.'));
            const confirmed = window.confirmAppAction ? await window.confirmAppAction(message) : confirm(message);
            if (!confirmed) return;
            const ok = await tracked(() => api(indicatorUrl(tr.dataset.id), 'DELETE'));
            if (ok === null) return;
        }
        tr.remove();
        emptyRow.classList.toggle('d-none', !!body.querySelector('.pb-row'));
    }

    /* ---------- row wiring ---------- */
    function applyBudgetState(tr) {
        const on = tr.querySelector('[data-flag="has_budget_implication"]').checked && tr.dataset.locked !== '1';
        tr.querySelectorAll('.pb-money').forEach((cell) => { cell.disabled = !on; });
    }

    /* Select2 raises its change event through jQuery only, which plain
       addEventListener never hears — so selects listen via jQuery when present. */
    function onChange(el, handler) {
        if (window.jQuery) jQuery(el).on('change', handler);
        else el.addEventListener('change', handler);
    }

    const select2Language = {
        noResults: () => @json(__('No results found')),
        searching: () => @json(__('Searching…')),
    };
    const STATUS_LABELS = @json($statusLabels);

    /* The second header row sticks directly under the first, whatever height it wraps to. */
    function syncHeaderOffset() {
        const first = sheet.querySelector('thead tr:first-child');
        sheet.style.setProperty('--pb-head-h', first.offsetHeight + 'px');
    }
    syncHeaderOffset();
    window.addEventListener('resize', syncHeaderOffset);

    /* Every dropdown in the grid is searchable. */
    function initSearchableSelects(tr) {
        if (!(window.jQuery && jQuery.fn.select2)) return;
        jQuery(tr).find('select.pb-cell').select2({
            width: '100%',
            minimumResultsForSearch: 0,
            language: select2Language,
            dropdownCssClass: 'pb-select2-dropdown',
        });
        jQuery(tr).find('select.pb-participants').select2({
            width: '100%',
            placeholder: @json(__('Select institutions…')),
            closeOnSelect: false,
            language: select2Language,
            dropdownCssClass: 'pb-select2-dropdown',
        });
    }

    function wireRow(tr) {
        tr.querySelector('.pb-name').addEventListener('change', () => syncIndicator(tr));
        onChange(tr.querySelector('.pb-frequency'), () => syncIndicator(tr));
        onChange(tr.querySelector('.pb-unit'), () => {
            reformatRow(tr);
            syncIndicator(tr).then(() => savePlan(tr));
        });
        onChange(tr.querySelector('.pb-measurement-type'), (e) => {
            tr.dataset.measurement = e.target.selectedOptions[0].dataset.code || '';
            reformatRow(tr);
            syncIndicator(tr).then(() => savePlan(tr));
        });
        tr.querySelectorAll('.pb-flag-input').forEach((box) => box.addEventListener('change', () => {
            applyBudgetState(tr);
            syncIndicator(tr).then(() => savePlan(tr));
        }));
        tr.querySelectorAll('.pb-num').forEach((cell) => wireNumberCell(tr, cell));
        tr.querySelectorAll('.pb-baseline-year, .pb-lead, .pb-participants').forEach((el) => onChange(el, () => savePlan(tr)));
        tr.querySelectorAll('.pb-act-item').forEach((li) => wireInterventionLine(tr, li));

        const addAct = tr.querySelector('.pb-act-add');
        if (addAct) addAct.addEventListener('click', () => addInterventionLine(tr));

        const toggle = tr.querySelector('.pb-more-toggle');
        const more = tr.querySelector('.pb-more-fields');
        toggle.addEventListener('click', () => more.classList.toggle('open'));

        const del = tr.querySelector('.pb-delete');
        if (del) del.addEventListener('click', () => deleteIndicator(tr));

        initSearchableSelects(tr);
    }

    /* Enter moves down the same column, like a spreadsheet. */
    body.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' || !e.target.classList.contains('pb-cell') || e.target.tagName === 'TEXTAREA') return;
        const tr = e.target.closest('tr');
        const next = tr.nextElementSibling;
        const cls = Array.from(e.target.classList).find((c) => c.startsWith('pb-') && c !== 'pb-cell' && c !== 'pb-num' && c !== 'pb-money');
        if (next && next.classList.contains('pb-row') && cls) {
            e.preventDefault();
            const target = next.querySelector('.' + cls);
            if (target && !target.disabled) target.focus();
        }
    });

    body.querySelectorAll('.pb-row').forEach(wireRow);

    const addBtn = document.getElementById('pbAddIndicator');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            emptyRow.classList.add('d-none');
            const tr = rowTemplate.content.firstElementChild.cloneNode(true);
            body.insertBefore(tr, emptyRow);
            wireRow(tr);
            applyBudgetState(tr);
            tr.querySelector('.pb-name').focus();
        });
    }

    /* ---------- open the plan for data collection ---------- */
    const FIELD_SELECTORS = {
        interventions: ['.pb-acts'],
        baseline: ['.pb-baseline-year', '.pb-baseline'],
        target: ['.pb-target'],
        budget: ['.pb-domestic', '.pb-external'],
        lead: ['.pb-lead'],
    };
    const summaryBox = document.getElementById('pbErrorSummary');

    function clearInvalid(tr) {
        tr.querySelectorAll('.pb-invalid').forEach((el) => el.classList.remove('pb-invalid'));
        const note = tr.querySelector('.pb-row-error');
        if (note) note.remove();
    }

    function clearAllInvalid() {
        body.querySelectorAll('.pb-row').forEach(clearInvalid);
        summaryBox.classList.add('d-none');
    }

    function markInvalid(rows) {
        clearAllInvalid();
        let first = null;
        rows.forEach((row) => {
            const tr = body.querySelector('.pb-row[data-id="' + row.indicator_id + '"]');
            if (!tr) return;
            first = first || tr;
            row.missing_keys.forEach((key) => {
                (FIELD_SELECTORS[key] || []).forEach((selector) => {
                    tr.querySelectorAll(selector).forEach((el) => {
                        const empty = el.matches('.pb-acts') ? !el.children.length : !el.value;
                        if (empty && !el.disabled) el.classList.add('pb-invalid');
                    });
                });
            });
            const note = document.createElement('div');
            note.className = 'pb-row-error';
            note.textContent = @json(__('Required:')) + ' ' + row.missing.join(', ');
            tr.querySelector('.pb-col-indicator').appendChild(note);
        });
        summaryBox.innerHTML = '<strong></strong><ul></ul>';
        summaryBox.querySelector('strong').textContent = @json(__('The plan cannot be opened for data collection yet.'));
        const list = summaryBox.querySelector('ul');
        rows.forEach((row) => {
            const li = document.createElement('li');
            li.textContent = row.name + ' — ' + row.missing.join(', ');
            list.appendChild(li);
        });
        summaryBox.classList.remove('d-none');
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    body.addEventListener('input', (event) => event.target.classList.remove('pb-invalid'));
    body.addEventListener('change', (event) => event.target.classList.remove('pb-invalid'));

    const submitBtn = document.getElementById('pbSubmitPlan');
    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            submitBtn.disabled = true;
            clearAllInvalid();
            try {
                await api(submitUrl, 'POST', { financial_year_id: FINANCIAL_YEAR_ID });
                window.showToast && window.showToast('success', @json(__('The plan is now open for data collection.')));
                setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                if (e.data && e.data.rows) {
                    markInvalid(e.data.rows);
                } else {
                    window.showToast && window.showToast('danger', e.message);
                }
                submitBtn.disabled = false;
            }
        });
    }

    const unlockBtn = document.getElementById('pbUnlockPlan');
    if (unlockBtn) {
        unlockBtn.addEventListener('click', async () => {
            unlockBtn.disabled = true;
            const res = await tracked(() => api(unlockUrl, 'POST', { financial_year_id: FINANCIAL_YEAR_ID }));
            if (res) {
                window.location.reload();
            } else {
                unlockBtn.disabled = false;
            }
        });
    }

    /* ---------- wide view: collapses the sidebar and trims chrome ---------- */
    const focusToggle = document.getElementById('pbFocusToggle');
    let sidebarWasCollapsed = false;
    function setFocus(on, remember) {
        if (on) sidebarWasCollapsed = document.body.classList.contains('sidebar-collapsed');
        document.body.classList.toggle('pb-focus', on);
        document.body.classList.toggle('sidebar-collapsed', on || sidebarWasCollapsed);
        focusToggle.querySelector('span').textContent = on ? @json(__('Normal view')) : @json(__('Wide view'));
        focusToggle.querySelector('i').className = 'mdi ' + (on ? 'mdi-arrow-collapse-all' : 'mdi-arrow-expand-all');
        if (remember) { try { localStorage.setItem('pbWideView', on ? '1' : '0'); } catch (e) {} }
    }
    focusToggle.addEventListener('click', () => setFocus(!document.body.classList.contains('pb-focus'), true));
    try { if (localStorage.getItem('pbWideView') === '1') setFocus(true, false); } catch (e) {}

    /* ---------- thematic area name/description ---------- */
    const nameInput = document.getElementById('pbNameInput');
    const descInput = document.getElementById('pbDescInput');
    if (nameInput && !nameInput.readOnly) {
        nameInput.addEventListener('input', () => {
            const text = nameInput.value || @json(__('Untitled thematic area'));
            document.getElementById('pbTitle').textContent = text;
            document.getElementById('pbCrumb').textContent = text;
        });
        nameInput.addEventListener('change', () => tracked(() => api(thematicAreaUrl, 'PATCH', { name: nameInput.value })));
    }
    if (descInput && !descInput.readOnly) {
        descInput.addEventListener('change', () => tracked(() => api(thematicAreaUrl, 'PATCH', { description: descInput.value })));
    }
})();
</script>
@endpush
