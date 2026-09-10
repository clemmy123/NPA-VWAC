@php
    $entry = $entry ?? null;
    $existingRows = old('rows', $entry?->rows?->map(fn ($row) => [
        'label' => $row->label,
        'value' => $row->value,
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
        <select name="indicator_id" id="indicator_id" class="form-control @error('indicator_id') is-invalid @enderror" required>
            <option value="">Select an indicator…</option>
            @foreach ($indicators as $indicator)
            <option value="{{ $indicator->id }}" @selected((int) old('indicator_id', $entry?->indicator_id) === $indicator->id)>{{ $indicator->name }}</option>
            @endforeach
        </select>
        @error('indicator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="financial_year_id" class="form-label">Financial Year</label>
        <select name="financial_year_id" id="financial_year_id" class="form-control @error('financial_year_id') is-invalid @enderror" required>
            <option value="">Select a financial year…</option>
            @foreach ($financialYears as $financialYear)
            <option value="{{ $financialYear->id }}" @selected((int) old('financial_year_id', $entry?->financial_year_id) === $financialYear->id)>{{ $financialYear->name }}</option>
            @endforeach
        </select>
        @error('financial_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="reporting_period_id" class="form-label">Reporting Period</label>
        <select name="reporting_period_id" id="reporting_period_id" class="form-control @error('reporting_period_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($reportingPeriods as $period)
            <option value="{{ $period->id }}" @selected((int) old('reporting_period_id', $entry?->reporting_period_id) === $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
        @error('reporting_period_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="entry_date" class="form-label">Entry Date</label>
        <input type="date" name="entry_date" id="entry_date" class="form-control @error('entry_date') is-invalid @enderror"
               value="{{ old('entry_date', $entry?->entry_date?->toDateString() ?? now()->toDateString()) }}" required>
        @error('entry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="activity_name" class="form-label">Activity Name</label>
        <input type="text" name="activity_name" id="activity_name" class="form-control @error('activity_name') is-invalid @enderror"
               value="{{ old('activity_name', $entry?->activity_name) }}" maxlength="255">
        @error('activity_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="organization_id" class="form-label">Organization</label>
        <select name="organization_id" id="organization_id" class="form-control @error('organization_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($organizations as $organization)
            <option value="{{ $organization->id }}" @selected((int) old('organization_id', $entry?->organization_id) === $organization->id)>{{ $organization->name }}</option>
            @endforeach
        </select>
        @error('organization_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="activity_description" class="form-label">Activity Description</label>
        <textarea name="activity_description" id="activity_description" rows="2"
                  class="form-control @error('activity_description') is-invalid @enderror">{{ old('activity_description', $entry?->activity_description) }}</textarea>
        @error('activity_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="location_level" class="form-label">Location Level</label>
        <select name="location_level" id="location_level" class="form-control @error('location_level') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($locationLevels as $level)
            <option value="{{ $level }}" @selected(old('location_level', $entry?->location_level) === $level)>{{ ucfirst(str_replace('_', ' ', $level)) }}</option>
            @endforeach
        </select>
        @error('location_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="location_id" class="form-label">Location ID</label>
        <input type="number" name="location_id" id="location_id" class="form-control @error('location_id') is-invalid @enderror"
               value="{{ old('location_id', $entry?->location_id) }}">
        @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="data_source_id" class="form-label">Data Source</label>
        <select name="data_source_id" id="data_source_id" class="form-control @error('data_source_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($dataSources as $dataSource)
            <option value="{{ $dataSource->id }}" @selected((int) old('data_source_id', $entry?->data_source_id) === $dataSource->id)>{{ $dataSource->name }}</option>
            @endforeach
        </select>
        @error('data_source_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="actual_value" class="form-label">Actual Value</label>
        <input type="number" step="0.0001" name="actual_value" id="actual_value" class="form-control @error('actual_value') is-invalid @enderror"
               value="{{ old('actual_value', $entry?->actual_value) }}">
        @error('actual_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="budget_allocated" class="form-label">Budget Allocated</label>
        <input type="number" step="0.01" name="budget_allocated" id="budget_allocated" class="form-control @error('budget_allocated') is-invalid @enderror"
               value="{{ old('budget_allocated', $entry?->budget_allocated) }}">
        @error('budget_allocated')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="budget_used" class="form-label">Budget Used</label>
        <input type="number" step="0.01" name="budget_used" id="budget_used" class="form-control @error('budget_used') is-invalid @enderror"
               value="{{ old('budget_used', $entry?->budget_used) }}">
        @error('budget_used')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
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
<div class="mb-4">
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
<div class="mb-4">
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
    <p class="text-muted small mb-0" id="expenses-empty-hint">No expenses recorded for this entry.</p>
</div>

@push('scripts')
<script>
(function () {
    var initialRows = @json($existingRows);
    var initialExpenses = @json($existingExpenses);
    var rowIndex = 0;
    var expenseIndex = 0;

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
    toggleHint('rows-tbody', 'rows-empty-hint');
    toggleHint('expenses-tbody', 'expenses-empty-hint');
})();
</script>
@endpush
