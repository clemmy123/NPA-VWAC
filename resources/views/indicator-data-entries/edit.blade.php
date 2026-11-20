@extends(request()->boolean('embedded') ? 'components.modal-layout' : 'components.main-layout')
@section('title', __('Edit Data Collection'))

@section('content')
@unless(request()->boolean('embedded'))
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Edit Data Collection') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('indicator-data-entries.index') }}">{{ __('Data Collections') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Edit') }}</li>
            </ol>
        </nav>
    </div>
</div>
@endunless

@if($entry->plan_snapshot)
<div class="chart-card mb-3 p-3" aria-label="Approved implementation plan">
    <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">{{ __('Approved plan — read only') }}</h5><span class="badge bg-success">{{ __('Locked') }}</span></div>
    <dl class="row mb-0 small">
      <dt class="col-md-3">{{ __('Planned activity') }}</dt><dd class="col-md-9">{{ $entry->plan_snapshot['planned_activity'] ?? '—' }}</dd>
      <dt class="col-md-3">{{ __('Baseline year / value') }}</dt><dd class="col-md-9">{{ $entry->plan_snapshot['baseline_year'] ?? '—' }} / {{ $entry->plan_snapshot['baseline_value'] ?? '—' }}</dd>
      <dt class="col-md-3">{{ __('Annual target') }}</dt><dd class="col-md-9">{{ $entry->plan_snapshot['annual_target'] ?? '—' }}</dd>
      <dt class="col-md-3">{{ __('Domestic / External plan (TZS)') }}</dt><dd class="col-md-9">{{ number_format((float)($entry->plan_snapshot['domestic_budget'] ?? 0),2) }} / {{ number_format((float)($entry->plan_snapshot['external_budget'] ?? 0),2) }}</dd>
      <dt class="col-md-3">{{ __('Lead institution') }}</dt><dd class="col-md-9">{{ \App\Models\Organization::find($entry->plan_snapshot['lead_organization_id'] ?? null)?->name ?? '—' }}</dd>
    </dl>
</div>
@endif
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
            <button type="submit" name="intent" value="save" class="btn btn-dark"><i class="mdi mdi-content-save-outline"></i> {{ __('Save Changes') }}</button>
            @if (request()->boolean('embedded') && in_array($entry->status, ['draft', 'rejected'], true))
                @can('indicator-data.submit')
                <button type="submit" name="intent" value="submit" class="btn btn-success" data-confirm="{{ __('Submit this completed collection for review?') }}">
                    <i class="mdi mdi-check-circle-outline"></i> {{ __('Confirm and submit for review') }}
                </button>
                @endcan
            @endif
            <a href="{{ route('indicator-data-entries.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
    @if (in_array($entry->status, ['draft', 'rejected'], true))
        @foreach ($entry->getMedia('evidence') as $media)
        <form id="delete-evidence-{{ $media->id }}" action="{{ route('indicator-data-entries.evidence.destroy', [$entry, $media]) }}" method="POST" data-confirm="{{ __('Remove this evidence file?') }}">
            @csrf
            @method('DELETE')
        </form>
        @endforeach
    @endif
</div>
@endsection

@if (request()->boolean('embedded') && (request()->boolean('submitted') || ! in_array($entry->status, ['draft', 'rejected'], true)))
@push('scripts')
<script>
window.parent.postMessage({
    type: 'collection-submitted',
    message: @json(request()->boolean('submitted') ? __('Data collection submitted for review.') : null)
}, window.location.origin);
</script>
@endpush
@endif
