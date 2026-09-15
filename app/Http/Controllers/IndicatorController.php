<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorRequest;
use App\Http\Requests\UpdateIndicatorRequest;
use App\Http\Resources\IndicatorResource;
use App\Models\DimensionOption;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\MeasurementType;
use App\Models\Organization;
use App\Models\ReportingPeriod;
use App\Models\ThematicArea;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Support\AdminLocationLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndicatorController extends Controller
{
    public const array STATUS_OPTIONS = ['draft', 'active', 'completed', 'closed'];

    /**
     * Columns that are NOT NULL with a DB-level default. The validation
     * rules mark them nullable (so JSON API clients may omit them), but a
     * null explicitly passed to Eloquent::create()/update() bypasses the
     * DB default and violates the constraint — so a null here must be
     * dropped instead of forwarded.
     */
    private const array COLUMNS_WITH_DB_DEFAULTS = [
        'collection_mode', 'aggregation_method', 'reporting_frequency', 'collection_scope',
    ];

    public function index(Request $request): JsonResponse|View
    {
        $user = $request->user();

        $indicators = Indicator::query()
            ->with(['interventions', 'thematicArea'])
            ->when($request->integer('thematic_area_id'), fn ($query, $thematicAreaId) => $query->where('thematic_area_id', $thematicAreaId))
            ->when(! $user->hasRole('Super Admin'), fn ($query) => $query->whereIn('id', $this->visibleIndicatorIds($user)))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json(IndicatorResource::collection($indicators)->response()->getData(true));
        }

        return view('indicators.index', ['indicators' => $indicators]);
    }

    public function create(Request $request): View
    {
        return view('indicators.create', $this->formData($request->user()));
    }

    public function store(StoreIndicatorRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorizeThematicArea($request->user(), (int) $request->validated('thematic_area_id'));
        $indicator = Indicator::create($this->withoutNullDefaults($request->validated()) + ['created_by' => $request->user()->id]);

        if ($request->wantsJson()) {
            return (new IndicatorResource($indicator))->response()->setStatusCode(201);
        }

        return $this->redirectBackOrTo($request, 'indicators.index')->with('success', "Indicator \"{$indicator->name}\" created.");
    }

    public function show(Request $request, Indicator $indicator): JsonResponse|View|IndicatorResource
    {
        $user = $request->user();

        $this->authorizeIndicator($user, $indicator);

        if ($request->wantsJson()) {
            return new IndicatorResource($indicator->load('interventions'));
        }

        $indicator->load('thematicArea', 'measurementType', 'unitOfMeasure');

        return view('indicators.show', [
            'indicator' => $indicator,
            'baselines' => $indicator->baselines()->with(['financialYear', 'organization'])->latest('id')->get(),
            'targets' => $indicator->targets()->with(['financialYear', 'reportingPeriod', 'dimensionOption'])->latest('id')->get(),
            'assignments' => $indicator->assignments()->with(['user', 'organization'])->latest('id')->get(),
            'allIndicators' => Indicator::query()->whereIn('id', $this->visibleIndicatorIds($user))->orderBy('name')->get(),
            'financialYears' => FinancialYear::query()->orderBy('name')->get(),
            'reportingPeriods' => ReportingPeriod::query()->orderBy('sequence')->get(),
            'dimensionOptions' => DimensionOption::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'locationLevels' => AdminLocationLevel::levels(),
        ]);
    }

    public function edit(Request $request, Indicator $indicator): View
    {
        $this->authorizeIndicator($request->user(), $indicator);

        return view('indicators.edit', ['indicator' => $indicator] + $this->formData($request->user()));
    }

    public function update(UpdateIndicatorRequest $request, Indicator $indicator): JsonResponse|RedirectResponse|IndicatorResource
    {
        $this->authorizeIndicator($request->user(), $indicator);
        if ($request->validated('thematic_area_id')) {
            $this->authorizeThematicArea($request->user(), (int) $request->validated('thematic_area_id'));
        }
        $indicator->update($this->withoutNullDefaults($request->validated()));

        if ($request->wantsJson()) {
            return new IndicatorResource($indicator);
        }

        return $this->redirectBackOrTo($request, 'indicators.index')->with('success', "Indicator \"{$indicator->name}\" updated.");
    }

    public function destroy(Request $request, Indicator $indicator): JsonResponse|RedirectResponse
    {
        $this->authorizeIndicator($request->user(), $indicator);
        $indicator->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return $this->redirectBackOrTo($request, 'indicators.index')->with('success', "Indicator \"{$indicator->name}\" deleted.");
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withoutNullDefaults(array $data): array
    {
        foreach (self::COLUMNS_WITH_DB_DEFAULTS as $column) {
            if (array_key_exists($column, $data) && $data[$column] === null) {
                unset($data[$column]);
            }
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function formData(User $user): array
    {
        $visibleAreaIds = $user->visibleThematicAreaIds();

        return [
            'statusOptions' => self::STATUS_OPTIONS,
            'thematicAreas' => ThematicArea::query()
                ->when(! $user->hasRole('Super Admin') && $visibleAreaIds !== [], fn ($query) => $query->whereIn('id', $visibleAreaIds))
                ->orderBy('name')->get(),
            'measurementTypes' => MeasurementType::query()->orderBy('name')->get(),
            'unitsOfMeasure' => UnitOfMeasure::query()->orderBy('name')->get(),
        ];
    }

    /** @return list<int> */
    private function visibleIndicatorIds(User $user): array
    {
        if ($user->hasRole('Super Admin')) {
            return Indicator::query()->pluck('id')->all();
        }

        if ($user->can('indicator.view-all')) {
            $thematicAreaIds = $user->visibleThematicAreaIds();

            return Indicator::query()
                ->when($thematicAreaIds !== [], fn ($query) => $query->whereIn('thematic_area_id', $thematicAreaIds))
                ->pluck('id')->all();
        }

        return $user->assignedIndicatorIds();
    }

    private function authorizeIndicator(User $user, Indicator $indicator): void
    {
        abort_unless($user->hasRole('Super Admin') || in_array($indicator->id, $this->visibleIndicatorIds($user), true), 403);
    }

    private function authorizeThematicArea(User $user, int $thematicAreaId): void
    {
        $visibleIds = $user->visibleThematicAreaIds();
        abort_unless($user->hasRole('Super Admin') || $visibleIds === [] || in_array($thematicAreaId, $visibleIds, true), 403);
    }
}
