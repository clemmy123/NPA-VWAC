@php
    $intervention = $intervention ?? null;
    $selectedIndicatorId = (int) (old('indicator_ids.0') ?? $intervention?->indicators->first()?->id);
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="thematic_area_id" class="form-label">{{ __('Thematic Area') }}</label>
        <select name="thematic_area_id" id="thematic_area_id" class="form-control select2 @error('thematic_area_id') is-invalid @enderror" required>
            <option value="">{{ __('Select a thematic area…') }}</option>
            @foreach ($thematicAreas as $thematicArea)
            <option value="{{ $thematicArea->id }}" @selected((int) old('thematic_area_id', $intervention?->thematic_area_id) === $thematicArea->id)>{{ $thematicArea->localized_name }}</option>
            @endforeach
        </select>
        @error('thematic_area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">{{ __('Status') }}</label>
        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
            @foreach ($statusOptions as $option)
            <option value="{{ $option }}" @selected(old('status', $intervention?->status ?? 'draft') === $option)>{{ __(ucfirst($option)) }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="name" class="form-label">{{ __('Name') }}</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $intervention?->name) }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="description" class="form-label">{{ __('Description') }}</label>
        <textarea name="description" id="description" rows="4"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $intervention?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="indicator_ids" class="form-label">{{ __('Indicator') }}</label>
        <select name="indicator_ids[]" id="indicator_ids" class="form-control select2 @error('indicator_ids') is-invalid @enderror">
            <option value="">{{ __('Select an indicator…') }}</option>
            @foreach ($indicators as $indicator)
            <option value="{{ $indicator->id }}" @selected($selectedIndicatorId === $indicator->id)>{{ $indicator->name }}</option>
            @endforeach
        </select>
        @error('indicator_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
