@php($assignment = $assignment ?? null)

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="indicator_id" class="form-label">Indicator</label>
        <select name="indicator_id" id="indicator_id" class="form-control @error('indicator_id') is-invalid @enderror" required>
            <option value="">Select an indicator…</option>
            @foreach ($indicators as $indicator)
            <option value="{{ $indicator->id }}" @selected((int) old('indicator_id', $assignment?->indicator_id) === $indicator->id)>{{ $indicator->name }}</option>
            @endforeach
        </select>
        @error('indicator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="user_id" class="form-label">User</label>
        <select name="user_id" id="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
            <option value="">Select a user…</option>
            @foreach ($users as $availableUser)
            <option value="{{ $availableUser->id }}" @selected((int) old('user_id', $assignment?->user_id) === $availableUser->id)>{{ $availableUser->name }} ({{ $availableUser->email }})</option>
            @endforeach
        </select>
        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="organization_id" class="form-label">Organization</label>
        <select name="organization_id" id="organization_id" class="form-control @error('organization_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($organizations as $organization)
            <option value="{{ $organization->id }}" @selected((int) old('organization_id', $assignment?->organization_id) === $organization->id)>{{ $organization->name }}</option>
            @endforeach
        </select>
        @error('organization_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="data_source_id" class="form-label">Data Source</label>
        <select name="data_source_id" id="data_source_id" class="form-control @error('data_source_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($dataSources as $dataSource)
            <option value="{{ $dataSource->id }}" @selected((int) old('data_source_id', $assignment?->data_source_id) === $dataSource->id)>{{ $dataSource->name }}</option>
            @endforeach
        </select>
        @error('data_source_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="location_level" class="form-label">Location Level</label>
        <select name="location_level" id="location_level" class="form-control @error('location_level') is-invalid @enderror">
            <option value="">— (all locations)</option>
            @foreach ($locationLevels as $level)
            <option value="{{ $level }}" @selected(old('location_level', $assignment?->location_level) === $level)>{{ ucfirst(str_replace('_', ' ', $level)) }}</option>
            @endforeach
        </select>
        @error('location_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="location_id" class="form-label">Location ID</label>
        <input type="number" name="location_id" id="location_id" class="form-control @error('location_id') is-invalid @enderror"
               value="{{ old('location_id', $assignment?->location_id) }}">
        @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Leave Location Level and Location ID both blank for an unrestricted (all-locations) assignment.</small>
    </div>

    <div class="col-12 mb-3">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   @checked(old('is_active', $assignment?->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active</label>
        </div>
    </div>
</div>
