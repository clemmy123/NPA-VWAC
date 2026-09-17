@extends(request()->boolean('embedded') ? 'components.modal-layout' : 'components.main-layout')
@section('title', 'Edit Data Collection')

@section('content')
@unless(request()->boolean('embedded'))
<div class="page-header">
    <div>
        <h4 class="page-title">Edit Data Collection</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicator-data-entries.index') }}">Data Collections</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
</div>
@endunless

<div class="chart-card">
    <form action="{{ route('indicator-data-entries.update', ['indicator_data_entry' => $entry, 'embedded' => request()->boolean('embedded') ? 1 : null]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('indicator-data-entries.form', [
            'entry' => $entry,
            'indicators' => $indicators,
            'financialYears' => $financialYears,
            'reportingPeriods' => $reportingPeriods,
            'organizations' => $organizations,
            'locationLevels' => $locationLevels,
        ])

        <div class="d-flex form-actions">
            <button type="submit" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> Save Changes</button>
            <a href="{{ route('indicator-data-entries.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

@if (request()->boolean('embedded') && in_array($entry->status, ['draft', 'rejected'], true))
    @can('indicator-data.submit')
    <form action="{{ route('indicator-data-entries.submit', ['indicator_data_entry' => $entry, 'embedded' => 1]) }}" method="POST" class="mt-3" onsubmit="return confirm('Submit this completed collection for review?');">
        @csrf
        <button type="submit" class="btn btn-success"><i class="mdi mdi-check-circle-outline"></i> Confirm and submit for review</button>
    </form>
    @endcan
@endif
@endsection

@if (request()->boolean('embedded') && ! in_array($entry->status, ['draft', 'rejected'], true))
@push('scripts')
<script>window.parent.postMessage({ type: 'collection-submitted' }, window.location.origin);</script>
@endpush
@endif
