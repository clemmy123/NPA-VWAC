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
            ->when(! $user->can('indicator.view-all'), fn ($query) => $query->whereIn('id', $user->assignedIndicatorIds()))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json(IndicatorResource::collection($indicators)->response()->getData(true));
        }

        return view('indicators.index', ['indicators' => $indicators]);
    }

    public function create(): View
    {
        return view('indicators.create', $this->formData());
    }

    public function store(StoreIndicatorRequest $request): JsonResponse|RedirectResponse
    {
        $indicator = Indicator::create($this->withoutNullDefaults($request->validated()) + ['created_by' => $request->user()->id]);

        if ($request->wantsJson()) {
            return (new IndicatorResource($indicator))->response()->setStatusCode(201);
        }

        return $this->redirectBackOrTo($request, 'indicators.index')->with('success', "Indicator \"{$indicator->name}\" created.");
    }

    public function show(Request $request, Indicator $indicator): JsonResponse|View|IndicatorResource
    {
        $user = $request->user();

        if (! $user->can('indicator.view-all')) {
            abort_unless(in_array($indicator->id, $user->assignedIndicatorIds(), true), 403);
        }

        if ($request->wantsJson()) {
            return new IndicatorResource($indicator->load('interventions'));
        }

        $indicator->load('thematicArea', 'measurementType', 'unitOfMeasure');

        return view('indicators.show', [
            'indicator' => $indicator,
            'baselines' => $indicator->baselines()->with(['financialYear', 'organization'])->latest('id')->get(),
            'targets' => $indicator->targets()->with(['financialYear', 'reportingPeriod', 'dimensionOption'])->latest('id')->get(),
            'assignments' => $indicator->assignments()->with(['user', 'organization'])->latest('id')->get(),
            'allIndicators' => Indicator::query()->orderBy('name')->get(),
            'financialYears' => FinancialYear::query()->orderBy('name')->get(),
            'reportingPeriods' => ReportingPeriod::query()->orderBy('sequence')->get(),
            'dimensionOptions' => DimensionOption::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'locationLevels' => AdminLocationLevel::levels(),
        ]);
    }

    public function edit(Indicator $indicator): View
    {
        return view('indicators.edit', ['indicator' => $indicator] + $this->formData());
    }

    public function update(UpdateIndicatorRequest $request, Indicator $indicator): JsonResponse|RedirectResponse|IndicatorResource
    {
        $indicator->update($this->withoutNullDefaults($request->validated()));

        if ($request->wantsJson()) {
            return new IndicatorResource($indicator);
        }

        return $this->redirectBackOrTo($request, 'indicators.index')->with('success', "Indicator \"{$indicator->name}\" updated.");
    }

    public function destroy(Request $request, Indicator $indicator): JsonResponse|RedirectResponse
    {
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
    private function formData(): array
    {
        return [
            'statusOptions' => self::STATUS_OPTIONS,
            'thematicAreas' => ThematicArea::query()->orderBy('name')->get(),
            'measurementTypes' => MeasurementType::query()->orderBy('name')->get(),
            'unitsOfMeasure' => UnitOfMeasure::query()->orderBy('name')->get(),
        ];
    }
}
