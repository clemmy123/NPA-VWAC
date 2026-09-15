<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportPeriod;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\Region;
use App\Services\IndicatorPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesReportPeriod;

    public function __construct(private readonly IndicatorPerformanceService $performanceService) {}

    public function index(Request $request): View
    {
        $data = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'thematic_area_id' => ['nullable', 'integer', 'exists:thematic_areas,id'],
            'indicator_id' => ['nullable', 'integer', 'exists:indicators,id'],
        ]);

        $projects = $this->visibleProjects($request)
            ->with(['thematicAreas.indicators'])
            ->orderBy('name')
            ->get();

        $thematicAreas = $projects->flatMap->thematicAreas->sortBy('name')->values();
        $indicators = $thematicAreas->flatMap->indicators->sortBy('code')->values();

        $selectedProject = $projects->firstWhere('id', (int) ($data['project_id'] ?? 0))
            ?? $projects->first();

        $areasForPlan = $selectedProject
            ? $thematicAreas->where('project_id', $selectedProject->id)->values()
            : $thematicAreas;

        $selectedThematicArea = $areasForPlan->firstWhere('id', (int) ($data['thematic_area_id'] ?? 0))
            ?? $areasForPlan->first();

        $indicatorsForArea = $selectedThematicArea
            ? $indicators->where('thematic_area_id', $selectedThematicArea->id)->values()
            : $indicators;

        $selectedIndicator = $indicatorsForArea->firstWhere('id', (int) ($data['indicator_id'] ?? 0))
            ?? $indicatorsForArea->first();

        $financialYear = FinancialYear::query()->where('is_current', true)->first()
            ?? FinancialYear::query()->orderByDesc('start_date')->first();

        $performance = null;
        $regionChart = ['labels' => [], 'values' => []];
        $progressPercent = null;

        if ($selectedIndicator && $financialYear) {
            $performance = $this->performanceService->summarize($selectedIndicator, $financialYear);
            $regionChart = $this->regionChart($selectedIndicator, $financialYear, $performance['target_value']);
            $progressPercent = $regionChart['values'] !== []
                ? round(array_sum($regionChart['values']) / count($regionChart['values']), 0)
                : $performance['achievement_percent'];
        }

        return view('dashboard', [
            'projects' => $projects,
            'thematicAreas' => $thematicAreas,
            'indicators' => $indicators,
            'selectedProject' => $selectedProject,
            'selectedThematicArea' => $selectedThematicArea,
            'selectedIndicator' => $selectedIndicator,
            'financialYear' => $financialYear,
            'performance' => $performance,
            'regionChart' => $regionChart,
            'progressPercent' => $progressPercent,
        ]);
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private function regionChart(Indicator $indicator, FinancialYear $financialYear, ?float $targetValue): array
    {
        $groups = IndicatorDataEntry::query()
            ->where('indicator_id', $indicator->id)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', 'approved')
            ->where('location_level', 'region')
            ->whereNotNull('location_id')
            ->get()
            ->groupBy('location_id');

        if ($groups->isEmpty() || $targetValue === null || $targetValue <= 0) {
            return ['labels' => [], 'values' => []];
        }

        $regions = Region::query()
            ->whereIn('region_id', $groups->keys())
            ->get()
            ->keyBy('region_id');

        $labels = [];
        $values = [];
        $rows = [];

        foreach ($groups as $locationId => $entries) {
            $region = $regions->get((int) $locationId);

            if ($region === null) {
                continue;
            }

            $actual = $this->aggregateEntries($entries, $indicator->aggregation_method);
            $rows[] = [
                'name' => $region->name,
                'value' => round(($actual / $targetValue) * 100, 1),
            ];
        }

        $preferred = ['Dodoma', 'Arusha', 'Kagera', 'Kilimanjaro', 'Manyara'];
        usort($rows, function (array $left, array $right) use ($preferred): int {
            $leftIndex = array_search($left['name'], $preferred, true);
            $rightIndex = array_search($right['name'], $preferred, true);
            $leftIndex = $leftIndex === false ? 1000 : $leftIndex;
            $rightIndex = $rightIndex === false ? 1000 : $rightIndex;

            return $leftIndex <=> $rightIndex ?: strcmp($left['name'], $right['name']);
        });

        foreach ($rows as $row) {
            $labels[] = $row['name'];
            $values[] = $row['value'];
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @param  Collection<int, IndicatorDataEntry>  $entries
     */
    private function aggregateEntries(Collection $entries, ?string $method): float
    {
        return (float) match ($method) {
            'average' => $entries->avg('actual_value'),
            'latest' => $entries->sortByDesc('entry_date')->first()?->actual_value ?? 0,
            'count' => $entries->count(),
            'max' => $entries->max('actual_value'),
            'min' => $entries->min('actual_value'),
            default => $entries->sum('actual_value'),
        };
    }
}
