@php
    $thematicArea = $thematicArea ?? null;
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="project_id" class="form-label">{{ __('Project') }}</label>
        <select name="project_id" id="project_id" class="form-control select2 @error('project_id') is-invalid @enderror" required>
            <option value="">{{ __('Select a project…') }}</option>
            @foreach ($projects as $project)
            <option value="{{ $project->id }}" @selected((int) old('project_id', $thematicArea?->project_id) === $project->id)>{{ $project->name }}</option>
            @endforeach
        </select>
        @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">{{ __('Status') }}</label>
        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
            @foreach ($statusOptions as $option)
            <option value="{{ $option }}" @selected(old('status', $thematicArea?->status ?? 'draft') === $option)>{{ __(ucfirst($option)) }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">{{ __('Name (English)') }}</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $thematicArea?->name) }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="name_sw" class="form-label">{{ __('Name (Swahili)') }}</label>
        <input type="text" name="name_sw" id="name_sw" class="form-control @error('name_sw') is-invalid @enderror"
               value="{{ old('name_sw', $thematicArea?->name_sw) }}" maxlength="255">
        <small class="text-muted">{{ __('Shown instead of the English name when the interface is in Swahili.') }}</small>
        @error('name_sw')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="description" class="form-label">{{ __('Description') }}</label>
        <textarea name="description" id="description" rows="4"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $thematicArea?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
