<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportPeriod;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\Organization;
use App\Services\IndicatorPerformanceService;
use App\Support\AdminLocationLevel;
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

        $visibleAreaIds = $request->user()->hasRole('Super Admin')
            ? null
            : $this->visibleThematicAreaIds($request);
        $assignedIndicatorIds = $request->user()->assignedIndicatorIds();

        $projects = $this->visibleProjects($request)
            ->with(['thematicAreas' => function ($query) use ($visibleAreaIds, $assignedIndicatorIds): void {
                if ($visibleAreaIds !== null) {
                    $query->where(function ($scope) use ($visibleAreaIds, $assignedIndicatorIds): void {
                        $scope->whereIn('id', $visibleAreaIds)
                            ->orWhereHas('indicators', fn ($indicators) => $indicators->whereIn('id', $assignedIndicatorIds));
                    });
                }
            }, 'thematicAreas.indicators' => function ($query) use ($request, $assignedIndicatorIds): void {
                if (! $request->user()->can('indicator.view-all')) {
                    $query->whereIn('id', $assignedIndicatorIds);
                }
            }])
            ->orderBy('name')
            ->get();

        $thematicAreas = $projects->flatMap->thematicAreas
            ->when($visibleAreaIds !== null, fn (Collection $areas) => $areas->whereIn('id', $visibleAreaIds))
            ->sortBy('name')->values();
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
        $breakdown = ['type' => null, 'title' => 'Collected Data', 'labels' => [], 'values' => [], 'rows' => []];
        $progressPercent = null;

        if ($selectedIndicator && $financialYear) {
            $performance = $this->performanceService->summarize($selectedIndicator, $financialYear);
            $breakdown = $this->indicatorBreakdown($selectedIndicator, $financialYear);
            $progressPercent = $performance['achievement_percent'];
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
            'breakdown' => $breakdown,
            'progressPercent' => $progressPercent,
        ]);
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private function indicatorBreakdown(Indicator $indicator, FinancialYear $financialYear): array
    {
        $entries = IndicatorDataEntry::query()
            ->where('indicator_id', $indicator->id)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', 'approved')
            ->get();

        if ($entries->isEmpty()) {
            return ['type' => null, 'title' => 'Collected Data', 'labels' => [], 'values' => [], 'rows' => []];
        }

        $locationEntries = $entries->whereNotNull('location_level')->whereNotNull('location_id');
        if ($locationEntries->isNotEmpty()) {
            $level = $indicator->reporting_location_level
                ?: $locationEntries->groupBy('location_level')->map->count()->sortDesc()->keys()->first();
            $groups = $locationEntries->where('location_level', $level)->groupBy('location_id');
            $rows = $groups->map(function (Collection $group) use ($indicator): array {
                $entry = $group->first();
                $entryLevel = $entry->location_level;
                $locationId = (int) $entry->location_id;
                $chain = AdminLocationLevel::ancestorChain($entryLevel, $locationId);

                return [
                    'name' => AdminLocationLevel::name($entryLevel, $locationId) ?? 'Unknown location',
                    'path' => collect(AdminLocationLevel::pathToLevel($entryLevel))
                        ->map(fn (string $pathLevel): string => $chain[$pathLevel]['name'] ?? '')
                        ->filter()->implode(' / '),
                    'value' => $this->aggregateEntries($group, $indicator->aggregation_method),
                ];
            })->sortBy('path')->values();

            return [
                'type' => 'location',
                'title' => 'Data by '.ucfirst(str_replace('_', ' ', $level)),
                'labels' => $rows->pluck('name')->all(),
                'values' => $rows->pluck('value')->all(),
                'rows' => $rows->all(),
            ];
        }

        $organizationEntries = $entries->whereNotNull('organization_id');
        if ($organizationEntries->isNotEmpty()) {
            $organizations = Organization::query()->whereIn('id', $organizationEntries->pluck('organization_id'))->pluck('name', 'id');
            $rows = $organizationEntries->groupBy('organization_id')->map(fn (Collection $group, $id): array => [
                'name' => $organizations[(int) $id] ?? 'Unknown organization',
                'path' => $organizations[(int) $id] ?? 'Unknown organization',
                'value' => $this->aggregateEntries($group, $indicator->aggregation_method),
            ])->sortBy('name')->values();

            return ['type' => 'organization', 'title' => 'Data by Organization', 'labels' => $rows->pluck('name')->all(), 'values' => $rows->pluck('value')->all(), 'rows' => $rows->all()];
        }

        $activityEntries = $entries->filter(fn (IndicatorDataEntry $entry): bool => filled($entry->activity_name));
        if ($activityEntries->isNotEmpty()) {
            $rows = $activityEntries->groupBy('activity_name')->map(fn (Collection $group, $name): array => [
                'name' => (string) $name,
                'path' => (string) $name,
                'value' => $this->aggregateEntries($group, $indicator->aggregation_method),
            ])->sortBy('name')->values();

            return ['type' => 'activity', 'title' => 'Data by Activity / Workstation', 'labels' => $rows->pluck('name')->all(), 'values' => $rows->pluck('value')->all(), 'rows' => $rows->all()];
        }

        return ['type' => 'national', 'title' => 'National Collection', 'labels' => ['National'], 'values' => [$this->aggregateEntries($entries, $indicator->aggregation_method)], 'rows' => []];
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
