@php
    $baseline = $baseline ?? null;
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="indicator_id" class="form-label">Indicator</label>
        <select name="indicator_id" id="indicator_id" class="form-control select2 @error('indicator_id') is-invalid @enderror" required>
            <option value="">Select an indicator…</option>
            @foreach ($indicators as $indicator)
            <option value="{{ $indicator->id }}" @selected((int) old('indicator_id', $baseline?->indicator_id) === $indicator->id)>{{ $indicator->name }}</option>
            @endforeach
        </select>
        @error('indicator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="financial_year_id" class="form-label">Financial Year</label>
        <select name="financial_year_id" id="financial_year_id" class="form-control @error('financial_year_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($financialYears as $financialYear)
            <option value="{{ $financialYear->id }}" @selected((int) old('financial_year_id', $baseline?->financial_year_id) === $financialYear->id)>{{ $financialYear->name }}</option>
            @endforeach
        </select>
        @error('financial_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="baseline_value" class="form-label">Baseline Value</label>
        <input type="number" step="0.0001" name="baseline_value" id="baseline_value" class="form-control @error('baseline_value') is-invalid @enderror"
               value="{{ old('baseline_value', $baseline?->baseline_value) }}" required>
        @error('baseline_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="baseline_date" class="form-label">Baseline Date</label>
        <input type="date" name="baseline_date" id="baseline_date" class="form-control @error('baseline_date') is-invalid @enderror"
               value="{{ old('baseline_date', $baseline?->baseline_date?->toDateString()) }}">
        @error('baseline_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="source" class="form-label">Source</label>
        <input type="text" name="source" id="source" class="form-control @error('source') is-invalid @enderror"
               value="{{ old('source', $baseline?->source) }}" maxlength="255">
        @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="remarks" class="form-label">Remarks</label>
        <textarea name="remarks" id="remarks" rows="3"
                  class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks', $baseline?->remarks) }}</textarea>
        @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
