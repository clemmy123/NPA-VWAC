<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\IndicatorDataEntry;
use App\Models\Organization;
use App\Models\ReportingPeriod;
use App\Support\AdminLocationLevel;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WorkstationReportService
{
    /** Location levels a workstation report can be narrowed to, top to bottom. */
    public const array LOCATION_LEVELS = ['region', 'district', 'council', 'ward', 'village_mtaa'];

    private const array PENDING_STATUSES = ['submitted', 'pending_approval'];

    /**
     * Workstations registered at, or anywhere below, the chosen location.
     *
     * @param  Collection<int, Organization>  $organizations
     * @return Collection<int, Organization>
     */
    public function withinLocation(Collection $organizations, ?string $level, ?int $locationId): Collection
    {
        if ($level === null || $locationId === null) {
            return $organizations;
        }

        $chains = [];

        return $organizations->filter(function (Organization $organization) use ($level, $locationId, &$chains): bool {
            if ($organization->location_level === null || $organization->location_id === null
                || ! AdminLocationLevel::isValidLevel($organization->location_level)) {
                return false;
            }

            $key = $organization->location_level.':'.$organization->location_id;
            $chains[$key] ??= AdminLocationLevel::ancestorChain($organization->location_level, (int) $organization->location_id);

            return (int) ($chains[$key][$level]['id'] ?? 0) === $locationId;
        })->values();
    }

    /**
     * "Region / District / …" for the chosen location, limited to the levels the
     * report filter exposes.
     *
     * @param  array<string, array{id: int, name: string}>  $chain
     */
    public function locationLabel(array $chain): ?string
    {
        $label = collect(self::LOCATION_LEVELS)
            ->map(fn (string $level): ?string => $chain[$level]['name'] ?? null)
            ->filter()
            ->implode(' / ');

        return $label === '' ? null : $label;
    }

    /**
     * Side-by-side participation per workstation for the selected period. Targets
     * are set nationally, so workstations are compared on collections rather than
     * on an achievement percentage.
     *
     * @param  Collection<int, Organization>  $organizations
     * @param  list<int>  $indicatorIds
     * @return array{
     *     rows: list<array{organization: Organization, approved: int, pending: int, rejected: int, indicators_reported: int, last_approved_at: CarbonInterface|null}>,
     *     totals: array{approved: int, pending: int, rejected: int, workstations: int, reporting_workstations: int, indicators: int, indicators_reported: int}
     * }
     */
    public function comparison(
        Collection $organizations,
        array $indicatorIds,
        FinancialYear $financialYear,
        ?ReportingPeriod $reportingPeriod = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
    ): array {
        $entries = $this->scopedEntries($organizations->pluck('id')->all(), $indicatorIds, $financialYear, $reportingPeriod, $from, $to)
            ->get(['id', 'organization_id', 'indicator_id', 'status', 'entry_date', 'approved_at']);
        $entriesByOrganization = $entries->groupBy('organization_id');

        $rows = $organizations->map(function (Organization $organization) use ($entriesByOrganization): array {
            $organizationEntries = $entriesByOrganization->get($organization->id, collect());
            $approved = $organizationEntries->where('status', 'approved');

            return [
                'organization' => $organization,
                'approved' => $approved->count(),
                'pending' => $organizationEntries->whereIn('status', self::PENDING_STATUSES)->count(),
                'rejected' => $organizationEntries->where('status', 'rejected')->count(),
                'indicators_reported' => $approved->pluck('indicator_id')->unique()->count(),
                'last_approved_at' => $approved
                    ->map(fn (IndicatorDataEntry $entry): ?CarbonInterface => $entry->approved_at ?? $entry->entry_date)
                    ->filter()
                    ->sortDesc()
                    ->first(),
            ];
        })->all();

        usort($rows, function (array $left, array $right): int {
            return [$right['approved'], $right['indicators_reported'], $right['pending']]
                <=> [$left['approved'], $left['indicators_reported'], $left['pending']]
                ?: strcasecmp($left['organization']->name, $right['organization']->name);
        });

        $approvedEntries = $entries->where('status', 'approved');

        return [
            'rows' => $rows,
            'totals' => [
                'approved' => $approvedEntries->count(),
                'pending' => $entries->whereIn('status', self::PENDING_STATUSES)->count(),
                'rejected' => $entries->where('status', 'rejected')->count(),
                'workstations' => count($rows),
                'reporting_workstations' => count(array_filter($rows, fn (array $row): bool => $row['approved'] > 0)),
                'indicators' => count($indicatorIds),
                'indicators_reported' => $approvedEntries->pluck('indicator_id')->unique()->count(),
            ],
        ];
    }

    /**
     * Approved and pending collections per month across the financial year, up to
     * the current month.
     *
     * @param  Collection<int, Organization>  $organizations
     * @param  list<int>  $indicatorIds
     * @return array{labels: list<string>, approved: list<int>, pending: list<int>}
     */
    public function monthlyTrend(Collection $organizations, array $indicatorIds, FinancialYear $financialYear): array
    {
        $start = Carbon::parse($financialYear->start_date)->startOfMonth();
        $end = Carbon::parse($financialYear->end_date)->startOfMonth()->min(now()->startOfMonth());

        if ($start->greaterThan($end)) {
            return ['labels' => [], 'approved' => [], 'pending' => []];
        }

        $entries = $this->scopedEntries($organizations->pluck('id')->all(), $indicatorIds, $financialYear)
            ->get(['id', 'status', 'entry_date']);
        $byMonth = $entries->groupBy(fn (IndicatorDataEntry $entry): string => $entry->entry_date?->format('Y-m') ?? '');

        $labels = [];
        $approved = [];
        $pending = [];

        for ($month = $start->copy(); $month->lessThanOrEqualTo($end); $month->addMonth()) {
            $monthEntries = $byMonth->get($month->format('Y-m'), collect());
            $labels[] = $month->translatedFormat('M Y');
            $approved[] = $monthEntries->where('status', 'approved')->count();
            $pending[] = $monthEntries->whereIn('status', self::PENDING_STATUSES)->count();
        }

        return ['labels' => $labels, 'approved' => $approved, 'pending' => $pending];
    }

    /**
     * @param  list<int>  $organizationIds
     * @param  list<int>  $indicatorIds
     * @return Builder<IndicatorDataEntry>
     */
    private function scopedEntries(
        array $organizationIds,
        array $indicatorIds,
        FinancialYear $financialYear,
        ?ReportingPeriod $reportingPeriod = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
    ): Builder {
        $query = IndicatorDataEntry::query()
            ->whereIn('organization_id', $organizationIds)
            ->whereIn('indicator_id', $indicatorIds)
            ->where('financial_year_id', $financialYear->id)
            ->where('status', '!=', 'draft');

        if ($reportingPeriod !== null) {
            $query->where(function (Builder $inner) use ($reportingPeriod): void {
                $inner->where('reporting_period_id', $reportingPeriod->id)
                    ->orWhere(function (Builder $byDate) use ($reportingPeriod): void {
                        $byDate->whereNull('reporting_period_id')
                            ->whereDate('entry_date', '>=', $reportingPeriod->start_date)
                            ->whereDate('entry_date', '<=', $reportingPeriod->end_date);
                    });
            });
        }

        if ($from !== null && $to !== null) {
            $query->whereDate('entry_date', '>=', $from->toDateString())
                ->whereDate('entry_date', '<=', $to->toDateString());
        }

        return $query;
    }
}
