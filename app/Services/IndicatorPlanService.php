<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorBaseline;
use App\Models\IndicatorPlan;
use App\Models\IndicatorTarget;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Coordinates the annual planning ownership and thematic approval workflow. */
class IndicatorPlanService
{
    public function save(array $attributes, User $actor): IndicatorPlan
    {
        $indicator = Indicator::query()->with('thematicArea')->findOrFail($attributes['indicator_id']);
        $this->authorizeThematic($actor, (int) $indicator->thematic_area_id);

        return DB::transaction(function () use ($attributes, $actor): IndicatorPlan {
            $plan = IndicatorPlan::query()->lockForUpdate()->firstOrNew([
                'indicator_id' => $attributes['indicator_id'],
                'financial_year_id' => $attributes['financial_year_id'],
            ]);
            if ($plan->exists && ! in_array($plan->status, ['draft', 'returned'], true)) {
                throw ValidationException::withMessages(['plan' => 'A submitted or approved plan cannot be edited.']);
            }
            $plan->fill($attributes);
            if (array_key_exists('baseline_financial_year_id', $attributes)) {
                $plan->baseline_year = $attributes['baseline_financial_year_id']
                    ? FinancialYear::query()->whereKey($attributes['baseline_financial_year_id'])->first()?->start_date?->year
                    : null;
            }
            // Autosaved edits to a draft stay on the same version; only a
            // resubmission after a return starts a new one.
            $plan->version = ! $plan->exists ? 1 : ($plan->status === 'returned' ? $plan->version + 1 : $plan->version);
            $plan->status = 'draft';
            if (! $plan->exists) {
                $plan->created_by = $actor->id;
            }
            $plan->save();

            return $plan;
        });
    }

    public function transition(IndicatorPlan $plan, User $actor, string $action, ?string $comment): IndicatorPlan
    {
        return DB::transaction(function () use ($plan, $actor, $action, $comment): IndicatorPlan {
            $plan = IndicatorPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $changes = ['review_comment' => $comment];
            switch ($action) {
                case 'submit':
                    $this->authorizeThematic($actor, (int) $plan->indicator->thematic_area_id);
                    $this->expectStatus($plan, ['draft', 'returned', 'pending_institution', 'pending_thematic']);
                    if ($missing = $this->missingFields($plan)) {
                        throw ValidationException::withMessages(['plan' => __('Incomplete plan: :fields.', ['fields' => implode(', ', $missing)])]);
                    }
                    // No institutional/coordinator chain: completing the plan opens it for data collection.
                    $changes += [
                        'status' => 'approved', 'submitted_by' => $actor->id, 'submitted_at' => now(),
                        'confirmed_by' => null, 'confirmed_at' => null,
                        'approved_by' => $actor->id, 'approved_at' => now(),
                    ];
                    $this->recordTargetAndBaseline($plan, $actor);
                    break;
                case 'unlock':
                    $this->authorizeThematic($actor, (int) $plan->indicator->thematic_area_id);
                    $this->expectStatus($plan, ['approved']);
                    $changes += ['status' => 'returned', 'approved_by' => null, 'approved_at' => null];
                    break;
                case 'confirm':
                    $this->authorizeInstitution($actor, (int) $plan->lead_organization_id, (int) $plan->submitted_by);
                    $this->expectStatus($plan, ['pending_institution']);
                    $changes += ['status' => 'pending_thematic', 'confirmed_by' => $actor->id, 'confirmed_at' => now()];
                    break;
                case 'approve':
                    $this->authorizeThematic($actor, (int) $plan->indicator->thematic_area_id);
                    $this->expectStatus($plan, ['pending_thematic']);
                    if ((int) $plan->submitted_by === (int) $actor->id && ! $actor->hasRole('Super Admin')) {
                        throw new AuthorizationException('A submitter cannot grant final approval of their own plan.');
                    }
                    $changes += ['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now()];
                    $this->recordTargetAndBaseline($plan, $actor);
                    break;
                case 'return':
                    if ($plan->status === 'pending_institution') {
                        $this->authorizeInstitution($actor, (int) $plan->lead_organization_id, (int) $plan->submitted_by);
                    } else {
                        $this->authorizeThematic($actor, (int) $plan->indicator->thematic_area_id);
                    }
                    $this->expectStatus($plan, ['pending_institution', 'pending_thematic']);
                    if (blank($comment)) {
                        throw ValidationException::withMessages(['comment' => 'A return reason is required.']);
                    }
                    $changes += ['status' => 'returned', 'confirmed_by' => null, 'confirmed_at' => null];
                    break;
                default:
                    throw ValidationException::withMessages(['action' => 'Unknown plan action.']);
            }
            $plan->update($changes);

            return $plan->fresh();
        });
    }

    /** Keep dashboard/reporting targets and baselines consistent with a plan that is open for collection. */
    private function recordTargetAndBaseline(IndicatorPlan $plan, User $actor): void
    {
        // Keep existing dashboard/reporting targets and baselines consistent
        // with the newly approved annual implementation plan.
        IndicatorTarget::query()->updateOrCreate([
            'indicator_id' => $plan->indicator_id,
            'financial_year_id' => $plan->financial_year_id,
            'reporting_period_id' => null,
            'dimension_option_id' => null,
        ], [
            'target_value' => $plan->annual_target,
            'created_by' => $actor->id,
        ]);
        if ($plan->baseline_value !== null) {
            IndicatorBaseline::query()->updateOrCreate([
                'indicator_id' => $plan->indicator_id,
                'financial_year_id' => $plan->baseline_financial_year_id ?? $plan->financial_year_id,
            ], [
                'baseline_value' => $plan->baseline_value,
                'baseline_date' => $plan->baseline_year ? $plan->baseline_year.'-07-01' : null,
                'organization_id' => $plan->lead_organization_id,
                'created_by' => $actor->id,
            ]);
        }
    }

    /**
     * Everything an indicator's plan row must carry before it can be
     * submitted. Draft autosave is deliberately lenient; this is the gate.
     *
     * @return list<string> human labels of what is still missing
     */
    public function missingFields(IndicatorPlan $plan): array
    {
        return array_values($this->missingFieldKeys($plan));
    }

    /**
     * Same check keyed by field (interventions, baseline, target, budget, lead)
     * so the Plan Builder can mark the exact cells that need filling.
     *
     * @return array<string, string> field key => human label
     */
    public function missingFieldKeys(IndicatorPlan $plan): array
    {
        $indicator = $plan->indicator()->withCount('interventions')->first();
        $missing = [];

        if ($indicator->requires_activity && $indicator->interventions_count === 0) {
            $missing['interventions'] = __('at least one intervention');
        }
        if ($plan->baseline_value === null || ($plan->baseline_year === null && $plan->baseline_financial_year_id === null)) {
            $missing['baseline'] = __('baseline year and value');
        }
        if ($plan->annual_target === null) {
            $missing['target'] = __('target');
        }
        if ($indicator->has_budget_implication && ($plan->domestic_budget === null || $plan->external_budget === null)) {
            $missing['budget'] = __('domestic and external budget');
        }
        if ($plan->lead_organization_id === null) {
            $missing['lead'] = __('lead institution');
        }

        return $missing;
    }

    public function authorizeThematic(User $actor, int $areaId): void
    {
        if ($actor->hasRole('Super Admin')) {
            return;
        }
        if (! $actor->can('indicator.set-target') && ! $actor->hasRole('Project Manager')) {
            throw new AuthorizationException('You cannot manage plans.');
        }
        abort_unless(in_array($areaId, $actor->visibleThematicAreaIds(), true), 403);
    }

    public function authorizeInstitution(User $actor, int $organizationId, int $submitterId): void
    {
        if ($actor->hasRole('Super Admin')) {
            return;
        }
        abort_unless($actor->can('institution.confirm') && (int) $actor->organization_id === $organizationId, 403);
        if ($actor->id === $submitterId) {
            throw new AuthorizationException('Institution confirmation must be performed by another authorized officer.');
        }
    }

    private function expectStatus(IndicatorPlan $plan, array $allowed): void
    {
        if (! in_array($plan->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'This workflow action is not available at the current stage.']);
        }
    }
}
