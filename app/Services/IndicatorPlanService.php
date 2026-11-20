<?php

namespace App\Services;

use App\Models\IndicatorPlan;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Coordinates the annual planning ownership and thematic approval workflow. */
class IndicatorPlanService
{
    public function save(array $attributes, User $actor): IndicatorPlan
    {
        $indicator = \App\Models\Indicator::query()->with('thematicArea')->findOrFail($attributes['indicator_id']);
        $this->authorizeThematic($actor, (int) $indicator->thematic_area_id);

        return DB::transaction(function () use ($attributes, $actor): IndicatorPlan {
            $plan = IndicatorPlan::query()->lockForUpdate()->firstOrNew([
                'indicator_id' => $attributes['indicator_id'],
                'financial_year_id' => $attributes['financial_year_id'],
            ]);
            if ($plan->exists && ! in_array($plan->status, ['draft','returned'], true)) {
                throw ValidationException::withMessages(['plan' => 'A submitted or approved plan cannot be edited.']);
            }
            $plan->fill($attributes);
            $plan->version = $plan->exists ? $plan->version + 1 : 1;
            $plan->status = 'draft';
            if (! $plan->exists) $plan->created_by = $actor->id;
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
                    $this->expectStatus($plan, ['draft','returned']);
                    $changes += ['status' => 'pending_institution', 'submitted_by' => $actor->id, 'submitted_at' => now(), 'confirmed_by' => null, 'confirmed_at' => null];
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
                    // Keep existing dashboard/reporting targets and baselines consistent
                    // with the newly approved annual implementation plan.
                    \App\Models\IndicatorTarget::query()->updateOrCreate([
                        'indicator_id' => $plan->indicator_id,
                        'financial_year_id' => $plan->financial_year_id,
                        'reporting_period_id' => null,
                        'dimension_option_id' => null,
                    ], [
                        'target_value' => $plan->annual_target,
                        'created_by' => $actor->id,
                    ]);
                    if ($plan->baseline_value !== null) {
                        \App\Models\IndicatorBaseline::query()->updateOrCreate([
                            'indicator_id' => $plan->indicator_id,
                            'financial_year_id' => $plan->financial_year_id,
                        ], [
                            'baseline_value' => $plan->baseline_value,
                            'baseline_date' => $plan->baseline_year ? $plan->baseline_year.'-07-01' : null,
                            'organization_id' => $plan->lead_organization_id,
                            'created_by' => $actor->id,
                        ]);
                    }
                    break;
                case 'return':
                    if ($plan->status === 'pending_institution') {
                        $this->authorizeInstitution($actor, (int) $plan->lead_organization_id, (int) $plan->submitted_by);
                    } else {
                        $this->authorizeThematic($actor, (int) $plan->indicator->thematic_area_id);
                    }
                    $this->expectStatus($plan, ['pending_institution','pending_thematic']);
                    if (blank($comment)) throw ValidationException::withMessages(['comment' => 'A return reason is required.']);
                    $changes += ['status' => 'returned', 'confirmed_by' => null, 'confirmed_at' => null];
                    break;
                default:
                    throw ValidationException::withMessages(['action' => 'Unknown plan action.']);
            }
            $plan->update($changes);
            return $plan->fresh();
        });
    }

    public function authorizeThematic(User $actor, int $areaId): void
    {
        if ($actor->hasRole('Super Admin')) return;
        if (! $actor->can('indicator.set-target') && ! $actor->hasRole('Project Manager')) {
            throw new AuthorizationException('You cannot manage plans.');
        }
        abort_unless(in_array($areaId, $actor->visibleThematicAreaIds(), true), 403);
    }

    public function authorizeInstitution(User $actor, int $organizationId, int $submitterId): void
    {
        if ($actor->hasRole('Super Admin')) return;
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
