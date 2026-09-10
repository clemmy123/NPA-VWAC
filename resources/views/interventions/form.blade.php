@php
    $intervention = $intervention ?? null;
    $selectedIndicatorIds = old('indicator_ids', $intervention?->indicators->pluck('id')->all() ?? []);
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="thematic_area_id" class="form-label">Thematic Area</label>
        <select name="thematic_area_id" id="thematic_area_id" class="form-control @error('thematic_area_id') is-invalid @enderror" required>
            <option value="">Select a thematic area…</option>
            @foreach ($thematicAreas as $thematicArea)
            <option value="{{ $thematicArea->id }}" @selected((int) old('thematic_area_id', $intervention?->thematic_area_id) === $thematicArea->id)>{{ $thematicArea->name }}</option>
            @endforeach
        </select>
        @error('thematic_area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">Status</label>
        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
            @foreach ($statusOptions as $option)
            <option value="{{ $option }}" @selected(old('status', $intervention?->status ?? 'draft') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $intervention?->name) }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" id="description" rows="4"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $intervention?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="indicator_ids" class="form-label">Linked Indicators</label>
        <select name="indicator_ids[]" id="indicator_ids" class="form-control select2-multi @error('indicator_ids') is-invalid @enderror" multiple>
            @foreach ($indicators as $indicator)
            <option value="{{ $indicator->id }}" @selected(in_array($indicator->id, $selectedIndicatorIds))>{{ $indicator->name }}</option>
            @endforeach
        </select>
        @error('indicator_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        $('#indicator_ids').select2({ placeholder: 'Select indicators…', width: '100%' });
    });
</script>
@endpush
