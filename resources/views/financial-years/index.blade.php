@extends('components.main-layout')
@section('title', 'Financial Years')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Financial Years</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item active">Financial Years</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('financial-years.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Financial Year</a>
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>Name</th><th>Start</th><th>End</th><th>Current</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($financialYears as $financialYear)
            <tr>
                <td>{{ ($financialYears->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $financialYear->name }}</td>
                <td>{{ $financialYear->start_date->format('d M Y') }}</td>
                <td>{{ $financialYear->end_date->format('d M Y') }}</td>
                <td>@if ($financialYear->is_current)<span class="s-badge s-active">Current</span>@else —@endif</td>
                <td><span class="s-badge {{ $financialYear->is_active ? 's-active' : 's-inactive' }}">{{ $financialYear->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <a href="{{ route('financial-years.edit', $financialYear) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('financial-years.destroy', $financialYear) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this financial year?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="tbl-empty">
                        <i class="mdi mdi-calendar-range-outline"></i>
                        <p>No financial years found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($financialYears->hasPages())
    <div class="px-3 py-2 border-top">{{ $financialYears->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($financialYears as $financialYear)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $financialYear->name }}</span>
            <span class="s-badge {{ $financialYear->is_active ? 's-active' : 's-inactive' }}">{{ $financialYear->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-start-outline"></i> {{ $financialYear->start_date->format('d M Y') }}</span>
            <span><i class="mdi mdi-calendar-end-outline"></i> {{ $financialYear->end_date->format('d M Y') }}</span>
            @if ($financialYear->is_current)<span class="s-badge s-active">Current</span>@endif
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('financial-years.edit', $financialYear) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('financial-years.destroy', $financialYear) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this financial year?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-calendar-range-outline"></i>
        <p>No financial years found.</p>
    </div>
    @endforelse
    @if ($financialYears->hasPages())
    <div class="mt-3">{{ $financialYears->links() }}</div>
    @endif
</div>
@endsection
