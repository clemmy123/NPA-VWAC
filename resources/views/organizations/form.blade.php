@php($organization = $organization ?? null)

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $organization?->name) }}" required maxlength="255" placeholder="e.g. CRDB Bank">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="organization_type_id" class="form-label">Type</label>
        <select name="organization_type_id" id="organization_type_id" class="form-control @error('organization_type_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($organizationTypes as $type)
            <option value="{{ $type->id }}" @selected((int) old('organization_type_id', $organization?->organization_type_id) === $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
        @error('organization_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="code" class="form-label">Code</label>
        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $organization?->code) }}" maxlength="50">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" id="description" rows="2"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $organization?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   @checked(old('is_active', $organization?->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active</label>
        </div>
    </div>
</div>
