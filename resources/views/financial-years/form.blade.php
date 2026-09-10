@php($financialYear = $financialYear ?? null)

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $financialYear?->name) }}" required maxlength="20" placeholder="e.g. 2026/27">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="start_date" class="form-label">Start Date</label>
        <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror"
               value="{{ old('start_date', $financialYear?->start_date?->toDateString()) }}" required>
        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="end_date" class="form-label">End Date</label>
        <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror"
               value="{{ old('end_date', $financialYear?->end_date?->toDateString()) }}" required>
        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3 check-row">
        <div class="form-check">
            <input type="hidden" name="is_current" value="0">
            <input type="checkbox" name="is_current" id="is_current" class="form-check-input" value="1"
                   @checked(old('is_current', $financialYear?->is_current ?? false))>
            <label for="is_current" class="form-check-label">Current financial year</label>
        </div>
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   @checked(old('is_active', $financialYear?->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active</label>
        </div>
    </div>
</div>
