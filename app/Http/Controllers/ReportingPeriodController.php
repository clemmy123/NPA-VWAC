<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use App\Models\ReportingPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportingPeriodController extends Controller
{
    public const array PERIOD_TYPES = ['week', 'month', 'quarter', 'semi_annual', 'annual'];

    public function index(Request $request): View
    {
        $reportingPeriods = ReportingPeriod::query()
            ->with('financialYear')
            ->when($request->integer('financial_year_id'), fn ($query, $financialYearId) => $query->where('financial_year_id', $financialYearId))
            ->orderByDesc('financial_year_id')
            ->orderBy('sequence')
            ->paginate($request->integer('per_page', 15));

        return view('reporting-periods.index', [
            'reportingPeriods' => $reportingPeriods,
            'financialYears' => FinancialYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function create(): View
    {
        return view('reporting-periods.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $reportingPeriod = ReportingPeriod::create($data);

        return redirect()->route('reporting-periods.index')->with('success', __('Reporting period ":name" created.', ['name' => $reportingPeriod->name]));
    }

    public function edit(ReportingPeriod $reportingPeriod): View
    {
        return view('reporting-periods.edit', ['reportingPeriod' => $reportingPeriod] + $this->formData());
    }

    public function update(Request $request, ReportingPeriod $reportingPeriod): RedirectResponse
    {
        $data = $this->validated($request, $reportingPeriod);

        $reportingPeriod->update($data);

        return redirect()->route('reporting-periods.index')->with('success', __('Reporting period ":name" updated.', ['name' => $reportingPeriod->name]));
    }

    public function destroy(ReportingPeriod $reportingPeriod): RedirectResponse
    {
        $reportingPeriod->delete();

        return redirect()->route('reporting-periods.index')->with('success', __('Reporting period ":name" deleted.', ['name' => $reportingPeriod->name]));
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'financialYears' => FinancialYear::query()->orderByDesc('start_date')->get(),
            'periodTypes' => self::PERIOD_TYPES,
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?ReportingPeriod $reportingPeriod = null): array
    {
        $data = $request->validate([
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'period_type' => ['required', 'string', 'in:'.implode(',', self::PERIOD_TYPES)],
            'sequence' => ['required', 'integer', 'min:1', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
