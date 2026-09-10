<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorBaselineRequest;
use App\Http\Requests\UpdateIndicatorBaselineRequest;
use App\Http\Resources\IndicatorBaselineResource;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorBaseline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndicatorBaselineController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $baselines = IndicatorBaseline::query()
            ->with(['indicator', 'financialYear'])
            ->when($request->integer('indicator_id'), fn ($query, $indicatorId) => $query->where('indicator_id', $indicatorId))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json(IndicatorBaselineResource::collection($baselines)->response()->getData(true));
        }

        return view('indicator-baselines.index', ['baselines' => $baselines]);
    }

    public function create(): View
    {
        return view('indicator-baselines.create', $this->formData());
    }

    public function store(StoreIndicatorBaselineRequest $request): JsonResponse|RedirectResponse
    {
        $baseline = IndicatorBaseline::create($request->validated() + ['created_by' => $request->user()->id]);

        if ($request->wantsJson()) {
            return (new IndicatorBaselineResource($baseline))->response()->setStatusCode(201);
        }

        return redirect()->route('indicator-baselines.index')->with('success', 'Baseline created.');
    }

    public function show(IndicatorBaseline $indicatorBaseline): IndicatorBaselineResource
    {
        return new IndicatorBaselineResource($indicatorBaseline);
    }

    public function edit(IndicatorBaseline $indicatorBaseline): View
    {
        return view('indicator-baselines.edit', ['baseline' => $indicatorBaseline] + $this->formData());
    }

    public function update(UpdateIndicatorBaselineRequest $request, IndicatorBaseline $indicatorBaseline): JsonResponse|RedirectResponse|IndicatorBaselineResource
    {
        $indicatorBaseline->update($request->validated());

        if ($request->wantsJson()) {
            return new IndicatorBaselineResource($indicatorBaseline);
        }

        return redirect()->route('indicator-baselines.index')->with('success', 'Baseline updated.');
    }

    public function destroy(Request $request, IndicatorBaseline $indicatorBaseline): JsonResponse|RedirectResponse
    {
        $indicatorBaseline->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('indicator-baselines.index')->with('success', 'Baseline deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'indicators' => Indicator::query()->orderBy('name')->get(),
            'financialYears' => FinancialYear::query()->orderBy('name')->get(),
        ];
    }
}
