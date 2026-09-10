@php($reportingPeriod = $reportingPeriod ?? null)

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="financial_year_id" class="form-label">Financial Year</label>
        <select name="financial_year_id" id="financial_year_id" class="form-control @error('financial_year_id') is-invalid @enderror" required>
            <option value="">Select a financial year…</option>
            @foreach ($financialYears as $financialYear)
            <option value="{{ $financialYear->id }}" @selected((int) old('financial_year_id', $reportingPeriod?->financial_year_id) === $financialYear->id)>{{ $financialYear->name }}</option>
            @endforeach
        </select>
        @error('financial_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="period_type" class="form-label">Period Type</label>
        <select name="period_type" id="period_type" class="form-control @error('period_type') is-invalid @enderror" required>
            @foreach ($periodTypes as $type)
            <option value="{{ $type }}" @selected(old('period_type', $reportingPeriod?->period_type ?? 'quarter') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>
        @error('period_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="code" class="form-label">Code</label>
        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $reportingPeriod?->code) }}" required maxlength="30" placeholder="e.g. Q1">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $reportingPeriod?->name) }}" required maxlength="100" placeholder="e.g. Quarter 1">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="sequence" class="form-label">Sequence</label>
        <input type="number" name="sequence" id="sequence" class="form-control @error('sequence') is-invalid @enderror"
               value="{{ old('sequence', $reportingPeriod?->sequence ?? 1) }}" required min="1">
        @error('sequence')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="start_date" class="form-label">Start Date</label>
        <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror"
               value="{{ old('start_date', $reportingPeriod?->start_date?->toDateString()) }}" required>
        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="end_date" class="form-label">End Date</label>
        <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror"
               value="{{ old('end_date', $reportingPeriod?->end_date?->toDateString()) }}" required>
        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   @checked(old('is_active', $reportingPeriod?->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active</label>
        </div>
    </div>
</div>
