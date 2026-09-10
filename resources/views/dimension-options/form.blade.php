@php($dimensionOption = $dimensionOption ?? null)

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="code" class="form-label">Code</label>
        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $dimensionOption?->code) }}" required maxlength="50">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $dimensionOption?->name) }}" required maxlength="255" placeholder="e.g. Female">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="sort_order" class="form-label">Sort Order</label>
        <input type="number" name="sort_order" id="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $dimensionOption?->sort_order ?? 0) }}" min="0">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   @checked(old('is_active', $dimensionOption?->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active</label>
        </div>
    </div>
</div>
