@php
    $indicator = $indicator ?? null;
    // These four map to NOT NULL columns with DB-level defaults (see the
    // indicators migration), so unlike the genuinely nullable fields below,
    // they must never submit a blank/null value.
    $collectionModes = ['progressive', 'manual', 'system', 'mixed'];
    $aggregationMethods = ['sum', 'average', 'latest', 'count', 'max', 'min'];
    $reportingFrequencies = ['monthly', 'quarterly', 'biannual', 'annual'];
    $collectionScopes = ['national', 'individual', 'aggregate'];
    $locationLevels = ['national', 'region', 'district', 'ward', 'village', 'facility'];
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="thematic_area_id" class="form-label">Thematic Area</label>
        <select name="thematic_area_id" id="thematic_area_id" class="form-control select2 @error('thematic_area_id') is-invalid @enderror" required>
            <option value="">Select a thematic area…</option>
            @foreach ($thematicAreas as $thematicArea)
            <option value="{{ $thematicArea->id }}" @selected((int) old('thematic_area_id', $indicator?->thematic_area_id) === $thematicArea->id)>{{ $thematicArea->name }}</option>
            @endforeach
        </select>
        @error('thematic_area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="code" class="form-label">Code</label>
        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $indicator?->code) }}" required maxlength="80">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $indicator?->name) }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" id="description" rows="3"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $indicator?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="measurement_type_id" class="form-label">Measurement Type</label>
        <select name="measurement_type_id" id="measurement_type_id" class="form-control @error('measurement_type_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($measurementTypes as $measurementType)
            <option value="{{ $measurementType->id }}" @selected((int) old('measurement_type_id', $indicator?->measurement_type_id) === $measurementType->id)>{{ $measurementType->name }}</option>
            @endforeach
        </select>
        @error('measurement_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="unit_of_measure_id" class="form-label">Unit of Measure</label>
        <select name="unit_of_measure_id" id="unit_of_measure_id" class="form-control @error('unit_of_measure_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($unitsOfMeasure as $unit)
            <option value="{{ $unit->id }}" @selected((int) old('unit_of_measure_id', $indicator?->unit_of_measure_id) === $unit->id)>{{ $unit->name }}</option>
            @endforeach
        </select>
        @error('unit_of_measure_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="collection_mode" class="form-label">Collection Mode</label>
        <select name="collection_mode" id="collection_mode" class="form-control">
            @foreach ($collectionModes as $option)
            <option value="{{ $option }}" @selected(old('collection_mode', $indicator?->collection_mode ?? 'progressive') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3 mb-3">
        <label for="aggregation_method" class="form-label">Aggregation Method</label>
        <select name="aggregation_method" id="aggregation_method" class="form-control">
            @foreach ($aggregationMethods as $option)
            <option value="{{ $option }}" @selected(old('aggregation_method', $indicator?->aggregation_method ?? 'sum') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3 mb-3">
        <label for="reporting_frequency" class="form-label">Reporting Frequency</label>
        <select name="reporting_frequency" id="reporting_frequency" class="form-control">
            @foreach ($reportingFrequencies as $option)
            <option value="{{ $option }}" @selected(old('reporting_frequency', $indicator?->reporting_frequency ?? 'quarterly') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3 mb-3">
        <label for="collection_scope" class="form-label">Collection Scope</label>
        <select name="collection_scope" id="collection_scope" class="form-control">
            @foreach ($collectionScopes as $option)
            <option value="{{ $option }}" @selected(old('collection_scope', $indicator?->collection_scope ?? 'national') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label for="reporting_location_level" class="form-label">Reporting Location Level</label>
        <select name="reporting_location_level" id="reporting_location_level" class="form-control">
            <option value="">—</option>
            @foreach ($locationLevels as $option)
            <option value="{{ $option }}" @selected(old('reporting_location_level', $indicator?->reporting_location_level) === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">Status</label>
        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
            @foreach ($statusOptions as $option)
            <option value="{{ $option }}" @selected(old('status', $indicator?->status ?? 'draft') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mb-3">
        <div class="check-row">
            <div class="form-check">
                <input type="hidden" name="requires_location" value="0">
                <input type="checkbox" name="requires_location" id="requires_location" value="1" class="form-check-input"
                       @checked(old('requires_location', $indicator?->requires_location))>
                <label for="requires_location" class="form-check-label">Requires Location</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="requires_activity" value="0">
                <input type="checkbox" name="requires_activity" id="requires_activity" value="1" class="form-check-input"
                       @checked(old('requires_activity', $indicator?->requires_activity))>
                <label for="requires_activity" class="form-check-label">Requires Activity</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="has_budget_implication" value="0">
                <input type="checkbox" name="has_budget_implication" id="has_budget_implication" value="1" class="form-check-input"
                       @checked(old('has_budget_implication', $indicator?->has_budget_implication))>
                <label for="has_budget_implication" class="form-check-label">Has Budget Implication</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="requires_evidence" value="0">
                <input type="checkbox" name="requires_evidence" id="requires_evidence" value="1" class="form-check-input"
                       @checked(old('requires_evidence', $indicator?->requires_evidence))>
                <label for="requires_evidence" class="form-check-label">Requires Evidence</label>
            </div>
        </div>
    </div>
</div>
