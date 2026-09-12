@php
    $user = $user ?? null;
    $currentRole = old('role', $user?->roles?->first()?->name);
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $user?->name) }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $user?->email) }}" required maxlength="255">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="phone_number" class="form-label">Phone Number</label>
        <input type="text" name="phone_number" id="phone_number" class="form-control @error('phone_number') is-invalid @enderror"
               value="{{ old('phone_number', $user?->phone_number) }}" maxlength="50">
        @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="gender" class="form-label">Gender</label>
        <select name="gender" id="gender" class="form-control @error('gender') is-invalid @enderror">
            <option value="">—</option>
            <option value="male" @selected(old('gender', $user?->gender) === 'male')>Male</option>
            <option value="female" @selected(old('gender', $user?->gender) === 'female')>Female</option>
        </select>
        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="organization_id" class="form-label">Organization</label>
        <select name="organization_id" id="organization_id" class="form-control select2 @error('organization_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($organizations as $organization)
            <option value="{{ $organization->id }}" @selected((int) old('organization_id', $user?->organization_id) === $organization->id)>{{ $organization->name }}</option>
            @endforeach
        </select>
        @error('organization_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Link this account to a reporting organization (e.g. a bank) so it can be scoped to specific indicators.</small>
    </div>

    <div class="col-md-6 mb-3">
        <label for="role" class="form-label">Role</label>
        <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required>
            <option value="">Select a role…</option>
            @foreach ($roles as $availableRole)
            <option value="{{ $availableRole->name }}" @selected($currentRole === $availableRole->name)>{{ $availableRole->name }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">Status</label>
        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
            <option value="active" @selected(old('status', $user?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $user?->status) === 'inactive')>Inactive</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="auth_provider" class="form-label">Sign-in Method</label>
        <select name="auth_provider" id="auth_provider" class="form-control @error('auth_provider') is-invalid @enderror" required>
            <option value="jumuishi" @selected(old('auth_provider', $user?->auth_provider ?? 'jumuishi') === 'jumuishi')>Jumuishi SSO (government staff)</option>
            <option value="local" @selected(old('auth_provider', $user?->auth_provider) === 'local')>Local email / password (reporting organizations)</option>
        </select>
        @error('auth_provider')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="password" class="form-label">
            Password @if ($user) <span class="text-muted">(leave blank to keep current)</span> @endif
        </label>
        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Only used when Sign-in Method is Local.</small>
    </div>

    <div class="col-md-6 mb-3">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password">
    </div>
</div>
