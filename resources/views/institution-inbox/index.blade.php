@extends('components.main-layout')
@section('title', __('Institution Ownership Inbox'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Institution Ownership Inbox') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Institution Ownership Inbox') }}</li>
            </ol>
        </nav>
    </div>
</div>

<p class="text-muted mb-3">{{ __('Confirm your organization’s plans and submissions before thematic review.') }}</p>

@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="ib-section">
    <div class="ib-section-head">
        <h5><i class="mdi mdi-file-document-edit-outline"></i> {{ __('Annual plans awaiting confirmation') }}</h5>
        <span class="s-badge {{ $plans->isEmpty() ? 's-default' : 's-investigation' }}">{{ $plans->count() }}</span>
    </div>

    @forelse ($plans as $plan)
    <div class="chart-card ib-card">
        <div class="ib-card-main">
            <div class="ib-meta">{{ $plan->indicator?->thematicArea?->localized_name }} · {{ $plan->financialYear?->name }}</div>
            <div class="ib-title">{{ $plan->indicator?->name }}</div>
            <div class="ib-facts">
                <span><i class="mdi mdi-target"></i> {{ rtrim(rtrim(number_format((float) $plan->annual_target, 4), '0'), '.') }} {{ $plan->indicator?->unitOfMeasure?->name }}</span>
                <span><i class="mdi mdi-cash"></i> TZS {{ number_format((float) $plan->domestic_budget + (float) $plan->external_budget) }}</span>
                <span><i class="mdi mdi-account-outline"></i> {{ $plan->creator?->name ?? '—' }}</span>
                @if ($plan->submitted_at)<span><i class="mdi mdi-clock-outline"></i> {{ $plan->submitted_at->diffForHumans() }}</span>@endif
            </div>
            @if ($plan->indicator?->interventions->isNotEmpty())
            <div class="ib-chips">@foreach ($plan->indicator->interventions as $intervention)<span>{{ $intervention->name }}</span>@endforeach</div>
            @endif
            @foreach ($plan->getMedia('supporting-documents') as $doc)
            <a class="ib-doc" href="{{ route('indicator-plans.documents.download', [$plan, $doc]) }}"><i class="mdi mdi-paperclip"></i> {{ $doc->file_name }}</a>
            @endforeach
        </div>
        <div class="ib-actions">
            <form method="POST" action="{{ route('indicator-plans.action', $plan) }}">
                @csrf<input type="hidden" name="action" value="confirm">
                <button class="btn btn-success btn-sm"><i class="mdi mdi-check"></i> {{ __('Confirm ownership') }}</button>
            </form>
            <details class="ib-return">
                <summary class="btn btn-outline-danger btn-sm"><i class="mdi mdi-undo-variant"></i> {{ __('Return') }}</summary>
                <form method="POST" action="{{ route('indicator-plans.action', $plan) }}">
                    @csrf<input type="hidden" name="action" value="return">
                    <input type="text" required maxlength="2000" name="comment" class="form-control form-control-sm" placeholder="{{ __('Reason for return') }}">
                    <button class="btn btn-danger btn-sm">{{ __('Send back') }}</button>
                </form>
            </details>
        </div>
    </div>
    @empty
    <div class="chart-card tbl-empty"><i class="mdi mdi-check-circle-outline"></i><p>{{ __('No outstanding plans.') }}</p></div>
    @endforelse
</div>

<div class="ib-section">
    <div class="ib-section-head">
        <h5><i class="mdi mdi-clipboard-check-outline"></i> {{ __('Data submissions awaiting confirmation') }}</h5>
        <span class="s-badge {{ $entries->isEmpty() ? 's-default' : 's-investigation' }}">{{ $entries->count() }}</span>
    </div>

    @forelse ($entries as $entry)
    <div class="chart-card ib-card">
        <div class="ib-card-main">
            <div class="ib-meta">{{ $entry->indicator?->thematicArea?->localized_name }} · {{ $entry->financialYear?->name }}@if ($entry->reportingPeriod) · {{ $entry->reportingPeriod->name }}@endif</div>
            <div class="ib-title">{{ $entry->indicator?->name }}</div>
            <div class="ib-facts">
                <span><i class="mdi mdi-chart-line"></i> {{ $entry->actual_value !== null ? rtrim(rtrim(number_format((float) $entry->actual_value, 4), '0'), '.') : ($entry->actual_text ?? '—') }} {{ $entry->indicator?->unitOfMeasure?->name }}</span>
                <span><i class="mdi mdi-account-outline"></i> {{ $entry->enteredBy?->name ?? '—' }}</span>
                @if ($entry->submitted_at)<span><i class="mdi mdi-clock-outline"></i> {{ $entry->submitted_at->diffForHumans() }}</span>@endif
            </div>
        </div>
        <div class="ib-actions">
            <form method="POST" action="{{ route('indicator-data-entries.institution-confirm', $entry) }}">
                @csrf
                <button class="btn btn-success btn-sm"><i class="mdi mdi-check"></i> {{ __('Confirm ownership') }}</button>
            </form>
            <details class="ib-return">
                <summary class="btn btn-outline-danger btn-sm"><i class="mdi mdi-undo-variant"></i> {{ __('Return') }}</summary>
                <form method="POST" action="{{ route('indicator-data-entries.institution-return', $entry) }}">
                    @csrf
                    <input type="text" required minlength="5" maxlength="2000" name="comment" class="form-control form-control-sm" placeholder="{{ __('Reason for return') }}">
                    <button class="btn btn-danger btn-sm">{{ __('Send back') }}</button>
                </form>
            </details>
            <a href="{{ route('indicator-data-entries.show', $entry) }}" class="btn btn-outline-dark btn-sm">{{ __('Review details') }}</a>
        </div>
    </div>
    @empty
    <div class="chart-card tbl-empty"><i class="mdi mdi-check-circle-outline"></i><p>{{ __('No outstanding submissions.') }}</p></div>
    @endforelse
</div>
@endsection
