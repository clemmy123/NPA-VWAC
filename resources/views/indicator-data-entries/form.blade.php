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
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="indicator_id" class="form-label">Indicator</label>
        <select name="indicator_id" id="indicator_id" class="form-control select2 @error('indicator_id') is-invalid @enderror" required>
            <option value="">Select an indicator…</option>
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
        @error('indicator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <p class="text-muted small mb-0 mt-1" id="indicator-config-hint"></p>
    </div>

    <div class="col-md-6 mb-3">
        <label for="financial_year_id" class="form-label">Financial Year</label>
        <select name="financial_year_id" id="financial_year_id" class="form-control @error('financial_year_id') is-invalid @enderror" required>
            <option value="">Select a financial year…</option>
            @foreach ($financialYears as $financialYear)
            <option value="{{ $financialYear->id }}" data-start-date="{{ $financialYear->start_date?->toDateString() }}" data-end-date="{{ $financialYear->end_date?->toDateString() }}" @selected((int) old('financial_year_id', $entry?->financial_year_id) === $financialYear->id)>{{ $financialYear->name }}</option>
            @endforeach
        </select>
        @error('financial_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3 js-reporting-period-field">
        <label for="reporting_period_id" class="form-label">Reporting Period</label>
        <select name="reporting_period_id" id="reporting_period_id" class="form-control @error('reporting_period_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($reportingPeriods as $period)
            <option value="{{ $period->id }}" data-financial-year-id="{{ $period->financial_year_id }}" data-period-type="{{ $period->period_type }}" data-start-date="{{ $period->start_date?->toDateString() }}" data-end-date="{{ $period->end_date?->toDateString() }}" @selected((int) old('reporting_period_id', $entry?->reporting_period_id) === $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
        @error('reporting_period_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3 js-activity-field">
        <label for="entry_date" class="form-label">Entry Date</label>
        <input type="date" name="entry_date" id="entry_date" class="form-control @error('entry_date') is-invalid @enderror"
               value="{{ old('entry_date', $entry?->entry_date?->toDateString() ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
        @error('entry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3 js-organization-field">
        <label for="activity_name" class="form-label">Activity Name <span class="text-danger d-none js-activity-required-mark">*</span></label>
        <input type="text" name="activity_name" id="activity_name" class="form-control @error('activity_name') is-invalid @enderror"
               value="{{ old('activity_name', $entry?->activity_name) }}" maxlength="255">
        @error('activity_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="organization_id" class="form-label">Organization</label>
        <select name="organization_id" id="organization_id" class="form-control select2 @error('organization_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($organizations as $organization)
            <option value="{{ $organization->id }}" @selected((int) old('organization_id', $entry?->organization_id) === $organization->id)>{{ $organization->name }}</option>
            @endforeach
        </select>
        @error('organization_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3 js-activity-field">
        <label for="activity_description" class="form-label">Activity Description</label>
        <textarea name="activity_description" id="activity_description" rows="2"
                  class="form-control @error('activity_description') is-invalid @enderror">{{ old('activity_description', $entry?->activity_description) }}</textarea>
        @error('activity_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3 js-location-field">
        <label for="location_level" class="form-label">Location Level <span class="text-danger d-none js-location-required-mark">*</span></label>
        <select name="location_level" id="location_level" class="form-control @error('location_level') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($locationLevels as $level)
            <option value="{{ $level }}" @selected(old('location_level', $entry?->location_level) === $level)>{{ ucfirst(str_replace('_', ' ', $level)) }}</option>
            @endforeach
        </select>
        @error('location_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-5 mb-3 js-location-field">
        <label class="form-label">Location</label>
        @include('components.location-cascade', [
            'currentId' => old('location_id', $entry?->location_id),
            'ancestorChain' => $locationAncestorChain ?? [],
        ])
        @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <p class="text-muted small mb-0 mt-1">Pick a Location Level above, then narrow down to the exact place.</p>
    </div>

    <div class="col-md-4 mb-3 js-actual-field">
        <label for="actual_value" class="form-label" id="actual-value-label">Actual Value</label>
        <input type="number" step="0.0001" name="actual_value" id="actual_value" class="form-control @error('actual_value') is-invalid @enderror"
               value="{{ old('actual_value', $entry?->actual_value) }}">
        <select id="actual_value_yes_no" class="form-control d-none">
            <option value="">Select…</option>
            <option value="1">Yes</option>
            <option value="0">No</option>
        </select>
        @error('actual_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3 js-budget-field">
        <label for="budget_allocated" class="form-label">Budget Allocated <span class="text-danger d-none js-budget-required-mark">*</span></label>
        <input type="number" step="0.01" name="budget_allocated" id="budget_allocated" class="form-control @error('budget_allocated') is-invalid @enderror"
               value="{{ old('budget_allocated', $entry?->budget_allocated) }}">
        @error('budget_allocated')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3 js-budget-field">
        <label for="budget_used" class="form-label">Budget Used</label>
        <input type="number" step="0.01" name="budget_used" id="budget_used" class="form-control @error('budget_used') is-invalid @enderror"
               value="{{ old('budget_used', $entry?->budget_used) }}">
        @error('budget_used')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3 js-budget-field">
        <label for="currency" class="form-label">Currency</label>
        <input type="text" name="currency" id="currency" class="form-control @error('currency') is-invalid @enderror"
               value="{{ old('currency', $entry?->currency ?? 'TZS') }}" maxlength="3">
        @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="remarks" class="form-label">Remarks</label>
        <textarea name="remarks" id="remarks" rows="2"
                  class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks', $entry?->remarks) }}</textarea>
        @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Disaggregated rows --}}
<div class="mb-4 js-dimensions-section">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0">Disaggregated Rows</label>
        <button type="button" id="add-row-btn" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-plus"></i> Add Row</button>
    </div>
    <table class="table table-sm mb-0" id="rows-table">
        <thead>
            <tr><th>Label</th><th>Value</th><th class="text-end">Remove</th></tr>
        </thead>
        <tbody id="rows-tbody"></tbody>
    </table>
    <p class="text-muted small mb-0" id="rows-empty-hint">No disaggregated rows — the entry's single Actual Value will be used.</p>
</div>

{{-- Expenses --}}
<div class="mb-4 js-budget-field">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0">Expenses</label>
        <button type="button" id="add-expense-btn" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-plus"></i> Add Expense</button>
    </div>
    <table class="table table-sm mb-0" id="expenses-table">
        <thead>
            <tr><th>Category</th><th>Description</th><th>Amount</th><th class="text-end">Remove</th></tr>
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
    <label class="form-label">Evidence <span class="text-danger d-none js-evidence-required-mark">*</span></label>

    @if ($existingEvidence->isNotEmpty())
    <table class="table table-sm mb-2">
        <thead>
            <tr><th>File</th><th>Size</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
            @foreach ($existingEvidence as $media)
            <tr>
                <td>{{ $media->file_name }}</td>
                <td>{{ number_format($media->size / 1024, 1) }} KB</td>
                <td class="text-end">
                    <a href="{{ route('indicator-data-entries.evidence.download', [$entry, $media]) }}" class="btn-icon" title="Download"><i class="mdi mdi-download-outline"></i></a>
                    @if ($canEditEvidence)
                    <form action="{{ route('indicator-data-entries.evidence.destroy', [$entry, $media]) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Remove this evidence file?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="Remove"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
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
    var assignmentScopes = @json($assignmentScopes);
    var dimensionConfig = @json($dimensionConfig ?? []);
    var rowIndex = 0;
    var expenseIndex = 0;

    function applyIndicatorConfig() {
        var select = document.getElementById('indicator_id');
        var option = select.options[select.selectedIndex];
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
        toggleFields('.js-activity-field', requiresActivity);
        toggleFields('.js-budget-field', hasBudgetImplication);
        toggleFields('.js-evidence-section', requiresEvidence);
        toggleFields('.js-dimensions-section', hasDimensions);
        toggleFields('.js-organization-field', collectionScope === 'institutional' || collectionScope === 'mixed' || Boolean(assignmentScope && assignmentScope.organization_id));
        toggleFields('.js-reporting-period-field', ['quarterly', 'biannual', 'annual'].indexOf(reportingFrequency) !== -1);

        document.querySelectorAll('.js-location-required-mark').forEach(function (el) { el.classList.toggle('d-none', ! requiresLocation); });
        document.querySelectorAll('.js-activity-required-mark').forEach(function (el) { el.classList.toggle('d-none', ! requiresActivity); });
        document.querySelectorAll('.js-budget-required-mark').forEach(function (el) { el.classList.toggle('d-none', ! hasBudgetImplication); });
        document.querySelectorAll('.js-evidence-required-mark').forEach(function (el) { el.classList.toggle('d-none', ! requiresEvidence); });

        document.getElementById('location_level').required = requiresLocation;
        document.getElementById('activity_name').required = requiresActivity;
        document.getElementById('budget_allocated').required = hasBudgetImplication;

        var locationLevel = document.getElementById('location_level');
        if (requiresLocation && reportingLocationLevel && locationLevel.value !== reportingLocationLevel) {
            locationLevel.value = reportingLocationLevel;
            locationLevel.dispatchEvent(new Event('change'));
        }

        var organization = document.getElementById('organization_id');
        if (assignmentScope && assignmentScope.organization_id) {
            organization.value = String(assignmentScope.organization_id);
            organization.dispatchEvent(new Event('change'));
            organization.setAttribute('data-assignment-locked', '1');
        } else {
            organization.removeAttribute('data-assignment-locked');
        }

        Array.from(locationLevel.options).forEach(function (candidate) {
            candidate.disabled = Boolean(reportingLocationLevel) && candidate.value !== reportingLocationLevel;
        });
        Array.from(organization.options).forEach(function (candidate) {
            candidate.disabled = Boolean(assignmentScope && assignmentScope.organization_id)
                && candidate.value !== String(assignmentScope.organization_id);
        });
        if (assignmentScope && assignmentScope.location_id) {
            var cascade = document.querySelector('.location-cascade');
            if (cascade) {
                cascade.dispatchEvent(new CustomEvent('location-cascade:set', {
                    detail: { ancestorChain: assignmentScope.location_chain || {} }
                }));
            }
        }

        configurePeriodChoices(reportingFrequency);
        configureActualValue(measurementType, unitName, unitSymbol);

        var notes = [];
        if (requiresLocation) { notes.push('reporting location'); }
        if (requiresActivity) { notes.push('activity name'); }
        if (hasBudgetImplication) { notes.push('budget allocated'); }
        if (requiresEvidence) { notes.push('supporting evidence'); }

        var behavior = collectionMode ? ('Collection: ' + collectionMode + '; aggregation: ' + aggregationMethod + '; frequency: ' + reportingFrequency + '.') : '';
        hint.textContent = (notes.length ? ('Required: ' + notes.join(', ') + '. ') : '') + behavior;
    }

    function configurePeriodChoices(reportingFrequency) {
        var periodSelect = document.getElementById('reporting_period_id');
        var financialYearId = document.getElementById('financial_year_id').value;
        var requiredType = { quarterly: 'quarter', biannual: 'semi_annual', annual: 'annual' }[reportingFrequency] || null;
        var firstAllowed = null;

        Array.from(periodSelect.options).forEach(function (period) {
            if (! period.value) { return; }
            var allowed = Boolean(requiredType)
                && period.dataset.financialYearId === financialYearId
                && period.dataset.periodType === requiredType;
            period.hidden = ! allowed;
            period.disabled = ! allowed;
            if (allowed && ! firstAllowed) { firstAllowed = period; }
        });
        var selected = periodSelect.options[periodSelect.selectedIndex];
        if (selected && selected.disabled) { periodSelect.value = firstAllowed ? firstAllowed.value : ''; }
        periodSelect.required = Boolean(requiredType);

        var yearOption = document.getElementById('financial_year_id').selectedOptions[0];
        var entryDate = document.getElementById('entry_date');
        var today = '{{ now()->toDateString() }}';
        if (yearOption && yearOption.value) {
            entryDate.min = yearOption.dataset.startDate || '';
            entryDate.max = yearOption.dataset.endDate && yearOption.dataset.endDate < today ? yearOption.dataset.endDate : today;
        }
    }

    function configureActualValue(measurementType, unitName, unitSymbol) {
        var input = document.getElementById('actual_value');
        var yesNo = document.getElementById('actual_value_yes_no');
        var label = document.getElementById('actual-value-label');
        label.textContent = 'Actual Value' + (unitName ? ' (' + unitName + (unitSymbol ? ' - ' + unitSymbol : '') + ')' : '');
        input.classList.toggle('d-none', measurementType === 'yes_no');
        yesNo.classList.toggle('d-none', measurementType !== 'yes_no');
        input.step = measurementType === 'count' ? '1' : (measurementType === 'currency' ? '0.01' : '0.0001');
        input.min = ['count', 'percentage', 'currency'].indexOf(measurementType) !== -1 ? '0' : '';
        input.max = measurementType === 'percentage' ? '100' : '';
        if (measurementType === 'yes_no') { yesNo.value = input.value; }
    }

    document.getElementById('indicator_id').addEventListener('change', function () {
        applyIndicatorConfig();
        renderConfiguredRows(true);
    });
    var indicatorSelect = document.getElementById('indicator_id');
    if (! indicatorSelect.value && indicatorSelect.options.length === 2) {
        indicatorSelect.selectedIndex = 1;
        indicatorSelect.dispatchEvent(new Event('change'));
    }
    applyIndicatorConfig();
    document.getElementById('financial_year_id').addEventListener('change', applyIndicatorConfig);
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
            '<td class="text-end"><button type="button" class="btn-icon danger btn-remove-row" title="Remove"><i class="mdi mdi-trash-can-outline"></i></button></td>';
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
            '<td class="text-end"><button type="button" class="btn-icon danger btn-remove-expense" title="Remove"><i class="mdi mdi-trash-can-outline"></i></button></td>';
        document.getElementById('expenses-tbody').appendChild(tr);
        toggleHint('expenses-tbody', 'expenses-empty-hint');
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

    initialRows.forEach(addRow);
    initialExpenses.forEach(addExpense);
    renderConfiguredRows(false);
    toggleHint('rows-tbody', 'rows-empty-hint');
    toggleHint('expenses-tbody', 'expenses-empty-hint');
})();
</script>
@endpush
