@php($assignment = $assignment ?? null)

@if ($delegatedCollector ?? false)
<div class="alert alert-info">
    You may assign another data collector to the next administrative level inside your own assigned area. The indicator and parent area determine the locations available below.
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="delegation_parent_scope" class="form-label">My Parent Assignment</label>
        <select id="delegation_parent_scope" class="form-control"></select>
    </div>
</div>
@endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="indicator_id" class="form-label">Indicator</label>
        <select name="indicator_id" id="indicator_id" class="form-control select2 @error('indicator_id') is-invalid @enderror" required>
            <option value="">Select an indicator…</option>
            @foreach ($indicators as $indicator)
            <option value="{{ $indicator->id }}" @selected((int) old('indicator_id', $assignment?->indicator_id) === $indicator->id)>{{ $indicator->name }}</option>
            @endforeach
        </select>
        @error('indicator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="user_id" class="form-label">Data Entry User</label>
        <select name="user_id" id="user_id" class="form-control select2 @error('user_id') is-invalid @enderror" required>
            <option value="">Select a data entry user…</option>
            @foreach ($users as $availableUser)
            <option value="{{ $availableUser->id }}" @selected((int) old('user_id', $assignment?->user_id) === $availableUser->id)>{{ $availableUser->name }} ({{ $availableUser->email }})</option>
            @endforeach
        </select>
        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="organization_id" class="form-label">Organization</label>
        <select name="organization_id" id="organization_id" class="form-control select2 @error('organization_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($organizations as $organization)
            <option value="{{ $organization->id }}" @selected((int) old('organization_id', $assignment?->organization_id) === $organization->id)>{{ $organization->name }}</option>
            @endforeach
        </select>
        @error('organization_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
        <label class="form-label">Location</label>
        @include('components.location-cascade', [
            'currentId' => old('location_id', $assignment?->location_id),
            'ancestorChain' => $locationAncestorChain ?? [],
        ])
        @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <small class="text-muted">
            @if ($delegatedCollector ?? false)
                Select a location directly below your assigned parent area.
            @else
                Leave Location Level and Location both blank for an unrestricted (all-locations) assignment.
            @endif
        </small>
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

@if ($delegatedCollector ?? false)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var scopesByIndicator = @json($delegationScopes ?? []);
    var indicator = document.getElementById('indicator_id');
    var parentScope = document.getElementById('delegation_parent_scope');
    var locationLevel = document.getElementById('location_level');
    var organization = document.getElementById('organization_id');
    var cascade = document.querySelector('.location-cascade');

    function applyScope() {
        var scopes = scopesByIndicator[String(indicator.value)] || scopesByIndicator[indicator.value] || [];
        parentScope.innerHTML = scopes.map(function (scope, index) {
            return '<option value="' + index + '">' + (scope.label || scope.level) + '</option>';
        }).join('');

        var scope = scopes[Number(parentScope.value || 0)];
        Array.from(locationLevel.options).forEach(function (option) {
            option.disabled = ! scope || ! scope.child_levels.includes(option.value);
        });

        if (! scope) {
            locationLevel.value = '';
            return;
        }

        if (! scope.child_levels.includes(locationLevel.value)) {
            locationLevel.value = scope.child_levels[0] || '';
        }

        if (scope.organization_id) {
            organization.value = String(scope.organization_id);
            organization.disabled = true;
        } else {
            organization.disabled = false;
        }

        locationLevel.dispatchEvent(new Event('change'));
        cascade.dispatchEvent(new CustomEvent('location-cascade:set', { detail: {
            ancestorChain: scope.ancestor_chain,
            lockedThroughLevel: scope.level
        }}));
    }

    indicator.addEventListener('change', applyScope);
    parentScope.addEventListener('change', applyScope);
    applyScope();

    indicator.closest('form').addEventListener('submit', function () {
        organization.disabled = false;
    });
});
</script>
@endpush
@endif
