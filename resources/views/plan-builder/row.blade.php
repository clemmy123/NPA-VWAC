@php
    $num = fn ($value, int $decimals = 4) => $value === null || $value === ''
        ? ''
        : rtrim(rtrim(number_format((float) $value, $decimals, '.', ','), '0'), '.');
    $flagDefaults = [
        'requires_location' => false,
        'requires_activity' => true,
        'has_budget_implication' => true,
        'requires_evidence' => true,
        'requires_hierarchical_approval' => true,
    ];
    $flagLabels = [
        'requires_location' => __('Requires Location'),
        'requires_activity' => __('Requires Activity'),
        'has_budget_implication' => __('Has Budget Implication'),
        'requires_evidence' => __('Requires Evidence'),
        'requires_hierarchical_approval' => __('Requires Hierarchical Approval'),
    ];
    $flag = fn (string $name): bool => $indicator ? (bool) $indicator->{$name} : $flagDefaults[$name];
    $planStatus = $plan?->status;
    $locked = $planStatus !== null && ! in_array($planStatus, ['draft', 'returned'], true);
    $editPlan = $canPlan && ! $locked;
    $editIndicator = $canUpdateIndicator && ! $locked;
    $budgetOn = $flag('has_budget_implication');
    $statusClass = match ($planStatus) {
        'approved' => 's-active',
        'pending_institution', 'pending_thematic' => 's-investigation',
        'returned' => 's-inactive',
        default => 's-default',
    };
    $participants = $plan?->participating_organization_ids ?? [];
@endphp
<tr class="pb-row"
    data-id="{{ $indicator?->id }}"
    data-measurement="{{ $indicator?->measurementType?->code }}"
    data-locked="{{ $locked ? 1 : 0 }}">
    <td class="pb-col-indicator">
        <textarea class="pb-cell pb-text pb-name" rows="1" placeholder="{{ __('Indicator name…') }}"
                  maxlength="255" {{ $editIndicator || ! $indicator ? '' : 'readonly' }}>{{ $indicator?->name }}</textarea>
        <button type="button" class="pb-more-toggle"><i class="mdi mdi-cog-outline"></i> {{ __('Settings') }}</button>
        <div class="pb-more-fields">
            <div>
                <label>{{ __('Reporting frequency') }}</label>
                <select class="pb-cell pb-frequency" data-no-select2 {{ $editIndicator || ! $indicator ? '' : 'disabled' }}>
                    <option value="">—</option>
                    @foreach ($reportingFrequencies as $freq)
                    <option value="{{ $freq }}" @selected($indicator?->reporting_frequency === $freq)>{{ __(ucfirst($freq)) }}</option>
                    @endforeach
                </select>
            </div>
            @foreach ($flagLabels as $name => $label)
            <label class="pb-flag">
                <input type="checkbox" class="pb-flag-input" data-flag="{{ $name }}" @checked($flag($name)) {{ $editIndicator || ! $indicator ? '' : 'disabled' }}>
                <span>{{ $label }}</span>
            </label>
            @endforeach
        </div>
    </td>

    <td class="pb-col-acts">
        <ul class="pb-acts">
            @foreach ($indicator?->interventions ?? [] as $intervention)
            <li class="pb-act-item" data-intervention-id="{{ $intervention->id }}">
                <textarea class="pb-cell pb-text pb-act" rows="1" maxlength="255" {{ $canUpdateIntervention && ! $locked ? '' : 'readonly' }}>{{ $intervention->name }}</textarea>
                @if ($canDeleteIntervention && ! $locked)
                <button type="button" class="pb-act-remove" title="{{ __('Remove') }}"><i class="mdi mdi-close"></i></button>
                @endif
            </li>
            @endforeach
        </ul>
        @if ($canCreateIntervention && ! $locked)
        <button type="button" class="pb-act-add"><i class="mdi mdi-plus"></i> {{ __('Add intervention') }}</button>
        @endif
    </td>

    <td>
        <select class="pb-cell pb-measurement-type" data-no-select2 {{ $editIndicator || ! $indicator ? '' : 'disabled' }}>
            <option value="">—</option>
            @foreach ($measurementTypes as $type)
            <option value="{{ $type->id }}" data-code="{{ $type->code }}" data-limits="{{ json_encode($type->valueLimits()) }}" @selected($indicator?->measurement_type_id === $type->id)>{{ __($type->name) }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select class="pb-cell pb-unit" data-no-select2 {{ $editIndicator || ! $indicator ? '' : 'disabled' }}>
            <option value="">—</option>
            @foreach ($unitsOfMeasure as $unit)
            <option value="{{ $unit->id }}" data-symbol="{{ $unit->symbol }}" data-limits="{{ json_encode($unit->valueLimits()) }}" @selected($indicator?->unit_of_measure_id === $unit->id)>{{ __($unit->name) }}</option>
            @endforeach
        </select>
    </td>

    <td>
        <select class="pb-cell pb-baseline-year" data-no-select2 {{ $editPlan ? '' : 'disabled' }}>
            <option value="">—</option>
            @foreach ($financialYears as $fy)
            <option value="{{ $fy->id }}" @selected($plan?->baseline_financial_year_id === $fy->id)>{{ $fy->name }}</option>
            @endforeach
        </select>
    </td>
    <td><input type="text" inputmode="decimal" class="pb-cell pb-num pb-baseline" placeholder="0" value="{{ $num($plan?->baseline_value) }}" {{ $editPlan ? '' : 'disabled' }}></td>
    <td><input type="text" inputmode="decimal" class="pb-cell pb-num pb-target" placeholder="0" value="{{ $num($plan?->annual_target) }}" {{ $editPlan ? '' : 'disabled' }}></td>
    <td><input type="text" inputmode="decimal" class="pb-cell pb-num pb-money pb-domestic" placeholder="0" value="{{ $num($plan?->domestic_budget, 2) }}" {{ $editPlan && $budgetOn ? '' : 'disabled' }}></td>
    <td><input type="text" inputmode="decimal" class="pb-cell pb-num pb-money pb-external" placeholder="0" value="{{ $num($plan?->external_budget, 2) }}" {{ $editPlan && $budgetOn ? '' : 'disabled' }}></td>

    <td>
        <select class="pb-cell pb-lead" data-no-select2 {{ $editPlan ? '' : 'disabled' }}>
            <option value="">—</option>
            @foreach ($organizations as $organization)
            <option value="{{ $organization->id }}" @selected($plan?->lead_organization_id === $organization->id)>{{ $organization->name }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select class="pb-participants" multiple data-no-select2 {{ $editPlan ? '' : 'disabled' }}>
            @foreach ($organizations as $organization)
            <option value="{{ $organization->id }}" @selected(in_array($organization->id, $participants))>{{ $organization->name }}</option>
            @endforeach
        </select>
    </td>

    <td class="pb-col-status">
        <span class="s-badge {{ $statusClass }} pb-status">{{ $planStatus ? __(ucfirst(str_replace('_', ' ', $planStatus))) : __('Unplanned') }}</span>
        <span class="pb-missing {{ ($missing ?? []) ? '' : 'd-none' }}" title="{{ __('Still missing:') }} {{ implode(', ', $missing ?? []) }}"><i class="mdi mdi-alert-circle-outline"></i></span>
    </td>
    <td class="pb-col-actions">
        @if ($canDeleteIndicator && ! $locked)
        <button type="button" class="btn-icon danger pb-delete" title="{{ __('Delete indicator') }}"><i class="mdi mdi-trash-can-outline"></i></button>
        @endif
    </td>
</tr>
