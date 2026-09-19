@php
    $statusBadge = [
        'draft' => 's-default',
        'submitted' => 's-investigation',
        'pending_approval' => 's-investigation',
        'approved' => 's-active',
        'rejected' => 's-inactive',
    ];
    $canEdit = in_array($entry->status, ['draft', 'rejected'], true)
        && (int) $entry->entered_by === (int) auth()->id()
        && auth()->user()?->can('indicator-data.update');
    $awaitingReview = in_array($entry->status, ['submitted', 'pending_approval'], true);
    $canApprove = $awaitingReview && auth()->user()?->can('indicator-data.approve');
    $canReturn = $awaitingReview && auth()->user()?->can('indicator-data.return');
    $actualDisplay = $entry->actual_text ?: \App\Support\DisplayNumber::format($entry->actual_value);
    $unit = $entry->indicator?->unitOfMeasure?->symbol ?: $entry->indicator?->unitOfMeasure?->name;
    $locationName = $locationNames[$entry->id] ?? null;
@endphp

<div class="chart-card mb-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h6 class="mb-1">{{ __('Collection :number', ['number' => $number]) }}</h6>
            <div class="text-muted small">{{ $entry->entry_date?->format('d M Y') ?? '—' }}</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="s-badge {{ $statusBadge[$entry->status] ?? 's-default' }}">{{ \App\Support\UiCopy::entryStatus($entry->status) }}</span>
            @if ($canEdit)
            <a href="{{ route('indicator-data-entries.edit', $entry) }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-pencil-outline"></i> {{ __('Edit') }}</a>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3"><span class="text-muted small">{{ __('Financial Year') }}</span><div>{{ $entry->financialYear?->name ?? '—' }}</div></div>
        <div class="col-md-3 mb-3"><span class="text-muted small">{{ __('Reporting Period') }}</span><div>{{ $entry->reportingPeriod?->name ?? '—' }}</div></div>
        <div class="col-md-3 mb-3"><span class="text-muted small">{{ __('Entry Date') }}</span><div>{{ $entry->entry_date?->format('d M Y') ?? '—' }}</div></div>
        <div class="col-md-3 mb-3"><span class="text-muted small">{{ __('Entered By') }}</span><div>{{ $entry->enteredBy?->name ?? '—' }}</div></div>
        <div class="col-md-3 mb-3"><span class="text-muted small">{{ __('Organization') }}</span><div>{{ $entry->organization?->name ?? '—' }}</div></div>
        <div class="col-md-3 mb-3"><span class="text-muted small">{{ __('Location') }}</span><div>{{ $locationName ?? '—' }}</div></div>
        <div class="col-md-3 mb-3">
            <span class="text-muted small">{{ __('Actual Value') }}</span>
            <div>{{ $actualDisplay }}@if ($unit && ! $entry->actual_text) {{ $unit }}@endif</div>
        </div>
        @if ($entry->activity_name)
        <div class="col-md-3 mb-3"><span class="text-muted small">{{ __('Activity') }}</span><div>{{ $entry->activity_name }}</div></div>
        @endif
        @if ($entry->budget_allocated !== null || $entry->budget_used !== null)
        <div class="col-md-3 mb-3"><span class="text-muted small">{{ __('Budget Allocated') }}</span><div>{{ \App\Support\DisplayNumber::format($entry->budget_allocated, 2) }} {{ $entry->currency }}</div></div>
        <div class="col-md-3 mb-3"><span class="text-muted small">{{ __('Budget Used') }}</span><div>{{ \App\Support\DisplayNumber::format($entry->budget_used, 2) }} {{ $entry->currency }}</div></div>
        @endif
        @if ($entry->remarks)
        <div class="col-12 mb-1"><span class="text-muted small">{{ __('Remarks') }}</span><p class="mb-0">{{ $entry->remarks }}</p></div>
        @endif
        @if ($entry->activity_description)
        <div class="col-12 mb-1"><span class="text-muted small">{{ __('Activity Description') }}</span><p class="mb-0">{{ $entry->activity_description }}</p></div>
        @endif
    </div>

    @if ($entry->activities->isNotEmpty())
    <h6 class="mt-2 mb-2">{{ __('Activities') }}</h6>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Participants') }}</th>
                    <th>{{ __('Women') }}</th>
                    <th>{{ __('Men') }}</th>
                    <th>{{ __('Children') }}</th>
                    <th>{{ __('Other') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entry->activities as $activity)
                <tr>
                    <td>{{ $activity->name }}</td>
                    <td>{{ \App\Support\DisplayNumber::format($activity->participants_total) }}</td>
                    <td>{{ \App\Support\DisplayNumber::format($activity->women) }}</td>
                    <td>{{ \App\Support\DisplayNumber::format($activity->men) }}</td>
                    <td>{{ \App\Support\DisplayNumber::format($activity->children) }}</td>
                    <td>{{ \App\Support\DisplayNumber::format($activity->other) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if ($entry->rows->isNotEmpty())
    <h6 class="mt-3 mb-2">{{ __('Disaggregated rows') }}</h6>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{{ __('Label') }}</th>
                    <th>{{ __('Breakdown') }}</th>
                    <th>{{ __('Value') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entry->rows as $row)
                <tr>
                    <td>{{ $row->label ?: '—' }}</td>
                    <td>{{ $row->dimensionOptions->pluck('name')->filter()->join(', ') ?: '—' }}</td>
                    <td>{{ \App\Support\DisplayNumber::format($row->value) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if ($entry->expenses->isNotEmpty())
    <h6 class="mt-3 mb-2">{{ __('Expenses') }}</h6>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{{ __('Category') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entry->expenses as $expense)
                <tr>
                    <td>{{ $expense->expense_category }}</td>
                    <td>{{ $expense->description ?: '—' }}</td>
                    <td>{{ \App\Support\DisplayNumber::format($expense->amount, 2) }} {{ $entry->currency }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if ($entry->getMedia('evidence')->isNotEmpty())
    <h6 class="mt-3 mb-2">{{ __('Evidence') }}</h6>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{{ __('File') }}</th>
                    <th>{{ __('Size') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entry->getMedia('evidence') as $media)
                <tr>
                    <td>{{ $media->file_name }}</td>
                    <td>{{ number_format($media->size / 1024, 1) }} KB</td>
                    <td>
                        <a href="{{ route('indicator-data-entries.evidence.download', [$entry, $media]) }}" class="btn-icon" title="{{ __('Download') }}">
                            <i class="mdi mdi-download-outline"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if ($canApprove || $canReturn)
    <div class="collection-review-actions mt-3">
        @if ($canApprove)
        <form action="{{ route('indicator-data-entries.approve', $entry) }}" method="POST" data-confirm="{{ __('Approve this collection?') }}">
            @csrf
            <button type="submit" class="btn-review is-approve">
                <i class="mdi mdi-check-circle-outline"></i> {{ __('Approve') }}
            </button>
        </form>
        @endif
        @if ($canReturn)
        <form action="{{ route('indicator-data-entries.return', $entry) }}" method="POST" data-confirm="{{ __('Rollback this collection to the submitter?') }}">
            @csrf
            <button type="submit" class="btn-review is-rollback">
                <i class="mdi mdi-undo"></i> {{ __('Rollback') }}
            </button>
        </form>
        @endif
    </div>
    @endif
</div>
