@extends('components.main-layout')
@section('title', 'Data Collections')

@php
    $statusBadge = [
        'draft' => 's-default',
        'submitted' => 's-investigation',
        'approved' => 's-active',
        'rejected' => 's-inactive',
    ];
@endphp

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Data Collections</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Data Collections</li>
            </ol>
        </nav>
    </div>
    @can('indicator-data.create')
    <a href="{{ route('indicator-data-entries.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Collection</a>
    @endcan
</div>

{{-- Desktop Table --}}
<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Indicator</th>
                <th>Financial Year</th>
                <th>Entry Date</th>
                <th>Actual Value</th>
                <th>Entered By</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
            @php($canEdit = in_array($entry->status, ['draft', 'rejected'], true))
            <tr>
                <td>{{ ($entries->firstItem() ?? 1) + $loop->index }}</td>
                <td>{{ $entry->indicator?->name ?? '—' }}</td>
                <td>{{ $entry->financialYear?->name ?? '—' }}</td>
                <td>{{ $entry->entry_date?->format('d M Y') ?? '—' }}</td>
                <td>{{ $entry->actual_value !== null ? rtrim(rtrim(number_format((float) $entry->actual_value, 4), '0'), '.') : '—' }}</td>
                <td>{{ $entry->enteredBy?->name ?? '—' }}</td>
                <td><span class="s-badge {{ $statusBadge[$entry->status] ?? 's-default' }}">{{ ucfirst($entry->status) }}</span></td>
                <td>
                    @can('indicator-data.update')
                    @if ($canEdit)
                    <a href="{{ route('indicator-data-entries.edit', $entry) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    @endif
                    @endcan
                    @can('indicator-data.submit')
                    @if ($canEdit)
                    <form action="{{ route('indicator-data-entries.submit', $entry) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Submit this collection for review?');">
                        @csrf
                        <button type="submit" class="btn-icon" title="Submit"><i class="mdi mdi-send-outline"></i></button>
                    </form>
                    @endif
                    @endcan
                    @if ($entry->status === 'submitted')
                    @can('indicator-data.approve')
                    <form action="{{ route('indicator-data-entries.approve', $entry) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Approve this collection?');">
                        @csrf
                        <button type="submit" class="btn-icon" title="Approve"><i class="mdi mdi-check-circle-outline"></i></button>
                    </form>
                    @endcan
                    @can('indicator-data.return')
                    <form action="{{ route('indicator-data-entries.return', $entry) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Return this collection to the submitter?');">
                        @csrf
                        <button type="submit" class="btn-icon danger" title="Return"><i class="mdi mdi-undo"></i></button>
                    </form>
                    @endcan
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8">
                    <div class="tbl-empty">
                        <i class="mdi mdi-clipboard-text-outline"></i>
                        <p>No data collections found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($entries->hasPages())
    <div class="px-3 py-2 border-top">
        {{ $entries->links() }}
    </div>
    @endif
</div>

{{-- Mobile Cards --}}
<div class="d-md-none">
    @forelse ($entries as $entry)
    @php($canEdit = in_array($entry->status, ['draft', 'rejected'], true))
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $entry->indicator?->name ?? '—' }}</span>
            <span class="s-badge {{ $statusBadge[$entry->status] ?? 's-default' }}">{{ ucfirst($entry->status) }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-calendar-outline"></i> {{ $entry->entry_date?->format('d M Y') ?? '—' }}</span>
            <span><i class="mdi mdi-account-outline"></i> {{ $entry->enteredBy?->name ?? '—' }}</span>
        </div>
        <div class="mob-card-footer">
            @can('indicator-data.update')
            @if ($canEdit)
            <a href="{{ route('indicator-data-entries.edit', $entry) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            @endif
            @endcan
            @can('indicator-data.submit')
            @if ($canEdit)
            <form action="{{ route('indicator-data-entries.submit', $entry) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Submit this collection for review?');">
                @csrf
                <button type="submit" class="btn-icon" title="Submit"><i class="mdi mdi-send-outline"></i></button>
            </form>
            @endif
            @endcan
            @if ($entry->status === 'submitted')
            @can('indicator-data.approve')
            <form action="{{ route('indicator-data-entries.approve', $entry) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Approve this collection?');">
                @csrf
                <button type="submit" class="btn-icon" title="Approve"><i class="mdi mdi-check-circle-outline"></i></button>
            </form>
            @endcan
            @can('indicator-data.return')
            <form action="{{ route('indicator-data-entries.return', $entry) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Return this collection to the submitter?');">
                @csrf
                <button type="submit" class="btn-icon danger" title="Return"><i class="mdi mdi-undo"></i></button>
            </form>
            @endcan
            @endif
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-clipboard-text-outline"></i>
        <p>No data collections found.</p>
    </div>
    @endforelse

    @if ($entries->hasPages())
    <div class="mt-3">
        {{ $entries->links() }}
    </div>
    @endif
</div>
@endsection
