@php($dataSource = $dataSource ?? null)

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="code" class="form-label">Code</label>
        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $dataSource?->code) }}" required maxlength="50">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $dataSource?->name) }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="collection_method" class="form-label">Collection Method</label>
        <select name="collection_method" id="collection_method" class="form-control @error('collection_method') is-invalid @enderror" required>
            @foreach ($collectionMethods as $method)
            <option value="{{ $method }}" @selected(old('collection_method', $dataSource?->collection_method ?? 'manual') === $method)>{{ ucfirst($method) }}</option>
            @endforeach
        </select>
        @error('collection_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" id="description" rows="2"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $dataSource?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   @checked(old('is_active', $dataSource?->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active</label>
        </div>
    </div>
</div>
