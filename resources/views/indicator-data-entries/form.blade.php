@php
    $entry = $entry ?? null;
    $assignmentScopes = $assignmentScopes ?? collect();
    $existingRows = old('rows', $entry?->rows?->map(fn ($row) => [
        'label' => $row->label,
        'value' => $row->value,
        'dimension_option_ids' => $row->dimensionOptions->pluck('id')->all(),
    ])->all() ?? []);
    $existingExpenses = old('expenses', $entry?->expenses?->map(fn ($expense) => [
        'expense_category' => $expense->expense_category,
        'description' => $expense->description,
        'amount' => $expense->amount,
    ])->all() ?? []);
    $existingActivities = old('activities', $entry?->activities?->map(fn ($activity) => [
        'name' => $activity->name,
        'description' => $activity->description,
        'participants_total' => $activity->participants_total,
        'women' => $activity->women,
        'men' => $activity->men,
        'children' => $activity->children,
        'other' => $activity->other,
    ])->all() ?? []);
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="indicator_id" class="form-label">{{ __('Indicator') }}</label>
        @if ($entry)
        <input type="hidden" name="indicator_id" id="indicator_id" value="{{ $entry->indicator_id }}"
               data-requires-location="{{ $entry->indicator->requires_location ? '1' : '0' }}"
               data-reporting-location-level="{{ $entry->indicator->reporting_location_level }}"
               data-requires-activity="{{ $entry->indicator->requires_activity ? '1' : '0' }}"
               data-has-budget-implication="{{ $entry->indicator->has_budget_implication ? '1' : '0' }}"
               data-requires-evidence="{{ $entry->indicator->requires_evidence ? '1' : '0' }}"
               data-collection-scope="{{ $entry->indicator->collection_scope }}"
               data-has-dimensions="{{ $entry->indicator->dimensions->isNotEmpty() ? '1' : '0' }}"
               data-reporting-frequency="{{ $entry->indicator->reporting_frequency }}"
               data-collection-mode="{{ $entry->indicator->collection_mode }}"
               data-aggregation-method="{{ $entry->indicator->aggregation_method }}"
               data-measurement-type="{{ $entry->indicator->measurementType?->code }}"
               data-unit-name="{{ $entry->indicator->unitOfMeasure?->name }}"
               data-unit-symbol="{{ $entry->indicator->unitOfMeasure?->symbol }}">
        <div class="border rounded bg-light px-3 py-2 lh-base text-break" aria-readonly="true">
            @if ($entry->indicator?->code)
                <span class="fw-semibold">{{ $entry->indicator->code }}</span>
                <span class="text-muted mx-1">&middot;</span>
            @endif
            <span>{{ $entry->indicator?->name }}</span>
        </div>
        @else
        <select name="indicator_id" id="indicator_id" class="form-control select2 @error('indicator_id') is-invalid @enderror" required>
            <option value="">{{ __('Select an indicator…') }}</option>
            @foreach ($indicators as $indicator)
            <option value="{{ $indicator->id }}"
                    data-requires-location="{{ $indicator->requires_location ? '1' : '0' }}"
                    data-reporting-location-level="{{ $indicator->reporting_location_level }}"
                    data-requires-activity="{{ $indicator->requires_activity ? '1' : '0' }}"
                    data-has-budget-implication="{{ $indicator->has_budget_implication ? '1' : '0' }}"
                    data-requires-evidence="{{ $indicator->requires_evidence ? '1' : '0' }}"
                    data-collection-scope="{{ $indicator->collection_scope }}"
                    data-has-dimensions="{{ $indicator->dimensions->isNotEmpty() ? '1' : '0' }}"
                    data-reporting-frequency="{{ $indicator->reporting_frequency }}"
                    data-collection-mode="{{ $indicator->collection_mode }}"
                    data-aggregation-method="{{ $indicator->aggregation_method }}"
                    data-measurement-type="{{ $indicator->measurementType?->code }}"
                    data-unit-name="{{ $indicator->unitOfMeasure?->name }}"
                    data-unit-symbol="{{ $indicator->unitOfMeasure?->symbol }}"
                    @selected((int) old('indicator_id', $entry?->indicator_id) === $indicator->id)>{{ $indicator->name }}</option>
            @endforeach
        </select>
        @endif
        @error('indicator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <p class="text-muted small mb-0 mt-1" id="indicator-config-hint"></p>
    </div>

    <div class="col-md-6 mb-3 d-none">
        <label for="financial_year_id" class="form-label">{{ __('Financial Year') }}</label>
        <select name="financial_year_id" id="financial_year_id" class="form-control @error('financial_year_id') is-invalid @enderror" required>
            <option value="">{{ __('Select a financial year…') }}</option>
            @foreach ($financialYears as $financialYear)
            <option value="{{ $financialYear->id }}" data-start-date="{{ $financialYear->start_date?->toDateString() }}" data-end-date="{{ $financialYear->end_date?->toDateString() }}" @selected((int) old('financial_year_id', $entry?->financial_year_id) === $financialYear->id)>{{ $financialYear->name }}</option>
            @endforeach
        </select>
        @error('financial_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3 js-reporting-period-field d-none">
        <label for="reporting_period_id" class="form-label">{{ __('Reporting Period') }}</label>
        <select name="reporting_period_id" id="reporting_period_id" class="form-control @error('reporting_period_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($reportingPeriods as $period)
            <option value="{{ $period->id }}" data-financial-year-id="{{ $period->financial_year_id }}" data-period-type="{{ $period->period_type }}" data-start-date="{{ $period->start_date?->toDateString() }}" data-end-date="{{ $period->end_date?->toDateString() }}" @selected((int) old('reporting_period_id', $entry?->reporting_period_id) === $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
        @error('reporting_period_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3 js-entry-date-field d-none">
        <label for="entry_date" class="form-label">{{ __('Entry Date') }}</label>
        <input type="date" name="entry_date" id="entry_date" class="form-control @error('entry_date') is-invalid @enderror"
               value="{{ old('entry_date', $entry?->entry_date?->toDateString() ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
        @error('entry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3 js-activity-field d-none">
        <label for="activity_name" class="form-label">{{ __('Activity Name') }} <span class="text-danger d-none js-activity-required-mark">*</span></label>
        <input type="text" name="activity_name" id="activity_name" class="form-control @error('activity_name') is-invalid @enderror"
               value="{{ old('activity_name', $entry?->activity_name) }}" maxlength="255">
        @error('activity_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3 js-organization-field d-none">
        <label for="organization_id" class="form-label">{{ __('Organization') }}</label>
        <select name="organization_id" id="organization_id" class="form-control select2 @error('organization_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($organizations as $organization)
            <option value="{{ $organization->id }}" @selected((int) old('organization_id', $entry?->organization_id) === $organization->id)>{{ $organization->name }}</option>
            @endforeach
        </select>
        @error('organization_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3 js-activity-field d-none">
        <label for="activity_description" class="form-label">{{ __('Activity Description') }}</label>
        <textarea name="activity_description" id="activity_description" rows="2"
                  class="form-control @error('activity_description') is-invalid @enderror">{{ old('activity_description', $entry?->activity_description) }}</textarea>
        @error('activity_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3 js-location-field">
        <input type="hidden" name="location_level" id="location_level" value="{{ old('location_level', $entry?->location_level) }}">
        <label class="form-label" id="location-label">Reporting Location</label>
        @include('components.location-cascade', [
            'currentId' => old('location_id', $entry?->location_id),
            'ancestorChain' => $locationAncestorChain ?? [],
            'targetLevel' => old('location_level', $entry?->location_level),
        ])
        @error('location_level')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3 js-actual-field">
        <label for="actual_value" class="form-label" id="actual-value-label" data-base-label="{{ __('Actual Value') }}">{{ __('Actual Value') }} <span class="text-danger js-actual-required-mark">*</span></label>
        <input type="text" inputmode="decimal" min="0" step="0.0001" name="actual_value" id="actual_value" class="form-control @error('actual_value') is-invalid @enderror"
               value="{{ old('actual_value', \App\Support\DisplayNumber::input($entry?->actual_value)) }}" required>
        <select id="actual_value_yes_no" class="form-control d-none" data-no-select2 hidden disabled>
            <option value="">{{ __('Select…') }}</option>
            <option value="1">{{ __('Yes') }}</option>
            <option value="0">No</option>
        </select>
        <textarea name="actual_text" id="actual_text" rows="3" class="form-control d-none @error('actual_text') is-invalid @enderror" hidden disabled
                  placeholder="Enter the text response">{{ old('actual_text', $entry?->actual_text) }}</textarea>
        @error('actual_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @error('actual_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3 js-budget-field">
        <label for="budget_allocated" class="form-label">{{ __('Budget Allocated') }} <span class="text-muted small">{{ __('(optional)') }}</span></label>
        <input type="number" step="0.01" name="budget_allocated" id="budget_allocated" class="form-control @error('budget_allocated') is-invalid @enderror"
               value="{{ old('budget_allocated', \App\Support\DisplayNumber::input($entry?->budget_allocated, 2)) }}">
        @error('budget_allocated')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3 js-budget-field">
        <label for="budget_used" class="form-label">{{ __('Budget Used') }}</label>
        <input type="number" step="0.01" name="budget_used" id="budget_used" class="form-control @error('budget_used') is-invalid @enderror"
               value="{{ old('budget_used', \App\Support\DisplayNumber::input($entry?->budget_used, 2)) }}">
        @error('budget_used')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3 js-budget-field">
        <label for="currency" class="form-label">{{ __('Currency') }}</label>
        <input type="text" name="currency" id="currency" class="form-control @error('currency') is-invalid @enderror"
               value="{{ old('currency', $entry?->currency ?? 'TZS') }}" maxlength="3">
        @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="remarks" class="form-label">{{ __('Remarks') }}</label>
        <textarea name="remarks" id="remarks" rows="2"
                  class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks', $entry?->remarks) }}</textarea>
        @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-4 js-activities-section">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <label class="form-label mb-0">{{ __('Activities') }}</label>
            <p class="text-muted small mb-0">Add every activity and its participant breakdown.</p>
        </div>
        <button type="button" id="add-activity-btn" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-plus"></i> {{ __('Add Activity') }}</button>
    </div>
    <div id="activities-list"></div>
    @error('activities')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

{{-- Disaggregated rows --}}
<div class="mb-4 js-dimensions-section">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0">Disaggregated Rows</label>
        <button type="button" id="add-row-btn" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-plus"></i> {{ __('Add Row') }}</button>
    </div>
    <table class="table table-sm mb-0" id="rows-table">
        <thead>
            <tr><th>{{ __('Label') }}</th><th>{{ __('Value') }}</th><th class="text-end">{{ __('Remove') }}</th></tr>
        </thead>
        <tbody id="rows-tbody"></tbody>
    </table>
    <p class="text-muted small mb-0" id="rows-empty-hint">No disaggregated rows — the entry's single Actual Value will be used.</p>
</div>

{{-- Expenses --}}
<div class="mb-4 js-budget-field">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0">Expenses</label>
        <button type="button" id="add-expense-btn" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-plus"></i> {{ __('Add Expense') }}</button>
    </div>
    <table class="table table-sm mb-0" id="expenses-table">
        <thead>
            <tr><th>{{ __('Category') }}</th><th>{{ __('Description') }}</th><th>{{ __('Amount') }}</th><th class="text-end">{{ __('Remove') }}</th></tr>
        </thead>
        <tbody id="expenses-tbody"></tbody>
    </table>
    <p class="text-muted small mb-0" id="expenses-empty-hint">No expenses recorded for this collection.</p>
</div>

@php
    $canEditEvidence = ! $entry || in_array($entry->status, ['draft', 'rejected'], true);
    $existingEvidence = $entry?->getMedia('evidence') ?? collect();
@endphp

{{-- Evidence --}}
<div class="mb-4 js-evidence-section">
    <label class="form-label">{{ __('Evidence') }} <span class="text-muted small">{{ __('(optional)') }}</span></label>

    @if ($existingEvidence->isNotEmpty())
    <table class="table table-sm mb-2">
        <thead>
            <tr><th>{{ __('File') }}</th><th>{{ __('Size') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
        </thead>
        <tbody>
            @foreach ($existingEvidence as $media)
            <tr>
                <td>{{ $media->file_name }}</td>
                <td>{{ number_format($media->size / 1024, 1) }} KB</td>
                <td class="text-end">
                    <a href="{{ route('indicator-data-entries.evidence.download', [$entry, $media]) }}" class="btn-icon" title="Download"><i class="mdi mdi-download-outline"></i></a>
                    @if ($canEditEvidence)
                    <button type="submit" form="delete-evidence-{{ $media->id }}" class="btn-icon danger" title="{{ __('Remove') }}"><i class="mdi mdi-trash-can-outline"></i></button>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if ($canEditEvidence)
    <input type="file" name="evidence[]" id="evidence" class="form-control @error('evidence') is-invalid @enderror" multiple
           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
    @error('evidence')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    <p class="text-muted small mb-0 mt-1">PDF, image, Word, or Excel files, up to 10MB each. Adds to whatever's already attached.</p>
    @endif
</div>

@push('scripts')
<script>
(function () {
    var initialRows = @json($existingRows);
    var initialExpenses = @json($existingExpenses);
    var initialActivities = @json($existingActivities);
    var assignmentScopes = @json($assignmentScopes);
    var dimensionConfig = @json($dimensionConfig ?? []);
    var rowIndex = 0;
    var expenseIndex = 0;
    var activityIndex = 0;

    function applyIndicatorConfig() {
        var select = document.getElementById('indicator_id');
        var option = select.tagName === 'SELECT' ? select.options[select.selectedIndex] : select;
        var hint = document.getElementById('indicator-config-hint');

        var requiresLocation = option && option.dataset.requiresLocation === '1';
        var reportingLocationLevel = option ? option.dataset.reportingLocationLevel : '';
        var requiresActivity = option && option.dataset.requiresActivity === '1';
        var hasBudgetImplication = option && option.dataset.hasBudgetImplication === '1';
        var requiresEvidence = option && option.dataset.requiresEvidence === '1';
        var collectionScope = option ? option.dataset.collectionScope : '';
        var hasDimensions = option && option.dataset.hasDimensions === '1';
        var reportingFrequency = option ? option.dataset.reportingFrequency : '';
        var collectionMode = option ? option.dataset.collectionMode : '';
        var aggregationMethod = option ? option.dataset.aggregationMethod : '';
        var measurementType = option ? option.dataset.measurementType : '';
        var unitName = option ? option.dataset.unitName : '';
        var unitSymbol = option ? option.dataset.unitSymbol : '';
        var assignmentScope = option ? assignmentScopes[option.value] : null;

        function toggleFields(selector, visible) {
            document.querySelectorAll(selector).forEach(function (el) { el.classList.toggle('d-none', ! visible); });
        }

        toggleFields('.js-location-field', requiresLocation);
        toggleFields('.js-activities-section', requiresActivity);
        toggleFields('.js-budget-field', hasBudgetImplication);
        toggleFields('.js-evidence-section', requiresEvidence);
        toggleFields('.js-dimensions-section', hasDimensions);

        document.querySelectorAll('.js-activity-required-mark').forEach(function (el) { el.classList.toggle('d-none', ! requiresActivity); });

        document.getElementById('activity_name').required = false;
        document.getElementById('budget_allocated').required = false;

        var locationLevel = document.getElementById('location_level');
        var locationId = document.getElementById('location_id');
        locationLevel.disabled = ! requiresLocation;
        locationId.disabled = ! requiresLocation;
        if (! requiresLocation) {
            locationLevel.value = '';
            locationId.value = '';
        }
        locationLevel.value = requiresLocation ? reportingLocationLevel : '';
        document.getElementById('location-label').textContent = reportingLocationLevel
            ? 'Reporting Location (' + reportingLocationLevel.replaceAll('_', ' ').replace(/\b\w/g, function (letter) { return letter.toUpperCase(); }) + ')'
            : 'Reporting Location';

        var organization = document.getElementById('organization_id');
        if (assignmentScope && assignmentScope.organization_id) {
            organization.value = String(assignmentScope.organization_id);
            organization.dispatchEvent(new Event('change'));
            organization.setAttribute('data-assignment-locked', '1');
        } else {
            organization.removeAttribute('data-assignment-locked');
        }

        Array.from(organization.options).forEach(function (candidate) {
            candidate.disabled = Boolean(assignmentScope && assignmentScope.organization_id)
                && candidate.value !== String(assignmentScope.organization_id);
        });
        var cascade = document.querySelector('.location-cascade');
        if (cascade && requiresLocation) {
            cascade.dispatchEvent(new CustomEvent('location-cascade:set', {
                detail: {
                    targetLevel: reportingLocationLevel,
                    ancestorChain: assignmentScope && assignmentScope.location_id ? (assignmentScope.location_chain || {}) : @json($locationAncestorChain ?? []),
                    lockedThroughLevel: assignmentScope && assignmentScope.location_id ? assignmentScope.location_level : null
                }
            }));
        }

        configurePeriodChoices(reportingFrequency);
        configureActualValue(measurementType, unitName, unitSymbol);

        var notes = [];
        if (requiresLocation) { notes.push('reporting location'); }
        if (requiresActivity) { notes.push('activity name'); }
        if (hasBudgetImplication) { notes.push('budget details available'); }
        if (requiresEvidence) { notes.push('evidence upload available'); }

        var behavior = collectionMode ? ('Collection: ' + collectionMode + '; aggregation: ' + aggregationMethod + '; frequency: ' + reportingFrequency + '.') : '';
        hint.textContent = (notes.length ? ('Required: ' + notes.join(', ') + '. ') : '') + behavior;
    }

    function configurePeriodChoices(reportingFrequency) {
        var periodSelect = document.getElementById('reporting_period_id');
        var financialYearId = document.getElementById('financial_year_id').value;
        var requiredType = { weekly: 'week', monthly: 'month', quarterly: 'quarter', biannual: 'semi_annual', annual: 'annual' }[reportingFrequency] || null;
        var firstAllowed = null;
        var currentAllowed = null;
        var lastAllowed = null;
        var today = '{{ now()->toDateString() }}';

        Array.from(periodSelect.options).forEach(function (period) {
            if (! period.value) { return; }
            var allowed = Boolean(requiredType)
                && period.dataset.financialYearId === financialYearId
                && period.dataset.periodType === requiredType;
            period.hidden = ! allowed;
            period.disabled = ! allowed;
            if (allowed && ! firstAllowed) { firstAllowed = period; }
            if (allowed) {
                lastAllowed = period;
                if (period.dataset.startDate <= today && period.dataset.endDate >= today) { currentAllowed = period; }
            }
        });
        var selected = periodSelect.options[periodSelect.selectedIndex];
        if (! selected || ! selected.value || selected.disabled) {
            var preferred = currentAllowed || lastAllowed || firstAllowed;
            periodSelect.value = preferred ? preferred.value : '';
        }
        periodSelect.required = Boolean(requiredType);

        var yearOption = document.getElementById('financial_year_id').selectedOptions[0];
        var entryDate = document.getElementById('entry_date');
        if (yearOption && yearOption.value) {
            entryDate.min = yearOption.dataset.startDate || '';
            entryDate.max = yearOption.dataset.endDate && yearOption.dataset.endDate < today ? yearOption.dataset.endDate : today;
        }
        syncEntryDateToPeriod();
    }

    function syncEntryDateToPeriod() {
        var period = document.getElementById('reporting_period_id').selectedOptions[0];
        var entryDate = document.getElementById('entry_date');
        var today = '{{ now()->toDateString() }}';
        if (! period || ! period.value) { return; }

        entryDate.min = period.dataset.startDate;
        entryDate.max = period.dataset.endDate < today ? period.dataset.endDate : today;
        if (! entryDate.value || entryDate.value < entryDate.min || entryDate.value > entryDate.max) {
            entryDate.value = entryDate.max;
        }
    }

    function configureActualValue(measurementType, unitName, unitSymbol) {
        var input = document.getElementById('actual_value');
        var yesNo = document.getElementById('actual_value_yes_no');
        var textInput = document.getElementById('actual_text');
        var label = document.getElementById('actual-value-label');
        var isText = measurementType === 'text' || measurementType === 'qualitative';
        var isYesNo = measurementType === 'yes_no';
        var unitSuffix = unitName ? ' (' + unitName + (unitSymbol ? ' - ' + unitSymbol : '') + ')' : '';
        label.replaceChildren();
        label.appendChild(document.createTextNode((label.dataset.baseLabel || 'Actual Value') + unitSuffix + ' '));
        var requiredMark = document.createElement('span');
        requiredMark.className = 'text-danger js-actual-required-mark';
        requiredMark.textContent = '*';
        label.appendChild(requiredMark);
        input.classList.toggle('d-none', isYesNo || isText);
        input.hidden = isYesNo || isText;
        input.disabled = isText;
        yesNo.classList.toggle('d-none', ! isYesNo);
        yesNo.hidden = ! isYesNo;
        yesNo.disabled = ! isYesNo;
        textInput.classList.toggle('d-none', ! isText);
        textInput.hidden = ! isText;
        textInput.disabled = ! isText;
        input.step = measurementType === 'count' ? '1' : (measurementType === 'currency' ? '0.01' : '0.0001');
        input.min = isText ? '' : '0';
        input.max = measurementType === 'percentage' ? '100' : '';
        input.required = ! isText && ! isYesNo;
        yesNo.required = isYesNo;
        textInput.required = isText;
        document.querySelectorAll('.js-actual-required-mark').forEach(function (mark) {
            mark.classList.toggle('d-none', isText);
        });
        if (isYesNo) { yesNo.value = input.value; }
    }

    document.getElementById('indicator_id').addEventListener('change', function () {
        applyIndicatorConfig();
        renderConfiguredRows(true);
    });
    var indicatorSelect = document.getElementById('indicator_id');
    var financialYearSelect = document.getElementById('financial_year_id');
    if (! financialYearSelect.value && financialYearSelect.options.length > 1) {
        financialYearSelect.selectedIndex = 1;
    }
    if (indicatorSelect.tagName === 'SELECT' && ! indicatorSelect.value && indicatorSelect.options.length === 2) {
        indicatorSelect.selectedIndex = 1;
        indicatorSelect.dispatchEvent(new Event('change'));
    }
    applyIndicatorConfig();
    document.getElementById('financial_year_id').addEventListener('change', applyIndicatorConfig);
    document.getElementById('reporting_period_id').addEventListener('change', syncEntryDateToPeriod);
    document.getElementById('actual_value_yes_no').addEventListener('change', function () {
        document.getElementById('actual_value').value = this.value;
    });

    function toggleHint(tbodyId, hintId) {
        var tbody = document.getElementById(tbodyId);
        document.getElementById(hintId).style.display = tbody.children.length ? 'none' : 'block';
    }

    function addRow(data) {
        data = data || {};
        var i = rowIndex++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" class="form-control form-control-sm" name="rows[' + i + '][label]" value="' + (data.label || '') + '"></td>' +
            '<td><input type="number" step="0.0001" class="form-control form-control-sm" name="rows[' + i + '][value]" value="' + (data.value != null ? data.value : '') + '" required></td>' +
            '<td class="text-end"><button type="button" class="btn-icon danger btn-remove-row" title="{{ __('Remove') }}"><i class="mdi mdi-trash-can-outline"></i></button></td>';
        (data.dimension_option_ids || []).forEach(function (optionId) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'rows[' + i + '][dimension_option_ids][]';
            hidden.value = optionId;
            tr.firstElementChild.appendChild(hidden);
        });
        document.getElementById('rows-tbody').appendChild(tr);
        toggleHint('rows-tbody', 'rows-empty-hint');
    }

    function addExpense(data) {
        data = data || {};
        var i = expenseIndex++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" class="form-control form-control-sm" name="expenses[' + i + '][expense_category]" value="' + (data.expense_category || '') + '"></td>' +
            '<td><input type="text" class="form-control form-control-sm" name="expenses[' + i + '][description]" value="' + (data.description || '') + '" required></td>' +
            '<td><input type="number" step="0.01" class="form-control form-control-sm" name="expenses[' + i + '][amount]" value="' + (data.amount != null ? data.amount : '') + '" required></td>' +
            '<td class="text-end"><button type="button" class="btn-icon danger btn-remove-expense" title="{{ __('Remove') }}"><i class="mdi mdi-trash-can-outline"></i></button></td>';
        document.getElementById('expenses-tbody').appendChild(tr);
        toggleHint('expenses-tbody', 'expenses-empty-hint');
    }

    function addActivity(data) {
        data = data || {};
        var i = activityIndex++;
        var card = document.createElement('div');
        card.className = 'border rounded p-3 mb-2 activity-card';
        card.innerHTML =
            '<div class="d-flex justify-content-between"><strong>' + @json(__('Activity')) + ' ' + (i + 1) + '</strong><button type="button" class="btn-icon danger btn-remove-activity" title="{{ __('Remove') }}"><i class="mdi mdi-trash-can-outline"></i></button></div>' +
            '<div class="row">' +
            '<div class="col-md-6 mb-2"><label class="form-label small">Activity name</label><input type="text" class="form-control" name="activities[' + i + '][name]" required></div>' +
            '<div class="col-md-6 mb-2"><label class="form-label small">{{ __('Description') }}</label><input type="text" class="form-control" name="activities[' + i + '][description]"></div>' +
            '<div class="col-md-2 mb-2"><label class="form-label small">Total participants</label><input type="number" min="0" class="form-control" name="activities[' + i + '][participants_total]" required></div>' +
            '<div class="col-md-2 mb-2"><label class="form-label small">{{ __('Women') }}</label><input type="number" min="0" class="form-control" name="activities[' + i + '][women]"></div>' +
            '<div class="col-md-2 mb-2"><label class="form-label small">{{ __('Men') }}</label><input type="number" min="0" class="form-control" name="activities[' + i + '][men]"></div>' +
            '<div class="col-md-2 mb-2"><label class="form-label small">{{ __('Children') }}</label><input type="number" min="0" class="form-control" name="activities[' + i + '][children]"></div>' +
            '<div class="col-md-2 mb-2"><label class="form-label small">{{ __('Other') }}</label><input type="number" min="0" class="form-control" name="activities[' + i + '][other]"></div>' +
            '</div>';
        var values = { name: '', description: '', participants_total: 0, women: 0, men: 0, children: 0, other: 0 };
        Object.keys(values).forEach(function (field) {
            card.querySelector('[name="activities[' + i + '][' + field + ']"]').value = data[field] ?? values[field];
        });
        document.getElementById('activities-list').appendChild(card);
    }

    function renderConfiguredRows(force) {
        var indicatorId = document.getElementById('indicator_id').value;
        var dimensions = dimensionConfig[indicatorId] || [];
        var tbody = document.getElementById('rows-tbody');

        if (! force && tbody.children.length) {
            return;
        }

        tbody.innerHTML = '';
        dimensions.forEach(function (dimension) {
            dimension.options.forEach(function (option) {
                addRow({
                    label: dimension.name + ' - ' + option.name,
                    dimension_option_ids: [option.id]
                });
            });
        });
        toggleHint('rows-tbody', 'rows-empty-hint');
    }

    document.getElementById('add-row-btn').addEventListener('click', function () { addRow(); });
    document.getElementById('add-expense-btn').addEventListener('click', function () { addExpense(); });
    document.getElementById('add-activity-btn').addEventListener('click', function () { addActivity(); });

    document.getElementById('rows-tbody').addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-row')) {
            e.target.closest('tr').remove();
            toggleHint('rows-tbody', 'rows-empty-hint');
        }
    });

    document.getElementById('expenses-tbody').addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-expense')) {
            e.target.closest('tr').remove();
            toggleHint('expenses-tbody', 'expenses-empty-hint');
        }
    });

    document.getElementById('activities-list').addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-activity')) {
            e.target.closest('.activity-card').remove();
        }
    });

    initialRows.forEach(addRow);
    initialExpenses.forEach(addExpense);
    initialActivities.forEach(addActivity);
    if (! initialActivities.length && document.getElementById('indicator_id').dataset.requiresActivity === '1') { addActivity(); }
    renderConfiguredRows(false);
    toggleHint('rows-tbody', 'rows-empty-hint');
    toggleHint('expenses-tbody', 'expenses-empty-hint');
})();
</script>
@endpush
