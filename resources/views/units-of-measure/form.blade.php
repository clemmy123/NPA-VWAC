@php($unitOfMeasure = $unitOfMeasure ?? null)

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="code" class="form-label">Code</label>
        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $unitOfMeasure?->code) }}" required maxlength="50">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $unitOfMeasure?->name) }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="symbol" class="form-label">Symbol</label>
        <input type="text" name="symbol" id="symbol" class="form-control @error('symbol') is-invalid @enderror"
               value="{{ old('symbol', $unitOfMeasure?->symbol) }}" maxlength="30">
        @error('symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   @checked(old('is_active', $unitOfMeasure?->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active</label>
        </div>
    </div>
</div>
