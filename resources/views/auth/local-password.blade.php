@extends('components.main-layout')
@section('title', 'Change Password')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Change Password</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Change Password</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card" style="max-width: 480px;">
    <form action="{{ route('local-password.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
        </div>

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Update Password</button>
        </div>
    </form>
</div>
@endsection
