<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use App\Models\Project;
use App\Services\IndicatorPerformanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly IndicatorPerformanceService $performanceService) {}

    public function index(Request $request): View
    {
        $financialYears = FinancialYear::query()->orderByDesc('start_date')->get();

        $selectedFinancialYear = $financialYears->firstWhere('id', $request->integer('financial_year_id'))
            ?? $financialYears->firstWhere('is_current', true)
            ?? $financialYears->first();

        $projects = $this->scopedProjects($request)
            ->with(['thematicAreas' => function (HasMany $query) use ($request): void {
                if (! $request->user()->hasRole('Super Admin')) {
                    $query->whereIn('id', $request->user()->visibleThematicAreaIds());
                }

                $query->with('indicators')->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $performance = [];

        if ($selectedFinancialYear) {
            foreach ($projects as $project) {
                foreach ($project->thematicAreas as $thematicArea) {
                    foreach ($thematicArea->indicators as $indicator) {
                        $performance[$indicator->id] = $this->performanceService->summarize($indicator, $selectedFinancialYear);
                    }
                }
            }
        }

        return view('dashboard', [
            'financialYears' => $financialYears,
            'selectedFinancialYear' => $selectedFinancialYear,
            'projects' => $projects,
            'performance' => $performance,
        ]);
    }

    private function scopedProjects(Request $request): Builder
    {
        $query = Project::query();

        if (! $request->user()->hasRole('Super Admin')) {
            $query->whereIn('id', $request->user()->assignedProjectIds());
        }

        return $query;
    }
}
