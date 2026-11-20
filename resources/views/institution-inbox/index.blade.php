@extends('components.main-layout')
@section('title', __('Institution Ownership Inbox'))
@section('content')
<div class="page-header"><div><h4 class="page-title">{{ __('Institution Ownership Inbox') }}</h4>
<p class="mb-0 text-muted">{{ __('Confirm your organization’s plans and submissions before thematic review.') }}</p></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="chart-card mb-3 p-3">
  <h5>{{ __('Annual plans awaiting confirmation') }} ({{ $plans->count() }})</h5>
  <div class="table-responsive"><table class="table"><thead><tr><th>{{ __('Indicator') }}</th><th>{{ __('Activity') }}</th><th>{{ __('Financial year') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
  @forelse($plans as $plan)<tr><td>{{ $plan->indicator?->name }}</td><td>{{ $plan->planned_activity }}
    @foreach($plan->getMedia('supporting-documents') as $doc)<div><a href="{{ route('indicator-plans.documents.download',[$plan,$doc]) }}">{{ $doc->file_name }}</a></div>@endforeach
  </td><td>{{ $plan->financialYear?->name }}</td><td>
    <form method="POST" action="{{ route('indicator-plans.action',$plan) }}" class="d-flex gap-2">@csrf<input type="hidden" name="action" value="confirm"><button class="btn btn-success btn-sm">{{ __('Confirm ownership') }}</button></form>
    <form method="POST" action="{{ route('indicator-plans.action',$plan) }}" class="d-flex gap-2 mt-2">@csrf<input type="hidden" name="action" value="return"><input type="text" required name="comment" class="form-control form-control-sm" placeholder="{{ __('Reason for return') }}"><button class="btn btn-outline-danger btn-sm">{{ __('Return') }}</button></form>
  </td></tr>@empty<tr><td colspan="4">{{ __('No outstanding plans.') }}</td></tr>@endforelse
  </tbody></table></div>
</div>
<div class="chart-card p-3">
  <h5>{{ __('Data submissions awaiting confirmation') }} ({{ $entries->count() }})</h5>
  <div class="table-responsive"><table class="table"><thead><tr><th>{{ __('Indicator') }}</th><th>{{ __('Submitted by') }}</th><th>{{ __('Actual result') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
  @forelse($entries as $entry)<tr><td>{{ $entry->indicator?->name }}</td><td>{{ $entry->enteredBy?->name }}</td><td>{{ $entry->actual_value ?? $entry->actual_text ?? '—' }}</td><td>
    <form method="POST" action="{{ route('indicator-data-entries.institution-confirm',$entry) }}">@csrf<button class="btn btn-success btn-sm">{{ __('Confirm ownership') }}</button></form>
    <form method="POST" action="{{ route('indicator-data-entries.institution-return',$entry) }}" class="d-flex gap-2 mt-2">@csrf
      <input type="text" required minlength="5" maxlength="2000" name="comment" class="form-control form-control-sm" placeholder="{{ __('Reason for return') }}">
      <button class="btn btn-outline-danger btn-sm">{{ __('Return') }}</button>
    </form>
    <a href="{{ route('indicator-data-entries.show',$entry) }}" class="btn btn-outline-secondary btn-sm mt-2">{{ __('Review details') }}</a>
  </td></tr>@empty<tr><td colspan="4">{{ __('No outstanding submissions.') }}</td></tr>@endforelse
  </tbody></table></div>
</div>
@endsection
