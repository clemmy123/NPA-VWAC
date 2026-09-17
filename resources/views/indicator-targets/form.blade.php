@php
    $target = $target ?? null;
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="indicator_id" class="form-label">Indicator</label>
        <select name="indicator_id" id="indicator_id" class="form-control select2 @error('indicator_id') is-invalid @enderror" required>
            <option value="">Select an indicator…</option>
            @foreach ($indicators as $indicator)
            <option value="{{ $indicator->id }}" @selected((int) old('indicator_id', $target?->indicator_id) === $indicator->id)>{{ $indicator->name }}</option>
            @endforeach
        </select>
        @error('indicator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="financial_year_id" class="form-label">Financial Year</label>
        <select name="financial_year_id" id="financial_year_id" class="form-control @error('financial_year_id') is-invalid @enderror" required>
            <option value="">Select a financial year…</option>
            @foreach ($financialYears as $financialYear)
            <option value="{{ $financialYear->id }}" @selected((int) old('financial_year_id', $target?->financial_year_id) === $financialYear->id)>{{ $financialYear->name }}</option>
            @endforeach
        </select>
        @error('financial_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="reporting_period_id" class="form-label">Reporting Period</label>
        <select name="reporting_period_id" id="reporting_period_id" class="form-control @error('reporting_period_id') is-invalid @enderror">
            <option value="">Whole year</option>
            @foreach ($reportingPeriods as $period)
            <option value="{{ $period->id }}" @selected((int) old('reporting_period_id', $target?->reporting_period_id) === $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
        @error('reporting_period_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="dimension_option_id" class="form-label">Dimension (optional)</label>
        <select name="dimension_option_id" id="dimension_option_id" class="form-control @error('dimension_option_id') is-invalid @enderror">
            <option value="">Aggregate (no disaggregation)</option>
            @foreach ($dimensionOptions as $option)
            <option value="{{ $option->id }}" @selected((int) old('dimension_option_id', $target?->dimension_option_id) === $option->id)>{{ $option->name }}</option>
            @endforeach
        </select>
        @error('dimension_option_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="target_value" class="form-label">Target Value</label>
        <input type="number" step="0.0001" name="target_value" id="target_value" class="form-control @error('target_value') is-invalid @enderror"
               value="{{ old('target_value', \App\Support\DisplayNumber::input($target?->target_value)) }}" required>
        @error('target_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="remarks" class="form-label">Remarks</label>
        <textarea name="remarks" id="remarks" rows="3"
                  class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks', $target?->remarks) }}</textarea>
        @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
