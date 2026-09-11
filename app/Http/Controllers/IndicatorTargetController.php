<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorTargetRequest;
use App\Http\Requests\UpdateIndicatorTargetRequest;
use App\Http\Resources\IndicatorTargetResource;
use App\Models\DimensionOption;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorTarget;
use App\Models\ReportingPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndicatorTargetController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $targets = IndicatorTarget::query()
            ->with(['indicator', 'financialYear', 'reportingPeriod', 'dimensionOption'])
            ->when($request->integer('indicator_id'), fn ($query, $indicatorId) => $query->where('indicator_id', $indicatorId))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json(IndicatorTargetResource::collection($targets)->response()->getData(true));
        }

        return view('indicator-targets.index', ['targets' => $targets]);
    }

    public function create(): View
    {
        return view('indicator-targets.create', $this->formData());
    }

    public function store(StoreIndicatorTargetRequest $request): JsonResponse|RedirectResponse
    {
        $target = IndicatorTarget::create($request->validated() + ['created_by' => $request->user()->id]);

        if ($request->wantsJson()) {
            return (new IndicatorTargetResource($target))->response()->setStatusCode(201);
        }

        return $this->redirectBackOrTo($request, 'indicator-targets.index')->with('success', 'Target created.');
    }

    public function show(IndicatorTarget $indicatorTarget): IndicatorTargetResource
    {
        return new IndicatorTargetResource($indicatorTarget);
    }

    public function edit(IndicatorTarget $indicatorTarget): View
    {
        return view('indicator-targets.edit', ['target' => $indicatorTarget] + $this->formData());
    }

    public function update(UpdateIndicatorTargetRequest $request, IndicatorTarget $indicatorTarget): JsonResponse|RedirectResponse|IndicatorTargetResource
    {
        $indicatorTarget->update($request->validated());

        if ($request->wantsJson()) {
            return new IndicatorTargetResource($indicatorTarget);
        }

        return $this->redirectBackOrTo($request, 'indicator-targets.index')->with('success', 'Target updated.');
    }

    public function destroy(Request $request, IndicatorTarget $indicatorTarget): JsonResponse|RedirectResponse
    {
        $indicatorTarget->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return $this->redirectBackOrTo($request, 'indicator-targets.index')->with('success', 'Target deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'indicators' => Indicator::query()->orderBy('name')->get(),
            'financialYears' => FinancialYear::query()->orderBy('name')->get(),
            'reportingPeriods' => ReportingPeriod::query()->orderBy('sequence')->get(),
            'dimensionOptions' => DimensionOption::query()->orderBy('name')->get(),
        ];
    }
}
