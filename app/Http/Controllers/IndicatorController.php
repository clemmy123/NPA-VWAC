<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorRequest;
use App\Http\Requests\UpdateIndicatorRequest;
use App\Http\Resources\IndicatorResource;
use App\Models\Indicator;
use App\Models\MeasurementType;
use App\Models\ThematicArea;
use App\Models\UnitOfMeasure;
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
        $indicators = Indicator::query()
            ->with(['interventions', 'thematicArea'])
            ->when($request->integer('thematic_area_id'), fn ($query, $thematicAreaId) => $query->where('thematic_area_id', $thematicAreaId))
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

        return redirect()->route('indicators.index')->with('success', "Indicator \"{$indicator->name}\" created.");
    }

    public function show(Indicator $indicator): IndicatorResource
    {
        return new IndicatorResource($indicator->load('interventions'));
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

        return redirect()->route('indicators.index')->with('success', "Indicator \"{$indicator->name}\" updated.");
    }

    public function destroy(Request $request, Indicator $indicator): JsonResponse|RedirectResponse
    {
        $indicator->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('indicators.index')->with('success', "Indicator \"{$indicator->name}\" deleted.");
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
