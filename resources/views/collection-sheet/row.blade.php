@php
    /** @var \App\Models\Indicator $indicator */
    $num = fn ($value, int $decimals = 4) => $value === null || $value === ''
        ? ''
        : rtrim(rtrim(number_format((float) $value, $decimals, '.', ','), '0'), '.');
    $isText = in_array($indicator->measurementType?->code, ['text', 'qualitative'], true);
    $budgetOn = (bool) $indicator->has_budget_implication;
    $open = $editable && $periodOpen;
    $entryStatus = $entry?->status;
    $statusClass = match ($entryStatus) {
        'approved' => 's-active',
        'submitted', 'pending_approval' => 's-investigation',
        'rejected' => 's-inactive',
        default => 's-default',
    };
    $statusLabel = match (true) {
        $plan === null => __('Not open for collection'),
        $indicator->requires_location && ! $entry && $plan => __('Choose a location'),
        $entryStatus === null => __('Not started'),
        default => __(ucfirst(str_replace('_', ' ', $entryStatus))),
    };
    $lead = $plan?->lead_organization_id ? $organizations->get($plan->lead_organization_id)?->name : null;
    $partnerNames = collect($plan?->participating_organization_ids ?? [])->map(fn ($id) => $organizations->get((int) $id)?->name)->filter()->implode(', ');
    $limits = $indicator->valueLimits();
    $locked = fn (bool $on = true) => $open && $on ? '' : 'disabled';
@endphp
<tr class="pb-row cs-row {{ $plan === null ? 'cs-row-off' : '' }}"
    data-id="{{ $indicator->id }}"
    data-period="{{ $periodId }}"
    data-entry="{{ $entry?->id }}"
    data-location="{{ $entry?->location_id }}"
    data-requires-location="{{ $indicator->requires_location ? 1 : 0 }}"
    data-limits="{{ json_encode($limits) }}"
    data-target="{{ $plan?->annual_target }}">
    <td class="pb-col-indicator cs-locked">
        <div class="cs-indicator-name">{{ $indicator->name }}</div>
        <div class="cs-indicator-meta">
            {{ $indicator->measurementType ? __($indicator->measurementType->name) : '' }}{{ $indicator->unitOfMeasure ? ' · '.__($indicator->unitOfMeasure->name) : '' }}
            @if ($indicator->requires_evidence)<span class="cs-chip" title="{{ __('Requires Evidence') }}"><i class="mdi mdi-paperclip"></i></span>@endif
        </div>
        @if ($indicator->requires_location && $plan)
        <select class="cs-location" data-no-select2 data-url="{{ route('collection-sheet.locations', [$indicator->thematic_area_id, $indicator]) }}"
                data-placeholder="{{ __('Choose :level…', ['level' => __(ucfirst(str_replace('_', '/', (string) $indicator->reporting_location_level)))]) }}" {{ $locked() }}>
            @if ($entry?->location_id)
            <option value="{{ $entry->location_id }}" selected>{{ $locationLabel }}</option>
            @endif
        </select>
        @endif
    </td>

    <td data-label="{{ __('Planned interventions') }}" class="pb-col-acts cs-locked">
        @forelse ($indicator->interventions as $intervention)
        <div class="cs-intervention">{{ $intervention->name }}</div>
        @empty
        <span class="text-muted">—</span>
        @endforelse
    </td>

    <td data-label="{{ __('Baseline') }}" class="cs-locked cs-num">
        @if ($plan)
        {{ $num($plan->baseline_value) ?: '—' }}
        <small class="d-block text-muted">{{ $plan->baseline_financial_year_id ? $financialYears->get($plan->baseline_financial_year_id)?->name : $plan->baseline_year }}</small>
        @else — @endif
    </td>
    <td data-label="{{ __('Target') }}" class="cs-locked cs-num">{{ $plan ? ($num($plan->annual_target) ?: '—') : '—' }}</td>

    <td data-label="{{ __('Planned') }} · {{ __('Internal') }}" class="cs-locked cs-num">{{ $plan && $budgetOn ? $num($plan->domestic_budget, 2) : '—' }}</td>
    <td data-label="{{ __('Planned') }} · {{ __('External') }}" class="cs-locked cs-num">{{ $plan && $budgetOn ? $num($plan->external_budget, 2) : '—' }}</td>
    <td data-label="{{ __('Planned') }} · {{ __('Total') }}" class="cs-locked cs-num cs-total">{{ $plan && $budgetOn ? $num((float) $plan->domestic_budget + (float) $plan->external_budget, 2) : '—' }}</td>

    <td data-label="{{ __('Achieved') }}" class="cs-fill">
        @if ($isText)
        <textarea class="pb-cell pb-text cs-input cs-actual-text" rows="1" placeholder="{{ __('Response…') }}" maxlength="5000" {{ $locked() }}>{{ $entry?->actual_text }}</textarea>
        @else
        <input type="text" inputmode="decimal" class="pb-cell pb-num cs-input cs-actual" placeholder="0" value="{{ $num($entry?->actual_value) }}" {{ $locked() }}>
        @endif
    </td>
    <td data-label="{{ __('Progress') }}" class="cs-num cs-progress">—</td>
    <td data-label="{{ __('Status of implementation') }}" class="cs-fill">
        <textarea class="pb-cell pb-text cs-input cs-implementation" rows="1" placeholder="{{ __('What was implemented…') }}" maxlength="5000" {{ $locked() }}>{{ $entry?->implementation_status }}</textarea>
    </td>

    <td data-label="{{ __('Released') }} · {{ __('Internal') }}" class="cs-fill"><input type="text" inputmode="decimal" class="pb-cell pb-num pb-money cs-input cs-released cs-domestic-released" placeholder="0" value="{{ $num($entry?->domestic_released, 2) }}" {{ $locked($budgetOn) }}></td>
    <td data-label="{{ __('Released') }} · {{ __('External') }}" class="cs-fill"><input type="text" inputmode="decimal" class="pb-cell pb-num pb-money cs-input cs-released cs-external-released" placeholder="0" value="{{ $num($entry?->external_released, 2) }}" {{ $locked($budgetOn) }}></td>
    <td data-label="{{ __('Released') }} · {{ __('Total') }}" class="cs-num cs-total cs-released-total">—</td>

    <td data-label="{{ __('Spent') }} · {{ __('Internal') }}" class="cs-fill"><input type="text" inputmode="decimal" class="pb-cell pb-num pb-money cs-input cs-spent cs-domestic-spent" placeholder="0" value="{{ $num($entry?->domestic_spent, 2) }}" {{ $locked($budgetOn) }}></td>
    <td data-label="{{ __('Spent') }} · {{ __('External') }}" class="cs-fill"><input type="text" inputmode="decimal" class="pb-cell pb-num pb-money cs-input cs-spent cs-external-spent" placeholder="0" value="{{ $num($entry?->external_spent, 2) }}" {{ $locked($budgetOn) }}></td>
    <td data-label="{{ __('Spent') }} · {{ __('Total') }}" class="cs-num cs-total cs-spent-total">—</td>

    <td data-label="{{ __('Lead institution') }}" class="cs-locked">{{ $lead ?? '—' }}</td>
    <td data-label="{{ __('Partners') }}" class="cs-locked">{{ $partnerNames ?: '—' }}</td>

    <td data-label="{{ __('Remarks') }}" class="cs-fill">
        <textarea class="pb-cell pb-text cs-input cs-remarks" rows="1" placeholder="{{ __('Remarks…') }}" maxlength="5000" {{ $locked() }}>{{ $entry?->remarks }}</textarea>
    </td>

    <td class="pb-col-status">
        <span class="s-badge {{ $statusClass }} cs-status">{{ $statusLabel }}</span>
        <span class="pb-missing {{ $missing && $entry && $open ? '' : 'd-none' }}" title="{{ __('Still missing:') }} {{ implode(', ', $missing) }}"><i class="mdi mdi-alert-circle-outline"></i></span>
    </td>
    <td data-label="{{ __('Evidence') }}" class="cs-fill cs-evidence">
        <ul class="cs-files">
            @foreach ($files as $file)
            <li data-delete="{{ $file['delete_url'] }}">
                <a href="{{ $file['url'] }}" target="_blank" rel="noopener" title="{{ $file['name'] }}">{{ $file['name'] }}</a>
                @if ($open)<button type="button" class="cs-file-remove" title="{{ __('Remove') }}"><i class="mdi mdi-close"></i></button>@endif
            </li>
            @endforeach
        </ul>
        <div class="cs-evidence-actions">
            @if ($open)
            <label class="cs-attach">
                <input type="file" class="cs-file-input" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                <i class="mdi mdi-paperclip"></i> {{ __('Attach') }}
            </label>
            @endif
            <a class="btn-icon cs-details {{ $entry ? '' : 'd-none' }}" href="{{ $entry ? route('indicator-data-entries.edit', $entry) : '#' }}" title="{{ __('Open the full form') }}"><i class="mdi mdi-open-in-new"></i></a>
        </div>
    </td>
</tr>
