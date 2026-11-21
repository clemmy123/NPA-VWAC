@extends('components.main-layout')
@section('title', __('Plan review'))

@section('content')
@php
    $user = auth()->user();
    $statusTone = ['draft' => 's-default', 'returned' => 's-invalid', 'pending_institution' => 's-investigation', 'pending_thematic' => 's-received', 'approved' => 's-active'];
    $plans = $indicators->map(fn ($indicator) => $indicator->plans->first())->filter();
    $counts = $plans->countBy('status');
    $steps = ['submitted' => __('Submitted'), 'confirmed' => __('Institution confirmed'), 'approved' => __('Approved')];
    $isSuper = $user->hasRole('Super Admin');
    $canConfirmAny = $plans->contains(fn ($plan) => $plan->status === 'pending_institution'
        && ($isSuper || ($user->can('institution.confirm') && (int) $user->organization_id === (int) $plan->lead_organization_id && (int) $user->id !== (int) $plan->submitted_by)));
    $canApproveAny = $canManage && $plans->contains(fn ($plan) => $plan->status === 'pending_thematic' && ($isSuper || (int) $user->id !== (int) $plan->submitted_by));
    $canReturnAny = $plans->contains(fn ($plan) => in_array($plan->status, ['pending_institution', 'pending_thematic'], true)) && ($canConfirmAny || $canApproveAny);
    $fy = $financialYears->firstWhere('id', $financialYearId);
@endphp
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Plan review') }} · {{ $thematicArea->localized_name }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('plan-builder.index') }}">{{ __('Plan Builder') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('plan-builder.show', ['thematic_area' => $thematicArea, 'financial_year_id' => $financialYearId]) }}">{{ $thematicArea->localized_name }}</a></li>
                <li class="breadcrumb-item active">{{ __('Review') }}</li>
            </ol>
        </nav>
    </div>
    <form method="GET" class="pr-year pb-year-bar">
        <select name="financial_year_id" class="form-control pb-bar-select">
            @foreach ($financialYears as $year)
            <option value="{{ $year->id }}" @selected($financialYearId == $year->id)>{{ $year->name }}</option>
            @endforeach
        </select>
    </form>
</div>

@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="pr-summary">
    @foreach (['draft' => __('Draft'), 'pending_institution' => __('Awaiting institution'), 'pending_thematic' => __('Awaiting approval'), 'returned' => __('Returned'), 'approved' => __('Approved')] as $status => $label)
    <div class="pr-stat pr-stat-{{ $status }}">
        <div class="pr-stat-value">{{ $counts[$status] ?? 0 }}</div>
        <div class="pr-stat-label">{{ $label }}</div>
    </div>
    @endforeach
    <div class="pr-stat">
        <div class="pr-stat-value">{{ $indicators->count() - $plans->count() }}</div>
        <div class="pr-stat-label">{{ __('Not planned') }}</div>
    </div>
</div>

@if ($canConfirmAny || $canApproveAny || $canReturnAny)
<div class="chart-card pr-actionbar">
    <div class="pr-actionbar-text">
        <strong>{{ __('Review this plan') }}</strong>
        <span class="text-muted">{{ __('Actions apply to every indicator that is waiting on you for :year.', ['year' => $fy?->name]) }}</span>
    </div>
    <form method="POST" action="{{ route('indicator-plans.bulk-action', $thematicArea) }}" class="pr-actionbar-form">
        @csrf
        <input type="hidden" name="financial_year_id" value="{{ $financialYearId }}">
        <input type="text" name="comment" class="form-control form-control-sm pr-reason" maxlength="2000" placeholder="{{ __('Reason (required to return)') }}">
        @if ($canConfirmAny)<button name="action" value="confirm" class="btn btn-success btn-sm"><i class="mdi mdi-check"></i> {{ __('Confirm as institution') }}</button>@endif
        @if ($canApproveAny)<button name="action" value="approve" class="btn btn-primary btn-sm"><i class="mdi mdi-check-all"></i> {{ __('Approve for data collection') }}</button>@endif
        @if ($canReturnAny)<button name="action" value="return" class="btn btn-outline-danger btn-sm"><i class="mdi mdi-undo-variant"></i> {{ __('Return') }}</button>@endif
    </form>
</div>
@endif

<div class="pr-list">
    @foreach ($indicators as $indicator)
    @php
        $plan = $indicator->plans->first();
        $editable = $canManage && $plan && in_array($plan->status, ['draft', 'returned'], true);
        $documents = $plan?->getMedia('supporting-documents') ?? collect();
        $unit = $indicator->unitOfMeasure?->name;
        $stepIndex = match ($plan?->status) { 'pending_institution' => 1, 'pending_thematic' => 2, 'approved' => 3, default => 0 };
    @endphp
    <div class="chart-card pr-card">
        <div class="pr-card-head">
            <div>
                <div class="pr-code">{{ $indicator->code }}</div>
                <div class="pr-name">{{ $indicator->name }}</div>
            </div>
            <span class="s-badge {{ $plan ? ($statusTone[$plan->status] ?? 's-default') : 's-default' }}">{{ $plan ? __(ucfirst(str_replace('_', ' ', $plan->status))) : __('Not planned') }}</span>
        </div>

        @if ($plan)
        <ol class="pr-steps">
            @foreach (array_values($steps) as $i => $label)
            <li class="{{ $stepIndex > $i ? 'is-done' : ($stepIndex === $i && $stepIndex > 0 && $plan->status !== 'approved' ? 'is-current' : '') }}"><span>{{ $i + 1 }}</span>{{ $label }}</li>
            @endforeach
        </ol>

        <div class="pr-facts">
            <div><span>{{ __('Interventions') }}</span>
                @forelse ($indicator->interventions as $intervention)<em>{{ $intervention->name }}</em>@empty<em class="text-muted">—</em>@endforelse
            </div>
            <div><span>{{ __('Target') }}</span><b>{{ rtrim(rtrim(number_format((float) $plan->annual_target, 4), '0'), '.') }}</b> {{ $unit }}</div>
            <div><span>{{ __('Baseline') }}</span><b>{{ $plan->baseline_value !== null ? rtrim(rtrim(number_format((float) $plan->baseline_value, 4), '0'), '.') : '—' }}</b> @if ($plan->baseline_year)<small class="text-muted">({{ $plan->baseline_year }})</small>@endif</div>
            <div><span>{{ __('Budget') }}</span><b>TZS {{ number_format((float) $plan->domestic_budget + (float) $plan->external_budget) }}</b></div>
            <div><span>{{ __('Lead institution') }}</span>{{ $plan->leadOrganization?->name ?? '—' }}</div>
        </div>

        @if ($plan->review_comment)
        <div class="pr-comment"><i class="mdi mdi-comment-text-outline"></i> {{ $plan->review_comment }}</div>
        @endif

        <div class="pr-docs">
            <div class="pr-docs-title"><i class="mdi mdi-paperclip"></i> {{ __('Supporting documents') }} <small>({{ $documents->count() }})</small></div>
            @forelse ($documents as $document)
            <div class="pr-doc">
                <a href="{{ route('indicator-plans.documents.download', [$plan, $document]) }}"><i class="mdi mdi-file-document-outline"></i> {{ $document->file_name }}</a>
                <small class="text-muted">{{ number_format($document->size / 1024) }} KB</small>
                @if ($editable)
                <form method="POST" action="{{ route('indicator-plans.documents.delete', [$plan, $document]) }}" data-confirm="{{ __('Remove this document?') }}">
                    @csrf @method('DELETE')
                    <button class="btn-icon danger" title="{{ __('Remove') }}"><i class="mdi mdi-close"></i></button>
                </form>
                @endif
            </div>
            @empty
            <div class="text-muted small">{{ __('No supporting documents attached.') }}</div>
            @endforelse
            @if ($editable)
            <form method="POST" enctype="multipart/form-data" action="{{ route('indicator-plans.documents.upload', $plan) }}" class="pr-upload">
                @csrf
                <input type="file" name="document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required>
                <button class="btn btn-outline-dark btn-sm"><i class="mdi mdi-upload"></i> {{ __('Attach') }}</button>
            </form>
            @endif
        </div>
        @else
        <p class="text-muted small mb-0">{{ __('Nothing has been planned for this indicator in :year yet.', ['year' => $fy?->name]) }}
            <a href="{{ route('plan-builder.show', ['thematic_area' => $thematicArea, 'financial_year_id' => $financialYearId]) }}">{{ __('Open in Plan Builder') }}</a></p>
        @endif
    </div>
    @endforeach
</div>

@if ($indicators->isEmpty())<div class="alert alert-info">{{ __('No indicators in this thematic area yet.') }}</div>@endif
@endsection
