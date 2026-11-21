@extends('components.main-layout')
@section('title', $thematicArea->localized_name)

@section('content')
@php
    $statusLabels = [];
    foreach (['draft', 'rejected', 'pending_approval', 'submitted', 'approved'] as $entryStatus) {
        $statusLabels[$entryStatus] = __(ucfirst(str_replace('_', ' ', $entryStatus)));
    }
@endphp
<div class="page-header">
    <div class="pb-header-title">
        <h4 class="page-title">{{ $thematicArea->localized_name }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('collection-sheet.index') }}">{{ __('Data Collections') }}</a></li>
                <li class="breadcrumb-item active">{{ $thematicArea->localized_name }}</li>
            </ol>
        </nav>
    </div>
    <div class="pb-header-actions">
        <a class="btn btn-outline-dark btn-sm" href="{{ route('indicator-data-entries.index') }}" title="{{ __('Everything already submitted, with its review status') }}">{{ __('All records') }}</a>
        @if ($canSubmit && $financialYear)
        <button type="button" class="btn btn-primary btn-sm" id="csSubmit">{{ __('Submit for review') }}</button>
        @endif
    </div>
</div>

@unless ($financialYear)
<div class="alert alert-warning mb-3">{{ __('No financial years exist yet — add one in Settings before planning.') }}</div>
@endunless

<form method="GET" class="pb-year-bar">
    <label for="csYear">{{ __('Financial year') }}</label>
    <select name="financial_year_id" id="csYear" class="pb-bar-select">
        @foreach ($financialYears as $fy)
        <option value="{{ $fy->id }}" @selected($financialYear?->id === $fy->id)>{{ $fy->name }}</option>
        @endforeach
    </select>
    <label for="csPeriod">{{ __('Reporting period') }}</label>
    <select name="reporting_period_id" id="csPeriod" class="pb-bar-select">
        @forelse ($periods as $p)
        <option value="{{ $p->id }}" @selected($period?->id === $p->id)>{{ $p->name }}</option>
        @empty
        <option value="">—</option>
        @endforelse
    </select>
    <div class="pb-toolbar-end">
        <div class="autosave-flag" id="pbAutosave"><span class="autosave-dot"></span>{{ __('All changes saved') }}</div>
        <button type="button" class="btn btn-outline-dark btn-sm" id="pbFocusToggle" title="{{ __('Give the table more room') }}">
            <i class="mdi mdi-arrow-expand-all"></i> <span>{{ __('Wide view') }}</span>
        </button>
    </div>
</form>

@if ($financialYear && ! $periodOpen)
<div class="alert alert-warning mb-3">{{ __('This period has not started yet — data can only be entered once it opens.') }}</div>
@endif
@if ($rows->isNotEmpty() && $rows->every(fn ($row) => $row['plan'] === null))
<div class="alert alert-info mb-3">{{ __('No indicator here has an approved plan for this financial year yet, so there is nothing to fill in. In the Plan Builder: submit the plan, the lead institution confirms it, then the thematic area coordinator approves it.') }}</div>
@endif
@if ($hiddenCount > 0)
<div class="alert alert-info mb-3">{{ trans_choice(':count indicator reports on a different frequency — pick another reporting period to see it.|:count indicators report on a different frequency — pick another reporting period to see them.', $hiddenCount, ['count' => $hiddenCount]) }}</div>
@endif

<div class="cs-legend">
    <span><i class="cs-swatch cs-swatch-plan"></i> {{ __('From the approved plan (read-only)') }}</span>
    <span><i class="cs-swatch cs-swatch-fill"></i> {{ __('Fill in during data collection') }}</span>
</div>

<div class="pb-sheet" id="pbSheet"
     data-thematic-area-id="{{ $thematicArea->id }}"
     data-financial-year-id="{{ $financialYear?->id }}"
     data-period-id="{{ $period?->id }}">
    <table class="pb-table cs-table">
        <thead>
            <tr>
                <th rowspan="2" class="pb-col-indicator">{{ __('Indicator') }}</th>
                <th rowspan="2" class="pb-col-acts">{{ __('Planned interventions') }}</th>
                <th rowspan="2" class="cs-th-plan">{{ __('Baseline') }}</th>
                <th rowspan="2" class="cs-th-plan">{{ __('Target') }}</th>
                <th colspan="3" class="cs-th-plan">{{ __('Planned budget (TZS)') }}</th>
                <th colspan="3" class="cs-th-fill">{{ __('Implementation') }}</th>
                <th colspan="3" class="cs-th-fill">{{ __('Budget released (TZS)') }}</th>
                <th colspan="3" class="cs-th-fill">{{ __('Budget spent (TZS)') }}</th>
                <th colspan="2" class="cs-th-plan">{{ __('Institutions') }}</th>
                <th rowspan="2" class="cs-th-fill">{{ __('Remarks') }}</th>
                <th rowspan="2" class="pb-col-status">{{ __('Status') }}</th>
                <th rowspan="2" class="cs-th-fill">{{ __('Evidence') }}</th>
            </tr>
            <tr>
                <th class="cs-th-plan">{{ __('Internal') }}</th>
                <th class="cs-th-plan">{{ __('External') }}</th>
                <th class="cs-th-plan">{{ __('Total') }}</th>
                <th class="cs-th-fill">{{ __('Achieved') }}</th>
                <th class="cs-th-fill">{{ __('Progress') }}</th>
                <th class="cs-th-fill">{{ __('Status of implementation') }}</th>
                <th class="cs-th-fill">{{ __('Internal') }}</th>
                <th class="cs-th-fill">{{ __('External') }}</th>
                <th class="cs-th-fill">{{ __('Total') }}</th>
                <th class="cs-th-fill">{{ __('Internal') }}</th>
                <th class="cs-th-fill">{{ __('External') }}</th>
                <th class="cs-th-fill">{{ __('Total') }}</th>
                <th class="cs-th-plan">{{ __('Lead') }}</th>
                <th class="cs-th-plan">{{ __('Partners') }}</th>
            </tr>
        </thead>
        <tbody id="csBody">
            @forelse ($rows as $row)
                @include('collection-sheet.row', [
                    'indicator' => $row['indicator'], 'plan' => $row['plan'], 'entry' => $row['entry'],
                    'missing' => $row['missing'], 'editable' => $row['editable'], 'periodId' => $row['periodId'],
                    'locationLabel' => $row['locationLabel'], 'files' => $row['files'],
                    'organizations' => $organizations, 'financialYears' => $financialYears->keyBy('id'), 'periodOpen' => $periodOpen,
                ])
            @empty
            <tr>
                <td colspan="21">
                    <div class="tbl-empty">
                        <i class="mdi mdi-clipboard-text-outline"></i>
                        <p>{{ __('No indicators to report on for this period.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const sheet = document.getElementById('pbSheet');
    const FINANCIAL_YEAR_ID = Number(sheet.dataset.financialYearId) || null;
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const saveUrl = (id) => @json(url('collection-sheet/'.$thematicArea->id.'/indicators')) + '/' + id;
    const submitUrl = @json(route('collection-sheet.submit', $thematicArea));
    const editUrl = (id) => @json(url('indicator-data-entries')) + '/' + id + '/edit';
    const STATUS_LABELS = @json($statusLabels);

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

    async function api(url, payload) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload || {}),
        });
        const data = await res.json().catch(() => null);
        if (!res.ok) {
            let message = data && data.message ? data.message : @json(__('Something went wrong.'));
            if (data && data.errors) message = Object.values(data.errors).flat().join(' ');
            throw new Error(message);
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

    /* One request at a time per row so a slow save can never overwrite a newer one. */
    const queues = new WeakMap();
    function enqueue(tr, work) {
        const next = (queues.get(tr) || Promise.resolve()).then(work, work);
        queues.set(tr, next.catch(() => {}));
        return next;
    }

    /* ---------- numbers: follow the indicator's type + unit limits ---------- */
    function rules(tr, cell) {
        if (cell.classList.contains('pb-money')) return { decimals: 2, max: null };
        const limits = JSON.parse(tr.dataset.limits || '{}');
        return { decimals: limits.decimals === null || limits.decimals === undefined ? 4 : limits.decimals, max: limits.max ?? null };
    }

    function formatNumber(raw, rule) {
        let cleaned = raw.replace(/[^0-9.]/g, '');
        const firstDot = cleaned.indexOf('.');
        if (firstDot !== -1) cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
        if (rule.decimals === 0) cleaned = cleaned.split('.')[0];
        let [intPart, decPart] = cleaned.split('.');
        if (rule.max !== null && Number(cleaned || 0) > rule.max) { intPart = String(rule.max); decPart = undefined; }
        intPart = intPart.replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return decPart !== undefined ? intPart + '.' + decPart.slice(0, rule.decimals) : intPart;
    }

    const plain = (cell) => (cell ? cell.value.replace(/,/g, '') : '');
    const asNumber = (cell) => (cell && cell.value !== '' ? Number(plain(cell)) : null);
    const money = (value) => value.toLocaleString('en-US', { maximumFractionDigits: 2 });

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
            refreshDerived(tr);
        });
    }

    /* ---------- derived cells: totals + progress against target ---------- */
    function refreshDerived(tr) {
        const sum = (a, b) => (a === null && b === null ? null : (a || 0) + (b || 0));
        const released = sum(asNumber(tr.querySelector('.cs-domestic-released')), asNumber(tr.querySelector('.cs-external-released')));
        const spent = sum(asNumber(tr.querySelector('.cs-domestic-spent')), asNumber(tr.querySelector('.cs-external-spent')));
        tr.querySelector('.cs-released-total').textContent = released === null ? '—' : money(released);
        tr.querySelector('.cs-spent-total').textContent = spent === null ? '—' : money(spent);

        const target = Number(tr.dataset.target);
        const achieved = asNumber(tr.querySelector('.cs-actual'));
        tr.querySelector('.cs-progress').textContent = achieved === null || !target ? '—' : Math.round((achieved / target) * 100) + '%';
    }

    /* ---------- saving ---------- */
    const evidenceUrl = (id) => @json(url('collection-sheet/'.$thematicArea->id.'/indicators')) + '/' + id + '/evidence';

    function context(tr) {
        return {
            financial_year_id: FINANCIAL_YEAR_ID,
            reporting_period_id: tr.dataset.period ? Number(tr.dataset.period) : null,
            entry_id: tr.dataset.entry ? Number(tr.dataset.entry) : null,
            location_id: tr.dataset.location ? Number(tr.dataset.location) : null,
        };
    }

    function payload(tr) {
        const read = (selector) => {
            const el = tr.querySelector(selector);
            return el && el.value !== '' ? plain(el) : null;
        };
        const text = (selector) => {
            const el = tr.querySelector(selector);
            return el && el.value.trim() !== '' ? el.value : null;
        };
        return Object.assign(context(tr), {
            actual_value: read('.cs-actual'),
            actual_text: text('.cs-actual-text'),
            implementation_status: text('.cs-implementation'),
            remarks: text('.cs-remarks'),
            domestic_released: read('.cs-domestic-released'),
            external_released: read('.cs-external-released'),
            domestic_spent: read('.cs-domestic-spent'),
            external_spent: read('.cs-external-spent'),
        });
    }

    function renderFiles(tr, files) {
        const list = tr.querySelector('.cs-files');
        list.innerHTML = '';
        files.forEach((file) => {
            const li = document.createElement('li');
            li.dataset.delete = file.delete_url;
            const link = document.createElement('a');
            link.href = file.url; link.target = '_blank'; link.rel = 'noopener'; link.title = file.name; link.textContent = file.name;
            li.appendChild(link);
            if (tr.querySelector('.cs-file-input')) {
                const remove = document.createElement('button');
                remove.type = 'button'; remove.className = 'cs-file-remove'; remove.title = @json(__('Remove'));
                remove.innerHTML = '<i class="mdi mdi-close"></i>';
                li.appendChild(remove);
            }
            list.appendChild(li);
        });
    }

    function applyState(tr, data) {
        const wasBlank = !tr.dataset.entry;
        tr.dataset.entry = data.id;
        tr.querySelector('.cs-status').textContent = STATUS_LABELS[data.status] || data.status;
        const missing = tr.querySelector('.pb-missing');
        missing.classList.toggle('d-none', data.missing.length === 0);
        missing.title = @json(__('Still missing:')) + ' ' + data.missing.join(', ');
        const details = tr.querySelector('.cs-details');
        if (details) {
            details.href = data.edit_url;
            details.classList.remove('d-none');
        }
        renderFiles(tr, data.files || []);

        // A location row that just got its first save makes room for the next location.
        if (wasBlank && tr.dataset.requiresLocation === '1' && tr._pristine) {
            const next = tr.nextElementSibling;
            if (!next || next.dataset.id !== tr.dataset.id || next.dataset.entry) {
                const blank = tr._pristine.cloneNode(true);
                blank._pristine = tr._pristine;
                tr.after(blank);
                wireRow(blank);
            }
        }
    }

    function save(tr) {
        return enqueue(tr, async () => {
            const res = await tracked(() => api(saveUrl(tr.dataset.id), payload(tr)));
            if (res) applyState(tr, res.data);
        });
    }

    async function sendFiles(tr, files) {
        const form = new FormData();
        Object.entries(context(tr)).forEach(([key, value]) => { if (value !== null) form.append(key, value); });
        Array.from(files).forEach((file) => form.append('files[]', file));
        const res = await fetch(evidenceUrl(tr.dataset.id), {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: form,
        });
        const data = await res.json().catch(() => null);
        if (!res.ok) {
            throw new Error(data && data.errors ? Object.values(data.errors).flat().join(' ') : (data && data.message) || @json(__('Something went wrong.')));
        }
        return data;
    }

    function autosize(el) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }

    function wireLocation(tr) {
        const select = tr.querySelector('.cs-location');
        if (!select || select.disabled || !(window.jQuery && jQuery.fn.select2)) return;
        jQuery(select).select2({
            width: '100%',
            placeholder: select.dataset.placeholder,
            dropdownCssClass: 'pb-select2-dropdown',
            language: { noResults: () => @json(__('No results found')), searching: () => @json(__('Searching…')) },
            ajax: {
                url: select.dataset.url,
                delay: 250,
                dataType: 'json',
                data: (params) => ({ q: params.term || '' }),
                processResults: (data) => data,
            },
        });
        jQuery(select).on('change', () => {
            tr.dataset.location = select.value || '';
            if (select.value) save(tr);
        });
    }

    function wireRow(tr) {
        if (tr.dataset.requiresLocation === '1' && !tr.dataset.entry && !tr._pristine) {
            tr._pristine = tr.cloneNode(true);
        }
        tr.querySelectorAll('.pb-num.cs-input').forEach((cell) => wireNumberCell(tr, cell));
        tr.querySelectorAll('.cs-input').forEach((el) => el.addEventListener('change', () => save(tr)));
        tr.querySelectorAll('textarea.cs-input').forEach((el) => {
            el.addEventListener('input', () => autosize(el));
            autosize(el);
        });
        wireLocation(tr);

        const fileInput = tr.querySelector('.cs-file-input');
        if (fileInput) {
            fileInput.addEventListener('change', () => {
                const files = fileInput.files;
                if (!files.length) return;
                enqueue(tr, async () => {
                    const res = await tracked(() => sendFiles(tr, files));
                    if (res) applyState(tr, res.data);
                    fileInput.value = '';
                });
            });
        }
        tr.querySelector('.cs-files').addEventListener('click', (event) => {
            const button = event.target.closest('.cs-file-remove');
            if (!button) return;
            const url = button.closest('li').dataset.delete;
            enqueue(tr, async () => {
                const res = await tracked(async () => {
                    const response = await fetch(url, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
                    const data = await response.json().catch(() => null);
                    if (!response.ok) throw new Error((data && data.message) || @json(__('Something went wrong.')));
                    return data;
                });
                if (res) applyState(tr, res.data);
            });
        });
        refreshDerived(tr);
    }

    document.querySelectorAll('.cs-row').forEach(wireRow);

    /* ---------- submit ---------- */
    const submitBtn = document.getElementById('csSubmit');
    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            submitBtn.disabled = true;
            try {
                await Promise.all([...document.querySelectorAll('.cs-row')].map((tr) => queues.get(tr) || null));
                const res = await api(submitUrl, { financial_year_id: FINANCIAL_YEAR_ID, reporting_period_id: Number(sheet.dataset.periodId) || null });
                const problems = res.data.problems || [];
                window.showToast && window.showToast(problems.length ? 'warning' : 'success',
                    @json(__('Submitted for review:')) + ' ' + res.data.submitted + (problems.length ? ' — ' + @json(__('needs attention:')) + ' ' + problems.join(' • ') : ''));
                setTimeout(() => window.location.reload(), 1200);
            } catch (e) {
                window.showToast && window.showToast('danger', e.message);
                submitBtn.disabled = false;
            }
        });
    }

    /* ---------- wide view ---------- */
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

    /* ---------- the second header row sticks under the first ---------- */
    function syncHeaderOffset() {
        sheet.style.setProperty('--pb-head-h', sheet.querySelector('thead tr:first-child').offsetHeight + 'px');
    }
    syncHeaderOffset();
    window.addEventListener('resize', syncHeaderOffset);
})();
</script>
@endpush
