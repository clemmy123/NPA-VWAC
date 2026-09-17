<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\Organization;
use App\Models\User;
use App\Support\AdminLocationLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IndicatorDataAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = IndicatorDataAssignment::query()->with(['indicator', 'user', 'organization']);
        $this->limitToActorScope($query, $request->user());

        $assignments = $query
            ->when($request->integer('indicator_id'), fn ($query, $indicatorId) => $query->where('indicator_id', $indicatorId))
            ->when($request->integer('user_id'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return view('indicator-data-assignments.index', ['assignments' => $assignments]);
    }

    public function create(Request $request): View
    {
        return view('indicator-data-assignments.create', $this->formData($request->user()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->authorizeDelegation($request->user(), $data);

        IndicatorDataAssignment::create($data);

        return $this->redirectBackOrTo($request, 'indicator-data-assignments.index')->with('success', 'Assignment created.');
    }

    public function edit(Request $request, IndicatorDataAssignment $indicatorDataAssignment): View
    {
        $this->authorizeExistingAssignment($request->user(), $indicatorDataAssignment);

        return view('indicator-data-assignments.edit', [
            'assignment' => $indicatorDataAssignment,
            'locationAncestorChain' => $this->locationAncestorChain(
                $indicatorDataAssignment->location_level,
                $indicatorDataAssignment->location_id ? (int) $indicatorDataAssignment->location_id : null,
            ),
        ] + $this->formData($request->user()));
    }

    public function update(Request $request, IndicatorDataAssignment $indicatorDataAssignment): RedirectResponse
    {
        $this->authorizeExistingAssignment($request->user(), $indicatorDataAssignment);
        $data = $this->validated($request, $indicatorDataAssignment);
        $this->authorizeDelegation($request->user(), $data);

        $indicatorDataAssignment->update($data);

        return $this->redirectBackOrTo($request, 'indicator-data-assignments.index')->with('success', 'Assignment updated.');
    }

    public function destroy(Request $request, IndicatorDataAssignment $indicatorDataAssignment): RedirectResponse
    {
        $this->authorizeExistingAssignment($request->user(), $indicatorDataAssignment);
        $indicatorDataAssignment->delete();

        return $this->redirectBackOrTo($request, 'indicator-data-assignments.index')->with('success', 'Assignment deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(User $actor): array
    {
        $ownAssignments = $this->ownActiveAssignments($actor)->with('indicator')->get();
        $delegatedCollector = $actor->hasRole('Data Entry User') && ! $actor->hasRole('Super Admin');
        $indicatorQuery = Indicator::query()->orderBy('name');

        if ($delegatedCollector) {
            $indicatorQuery->whereIn('id', $ownAssignments->pluck('indicator_id'));
        } elseif ($actor->hasRole('Thematic Manager')) {
            $areaIds = $actor->assignedThematicAreaIds();
            if ($areaIds !== []) {
                $indicatorQuery->whereIn('thematic_area_id', $areaIds);
            }
        }

        $delegationScopes = $ownAssignments->filter(fn (IndicatorDataAssignment $assignment): bool =>
            $assignment->location_level !== null && $assignment->location_id !== null
        )->groupBy('indicator_id')->map(fn ($assignments) => $assignments->map(function (IndicatorDataAssignment $assignment): array {
            $chain = AdminLocationLevel::ancestorChain($assignment->location_level, (int) $assignment->location_id);

            return [
                'level' => $assignment->location_level,
                'id' => (int) $assignment->location_id,
                'organization_id' => $assignment->organization_id,
                'child_levels' => AdminLocationLevel::delegationChildLevels($assignment->location_level),
                'ancestor_chain' => $chain,
                'label' => collect($chain)->reverse()->pluck('name')->implode(' / '),
            ];
        })->values())->all();

        return [
            'indicators' => $indicatorQuery->get(),
            'users' => User::query()->role('Data Entry User')->where('id', '!=', $actor->id)->where('status', 'active')->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'locationLevels' => AdminLocationLevel::levels(),
            'delegatedCollector' => $delegatedCollector,
            'delegationScopes' => $delegationScopes,
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?IndicatorDataAssignment $assignment = null): array
    {
        $data = $request->validate([
            'indicator_id' => ['required', 'integer', 'exists:indicators,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'location_level' => ['nullable', 'string', 'in:'.implode(',', AdminLocationLevel::levels()), 'required_with:location_id'],
            'location_id' => ['nullable', 'integer', 'required_with:location_level'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (($assignment === null || (int) $assignment->user_id !== (int) $data['user_id'])
            && ! User::query()->findOrFail($data['user_id'])->hasRole('Data Entry User')) {
            throw ValidationException::withMessages(['user_id' => 'Assignments can only be given to a Data Entry User.']);
        }

        if (isset($data['location_level'], $data['location_id'])
            && ! AdminLocationLevel::exists($data['location_level'], (int) $data['location_id'])) {
            throw ValidationException::withMessages(['location_id' => 'The selected location is invalid for this level.']);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function authorizeDelegation(User $actor, array $data): void
    {
        if ($actor->hasRole('Super Admin')) {
            return;
        }

        if ($actor->hasRole('Thematic Manager')) {
            $areaIds = $actor->assignedThematicAreaIds();
            abort_if($areaIds !== [] && ! Indicator::query()->whereKey($data['indicator_id'])->whereIn('thematic_area_id', $areaIds)->exists(), 403);

            return;
        }

        abort_unless($actor->hasRole('Data Entry User'), 403);
        abort_if((int) $data['user_id'] === $actor->id, 403);
        abort_unless(isset($data['location_level'], $data['location_id']), 403);

        $permitted = $this->ownActiveAssignments($actor)
            ->where('indicator_id', $data['indicator_id'])
            ->get()
            ->contains(function (IndicatorDataAssignment $scope) use ($data): bool {
                if ($scope->location_level === null || $scope->location_id === null) {
                    return false;
                }

                $sameOrganization = $scope->organization_id === null
                    || (int) ($data['organization_id'] ?? 0) === (int) $scope->organization_id;

                return $sameOrganization
                    && in_array($data['location_level'], AdminLocationLevel::delegationChildLevels($scope->location_level), true)
                    && AdminLocationLevel::isWithin($data['location_level'], (int) $data['location_id'], $scope->location_level, (int) $scope->location_id);
            });

        abort_unless($permitted, 403);
    }

    private function authorizeExistingAssignment(User $actor, IndicatorDataAssignment $assignment): void
    {
        if ($actor->hasRole('Super Admin')) {
            return;
        }

        if ($actor->hasRole('Thematic Manager')) {
            $areaIds = $actor->assignedThematicAreaIds();
            abort_if($areaIds !== [] && ! Indicator::query()->whereKey($assignment->indicator_id)->whereIn('thematic_area_id', $areaIds)->exists(), 403);

            return;
        }

        abort_if($assignment->user_id === $actor->id, 403);
        $query = IndicatorDataAssignment::query()->whereKey($assignment->id);
        $this->limitToActorScope($query, $actor);
        abort_unless($query->exists(), 403);
    }

    private function limitToActorScope(Builder $query, User $actor): void
    {
        if ($actor->hasRole('Super Admin')) {
            return;
        }

        if ($actor->hasRole('Thematic Manager')) {
            $areaIds = $actor->assignedThematicAreaIds();
            if ($areaIds !== []) {
                $query->whereHas('indicator', fn (Builder $indicator) => $indicator->whereIn('thematic_area_id', $areaIds));
            }

            return;
        }

        $scopes = $this->ownActiveAssignments($actor)->get();
        $candidateLocations = IndicatorDataAssignment::query()
            ->whereNotNull('location_level')->whereNotNull('location_id')
            ->get(['indicator_id', 'location_level', 'location_id']);
        $query->where('user_id', '!=', $actor->id)->where(function (Builder $visible) use ($scopes, $candidateLocations): void {
            foreach ($scopes as $scope) {
                if ($scope->location_level === null || $scope->location_id === null) {
                    continue;
                }

                $descendantIds = $candidateLocations
                    ->where('indicator_id', $scope->indicator_id)
                    ->map(fn (IndicatorDataAssignment $candidate) => [$candidate->location_level, (int) $candidate->location_id])
                    ->unique(fn (array $location) => $location[0].':'.$location[1])
                    ->filter(fn (array $location) => AdminLocationLevel::isWithin($location[0], $location[1], $scope->location_level, (int) $scope->location_id));

                $visible->orWhere(function (Builder $within) use ($scope, $descendantIds): void {
                    $within->where('indicator_id', $scope->indicator_id)
                        ->where(function (Builder $locations) use ($descendantIds): void {
                            foreach ($descendantIds as [$level, $id]) {
                                $locations->orWhere(fn (Builder $location) => $location->where('location_level', $level)->where('location_id', $id));
                            }
                        });
                });
            }
        });
    }

    private function ownActiveAssignments(User $actor): Builder
    {
        return IndicatorDataAssignment::query()->where('is_active', true)->where(function (Builder $query) use ($actor): void {
            $query->where('user_id', $actor->id);
            if ($actor->organization_id !== null) {
                $query->orWhere('organization_id', $actor->organization_id);
            }
        });
    }
}
