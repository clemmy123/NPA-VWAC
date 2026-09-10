@extends('components.main-layout')
@section('title', 'Data Sources')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Data Sources</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item active">Data Sources</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('data-sources.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> New Data Source</a>
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>Code</th><th>Name</th><th>Method</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($dataSources as $dataSource)
            <tr>
                <td>{{ ($dataSources->firstItem() ?? 1) + $loop->index }}</td>
                <td><span class="ref-pill">{{ $dataSource->code }}</span></td>
                <td>{{ $dataSource->name }}</td>
                <td>{{ ucfirst($dataSource->collection_method) }}</td>
                <td><span class="s-badge {{ $dataSource->is_active ? 's-active' : 's-inactive' }}">{{ $dataSource->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <a href="{{ route('data-sources.edit', $dataSource) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('data-sources.destroy', $dataSource) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this data source?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="tbl-empty">
                        <i class="mdi mdi-database-outline"></i>
                        <p>No data sources found.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($dataSources->hasPages())
    <div class="px-3 py-2 border-top">{{ $dataSources->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($dataSources as $dataSource)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $dataSource->name }}</span>
            <span class="s-badge {{ $dataSource->is_active ? 's-active' : 's-inactive' }}">{{ $dataSource->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-pound"></i> {{ $dataSource->code }}</span>
            <span><i class="mdi mdi-sync"></i> {{ ucfirst($dataSource->collection_method) }}</span>
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('data-sources.edit', $dataSource) }}" class="btn-icon" title="Edit"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('data-sources.destroy', $dataSource) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this data source?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="Delete"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-database-outline"></i>
        <p>No data sources found.</p>
    </div>
    @endforelse
    @if ($dataSources->hasPages())
    <div class="mt-3">{{ $dataSources->links() }}</div>
    @endif
</div>
@endsection
