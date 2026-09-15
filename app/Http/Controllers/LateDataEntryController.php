<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\ReportingPeriod;
use Illuminate\View\View;

class LateDataEntryController extends Controller
{
    /**
     * Who owes data for a reporting period that has already closed but has
     * no submitted/approved entry yet — computed on the fly from
     * IndicatorDataAssignment (who's on the hook) against IndicatorDataEntry
     * (what's actually been turned in), rather than a separately tracked
     * "due" table.
     */
    public function index(): View
    {
        $financialYear = $this->currentFinancialYear();

        if (! $financialYear) {
            return view('reports.late-data-entries', [
                'financialYear' => null,
                'lateEntries' => collect(),
            ]);
        }

        $today = now()->startOfDay();

        $closedPeriods = ReportingPeriod::query()
            ->where('financial_year_id', $financialYear->id)
            ->where('end_date', '<', $today)
            ->orderBy('sequence')
            ->get();

        if ($closedPeriods->isEmpty()) {
            return view('reports.late-data-entries', [
                'financialYear' => $financialYear,
                'lateEntries' => collect(),
            ]);
        }

        $assignments = IndicatorDataAssignment::query()
            ->with(['indicator', 'user', 'organization'])
            ->where('is_active', true)
            ->get();

        $fulfilled = IndicatorDataEntry::query()
            ->whereIn('reporting_period_id', $closedPeriods->pluck('id'))
            ->whereIn('status', ['submitted', 'approved'])
            ->get(['indicator_id', 'reporting_period_id', 'entered_by', 'organization_id'])
            ->groupBy(fn (IndicatorDataEntry $entry) => $entry->indicator_id.'-'.$entry->reporting_period_id);

        $lateEntries = collect();

        foreach ($assignments as $assignment) {
            foreach ($closedPeriods as $period) {
                $entriesForSlot = $fulfilled->get($assignment->indicator_id.'-'.$period->id, collect());

                $isFulfilled = $entriesForSlot->contains(
                    fn (IndicatorDataEntry $entry) => $entry->entered_by === $assignment->user_id
                        || ($assignment->organization_id !== null && $entry->organization_id === $assignment->organization_id)
                );

                if ($isFulfilled) {
                    continue;
                }

                $lateEntries->push([
                    'indicator' => $assignment->indicator,
                    'user' => $assignment->user,
                    'organization' => $assignment->organization,
                    'period' => $period,
                    'days_late' => (int) $today->diffInDays($period->end_date),
                ]);
            }
        }

        return view('reports.late-data-entries', [
            'financialYear' => $financialYear,
            'lateEntries' => $lateEntries->sortByDesc('days_late')->values(),
        ]);
    }

    private function currentFinancialYear(): ?FinancialYear
    {
        return FinancialYear::query()->where('is_current', true)->first()
            ?? FinancialYear::query()->orderByDesc('start_date')->first();
    }
}
