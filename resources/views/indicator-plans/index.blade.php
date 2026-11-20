@extends('components.main-layout')
@section('title', __('MTAKUWWA Annual Plan'))
@section('content')
<div class="page-header"><div>
    <h4 class="page-title">{{ __('Annual Implementation Plan') }} — {{ $thematicArea->localized_name }}</h4>
    <p class="text-muted mb-0">{{ __('Activities, baseline, target, budget and institutional ownership in one worksheet.') }}</p>
</div></div>
<div class="chart-card mb-3 p-3">
<form method="GET" class="d-flex align-items-end gap-2">
<label class="form-label" for="fy">{{ __('Financial year') }}</label>
<select class="form-select" name="financial_year_id" id="fy" style="max-width:220px">
@foreach ($financialYears as $year)<option value="{{ $year->id }}" @selected($financialYearId == $year->id)>{{ $year->name }}</option>@endforeach
</select><button class="btn btn-dark">{{ __('View') }}</button></form></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@foreach ($indicators as $indicator)
@php $plan = $indicator->plans->first(); $editable = $canManage && (!$plan || in_array($plan->status, ['draft','returned'], true)); @endphp
<div class="chart-card mb-3 p-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0">{{ $indicator->code }} — {{ $indicator->name }}</h5>
    <span class="badge bg-secondary">{{ $plan?->status ?? __('Unplanned') }}</span>
  </div>
  <form method="POST" action="{{ route('indicator-plans.store', $thematicArea) }}">
  @csrf<input type="hidden" name="indicator_id" value="{{ $indicator->id }}"><input type="hidden" name="financial_year_id" value="{{ $financialYearId }}">
  <fieldset @disabled(!$editable)>
  <div class="row g-3">
    <div class="col-md-12"><label class="form-label">{{ __('Planned activity / Kazi zitakazotekelezwa') }}</label>
      <textarea name="planned_activity" class="form-control" rows="2" required>{{ old('planned_activity', $plan?->planned_activity) }}</textarea></div>
    <div class="col-md-3"><label class="form-label">{{ __('Baseline year') }}</label><input type="number" name="baseline_year" class="form-control" min="2000" max="2100" value="{{ old('baseline_year',$plan?->baseline_year) }}"></div>
    <div class="col-md-3"><label class="form-label">{{ __('Baseline value') }}</label><input type="number" name="baseline_value" min="0" step="any" class="form-control" value="{{ old('baseline_value',$plan?->baseline_value) }}"></div>
    <div class="col-md-3"><label class="form-label">{{ __('Annual target / Shabaha') }}</label><input type="number" name="annual_target" min="0" step="any" class="form-control" value="{{ old('annual_target',$plan?->annual_target) }}" required></div>
    <div class="col-md-3"><label class="form-label">{{ __('Domestic funding (TZS)') }}</label><input type="number" name="domestic_budget" min="0" step="0.01" class="form-control" value="{{ old('domestic_budget',$plan?->domestic_budget ?? 0) }}" required></div>
    <div class="col-md-3"><label class="form-label">{{ __('External funding (TZS)') }}</label><input type="number" name="external_budget" min="0" step="0.01" class="form-control" value="{{ old('external_budget',$plan?->external_budget ?? 0) }}" required></div>
    <div class="col-md-4"><label class="form-label">{{ __('Lead institution') }}</label><select name="lead_organization_id" class="form-select" required><option value="">{{ __('Choose') }}</option>
      @foreach($organizations as $org)<option value="{{ $org->id }}" @selected(old('lead_organization_id',$plan?->lead_organization_id)==$org->id)>{{ $org->name }}</option>@endforeach</select></div>
    <div class="col-md-5"><label class="form-label">{{ __('Participating institutions') }}</label><select name="participating_organization_ids[]" class="form-select" multiple size="4">
      @foreach($organizations as $org)<option value="{{ $org->id }}" @selected(in_array($org->id, old('participating_organization_ids',$plan?->participating_organization_ids ?? [])))>{{ $org->name }}</option>@endforeach</select></div>
  </div></fieldset>
  @if ($editable)<button class="btn btn-dark mt-3">{{ __('Save plan') }}</button>@endif
  </form>
  @if($plan)
  <div class="border-top mt-3 pt-3">
    <h6>{{ __('Supporting documents') }}</h6>
    @forelse($plan->getMedia('supporting-documents') as $document)
      <div class="d-flex align-items-center gap-2 mb-2"><a href="{{ route('indicator-plans.documents.download',[$plan,$document]) }}">{{ $document->file_name }}</a>
      @if($editable)<form method="POST" action="{{ route('indicator-plans.documents.delete',[$plan,$document]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('Remove') }}</button></form>@endif
      </div>
    @empty<p class="small text-muted">{{ __('No supporting documents attached.') }}</p>@endforelse
    @if($editable)<form method="POST" enctype="multipart/form-data" action="{{ route('indicator-plans.documents.upload',$plan) }}" class="d-flex gap-2 align-items-center">
    @csrf<input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required><button class="btn btn-outline-primary">{{ __('Attach document') }}</button></form>@endif
  </div>
  <div class="border-top mt-3 pt-3">
  <small class="text-muted">{{ __('Version') }} {{ $plan->version }} · {{ __('Planned total') }} TZS {{ number_format((float)$plan->domestic_budget+(float)$plan->external_budget,2) }}
  @if($plan->review_comment) · {{ __('Last review') }}: {{ $plan->review_comment }} @endif</small>
  <div class="d-flex flex-wrap gap-2 mt-2">
  @foreach(['submit'=>['draft','returned'],'confirm'=>['pending_institution'],'approve'=>['pending_thematic'],'return'=>['pending_institution','pending_thematic']] as $action => $states)
    @if(in_array($plan->status,$states))
    @php
      $allowed = $action==='submit' ? $canManage : ($action==='confirm'
        ? (auth()->user()->hasRole('Super Admin') || (auth()->user()->can('institution.confirm') && (int)auth()->user()->organization_id===(int)$plan->lead_organization_id && (int)auth()->id()!==(int)$plan->submitted_by))
        : ($canManage && ($action!=='approve' || (int)auth()->id()!==(int)$plan->submitted_by || auth()->user()->hasRole('Super Admin'))));
    @endphp
    @if($allowed)<form method="POST" action="{{ route('indicator-plans.action',$plan) }}" class="d-inline-flex gap-2">
      @csrf<input type="hidden" name="action" value="{{ $action }}">
      @if($action==='return')<input type="text" required maxlength="2000" name="comment" placeholder="{{ __('Reason for return') }}" class="form-control form-control-sm">@endif
      <button class="btn btn-sm {{ $action==='return'?'btn-outline-danger':'btn-outline-primary' }}">{{ __(ucfirst($action)) }}</button>
    </form>@endif
    @endif
  @endforeach
  </div></div>@endif
</div>
@endforeach
@if($indicators->isEmpty())<div class="alert alert-info">{{ __('No indicators in this thematic area yet.') }}</div>@endif
@endsection
