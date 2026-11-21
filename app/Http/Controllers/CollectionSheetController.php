<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorPlan;
use App\Models\Organization;
use App\Models\ReportingPeriod;
use App\Models\ThematicArea;
use App\Models\User;
use App\Services\IndicatorDataEntryService;
use App\Services\IndicatorReviewService;
use App\Support\AdminLocationLevel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The data-collection counterpart of the Plan Builder: the approved annual
 * plan laid out read-only on the left, the implementation columns (achieved,
 * status, money released and spent, evidence) editable on the right. Every row
 * is a regular IndicatorDataEntry, so the existing review / institution-confirm
 * / approval workflow and the full entry form keep working unchanged.
 *
 * An indicator that requires a location gets one row per location (plus a blank
 * row to add the next one); every other indicator has a single row per period.
 */
class CollectionSheetController extends Controller
{
    /** Indicator reporting frequency => reporting period type. */
    private const array PERIOD_TYPES = [
        'weekly' => 'week', 'monthly' => 'month', 'quarterly' => 'quarter',
        'biannual' => 'semi_annual', 'annual' => 'annual',
    ];

    public function __construct(
        private readonly IndicatorDataEntryService $entries,
        private readonly IndicatorReviewService $reviews,
    ) {}

    public function index(Request $request): View
    {
        $thematicAreas = $this->accessibleThematicAreas($request->user())->load('project');
        $financialYear = $this->defaultFinancialYear();

        $thematicAreas->each(function (ThematicArea $area) use ($request, $financialYear): void {
            $indicatorIds = $this->collectableIndicators($request->user(), $area)->pluck('id');
            $area->collectable_count = $indicatorIds->count();
            $area->approved_plan_count = $financialYear
                ? IndicatorPlan::query()->whereIn('indicator_id', $indicatorIds)
                    ->where('financial_year_id', $financialYear->id)->where('status', 'approved')->count()
                : 0;
        });

        return view('collection-sheet.index', ['thematicAreas' => $thematicAreas, 'financialYear' => $financialYear]);
    }

    public function show(Request $request, ThematicArea $thematicArea): View
    {
        $user = $request->user();
        $this->authorizeArea($user, $thematicArea);

        $financialYears = FinancialYear::query()->orderByDesc('start_date')->get();
        $financialYear = $financialYears->firstWhere('id', $request->integer('financial_year_id')) ?? $this->defaultFinancialYear();

        $periods = $financialYear
            ? ReportingPeriod::query()->where('financial_year_id', $financialYear->id)->where('is_active', true)
                ->orderBy('start_date')->orderBy('period_type')->orderBy('sequence')->get()
            : collect();

        $indicators = $this->collectableIndicators($user, $thematicArea);
        $period = $this->selectedPeriod($request, $periods, $indicators);

        $plans = $financialYear
            ? IndicatorPlan::query()->whereIn('indicator_id', $indicators->pluck('id'))
                ->where('financial_year_id', $financialYear->id)->where('status', 'approved')->get()->keyBy('indicator_id')
            : collect();

        $configuredTypes = $periods->pluck('period_type')->unique()->all();
        $hiddenCount = 0;
        $visible = collect();

        foreach ($indicators as $indicator) {
            $type = self::PERIOD_TYPES[$indicator->reporting_frequency] ?? null;
            $configured = $type !== null && in_array($type, $configuredTypes, true);

            if ($configured && $period?->period_type !== $type) {
                $hiddenCount++;

                continue;
            }

            $visible->push(['indicator' => $indicator, 'periodId' => $configured ? $period?->id : null]);
        }

        $entries = $financialYear && $visible->isNotEmpty()
            ? IndicatorDataEntry::query()->withCount('media')->with('media')
                ->where('financial_year_id', $financialYear->id)
                ->whereIn('indicator_id', $visible->pluck('indicator.id'))
                ->where(fn ($query) => $query->where('entered_by', $user->id)->orWhereNotIn('status', ['draft', 'rejected']))
                ->orderByRaw('entered_by = ? desc', [$user->id])->orderBy('id')
                ->get()
            : collect();

        $rows = collect();
        foreach ($visible as $item) {
            $indicator = $item['indicator'];
            $plan = $plans->get($indicator->id);
            $forPeriod = $entries->filter(fn (IndicatorDataEntry $entry) => $entry->indicator_id === $indicator->id
                && $entry->reporting_period_id === $item['periodId']);

            $matching = $indicator->requires_location
                ? $forPeriod->whereNotNull('location_id')->sortBy('id')->values()
                : $forPeriod->whereNull('location_id')->take(1)->values();

            // Location indicators always end with a blank row for the next location.
            $slots = $matching->all();
            if ($indicator->requires_location || $matching->isEmpty()) {
                $slots[] = null;
            }

            foreach ($slots as $entry) {
                $rows->push([
                    'indicator' => $indicator,
                    'plan' => $plan,
                    'periodId' => $item['periodId'],
                    'entry' => $entry,
                    'missing' => $entry ? $this->missingFields($entry, $indicator) : [],
                    'editable' => $this->rowIsEditable($user, $plan, $entry),
                    'locationLabel' => $entry?->location_id ? AdminLocationLevel::name($entry->location_level, (int) $entry->location_id) : null,
                    'files' => $entry ? $this->fileList($entry, $thematicArea->id) : [],
                ]);
            }
        }

        return view('collection-sheet.show', [
            'thematicArea' => $thematicArea->load('project'),
            'rows' => $rows,
            'hiddenCount' => $hiddenCount,
            'financialYears' => $financialYears,
            'financialYear' => $financialYear,
            'periods' => $periods,
            'period' => $period,
            'organizations' => Organization::query()->get()->keyBy('id'),
            'periodOpen' => $financialYear !== null && ($user->can('indicator-data.override-period')
                || ($financialYear->start_date->lte(now()) && ($period === null || $period->start_date->lte(now())))),
            'canSubmit' => $user->can('indicator-data.submit'),
        ]);
    }

    /**
     * Autosave one row. Lenient — a half-filled row is a draft; completeness
     * is checked when the sheet is submitted.
     */
    public function save(Request $request, ThematicArea $thematicArea, Indicator $indicator): JsonResponse
    {
        $user = $request->user();
        $this->authorizeRowRequest($user, $thematicArea, $indicator);

        $limits = $indicator->valueLimits();
        $valueRules = ['nullable', 'numeric', 'min:0'];
        if ($limits['max'] !== null) {
            $valueRules[] = 'max:'.$limits['max'];
        }
        if ($limits['decimals'] === 0) {
            $valueRules[] = 'integer';
        } elseif ($limits['decimals'] !== null) {
            $valueRules[] = 'decimal:0,'.$limits['decimals'];
        }

        $data = $request->validate($this->contextRules() + [
            'actual_value' => $valueRules,
            'actual_text' => ['nullable', 'string', 'max:5000'],
            'implementation_status' => ['nullable', 'string', 'max:5000'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'domestic_released' => ['nullable', 'numeric', 'min:0'],
            'external_released' => ['nullable', 'numeric', 'min:0'],
            'domestic_spent' => ['nullable', 'numeric', 'min:0'],
            'external_spent' => ['nullable', 'numeric', 'min:0'],
        ]);

        $attributes = collect($data)->only([
            'actual_value', 'actual_text', 'implementation_status', 'remarks',
            'domestic_released', 'external_released', 'domestic_spent', 'external_spent',
        ])->all();

        $entry = $this->obtainEntry($request, $user, $indicator, $attributes);

        return response()->json(['data' => $this->rowState($entry, $indicator)]);
    }

    public function uploadEvidence(Request $request, ThematicArea $thematicArea, Indicator $indicator): JsonResponse
    {
        $user = $request->user();
        $this->authorizeRowRequest($user, $thematicArea, $indicator);

        $request->validate($this->contextRules() + [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ], [
            'files.*.mimes' => __('Only PDF, Word, Excel and image (JPG/PNG) files can be attached.'),
            'files.*.max' => __('Each file must be 10 MB or smaller.'),
        ]);

        $entry = $this->obtainEntry($request, $user, $indicator, [], $request->file('files', []));

        return response()->json(['data' => $this->rowState($entry, $indicator)]);
    }

    public function destroyEvidence(Request $request, ThematicArea $thematicArea, IndicatorDataEntry $indicatorDataEntry, Media $media): JsonResponse
    {
        $user = $request->user();
        $this->authorizeArea($user, $thematicArea);
        abort_unless((int) $indicatorDataEntry->indicator()->value('thematic_area_id') === $thematicArea->id, 404);
        abort_unless($indicatorDataEntry->entered_by === $user->id || $user->hasRole('Super Admin'), 403);

        $this->entries->removeEvidence($indicatorDataEntry, $media);

        return response()->json(['data' => $this->rowState($indicatorDataEntry->fresh(), $indicatorDataEntry->indicator)]);
    }

    /** Single-box location search for the indicator's reporting level. */
    public function locations(Request $request, ThematicArea $thematicArea, Indicator $indicator): JsonResponse
    {
        $user = $request->user();
        $this->authorizeArea($user, $thematicArea);
        abort_unless($indicator->thematic_area_id === $thematicArea->id && $indicator->requires_location, 404);
        abort_if(blank($indicator->reporting_location_level), 422, __('This indicator has no reporting location level configured.'));

        $scopes = $this->locationScopes($user, $indicator);

        $results = collect(AdminLocationLevel::search($indicator->reporting_location_level, $request->string('q')->toString(), 60))
            ->when($scopes !== null, fn (Collection $results) => $results->filter(
                fn (array $result) => collect($scopes)->contains(
                    fn (IndicatorDataAssignment $scope) => AdminLocationLevel::isWithin($indicator->reporting_location_level, $result['id'], $scope->location_level, (int) $scope->location_id)
                )
            ))
            ->take(30)->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Submit every draft/returned row of the selected period. Rows that are not
     * complete are reported and left as drafts; the rest go to review.
     */
    public function submit(Request $request, ThematicArea $thematicArea): JsonResponse
    {
        $user = $request->user();
        $this->authorizeArea($user, $thematicArea);
        abort_unless($user->can('indicator-data.submit'), 403);

        $data = $request->validate([
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'reporting_period_id' => ['nullable', 'integer', 'exists:reporting_periods,id'],
        ]);

        $indicators = $this->collectableIndicators($user, $thematicArea)->keyBy('id');
        $entries = IndicatorDataEntry::query()->withCount('media')
            ->where('financial_year_id', $data['financial_year_id'])
            ->whereIn('indicator_id', $indicators->keys())
            ->whereIn('status', ['draft', 'rejected'])
            ->where('entered_by', $user->id)
            ->when($data['reporting_period_id'] ?? null,
                fn ($query, $periodId) => $query->where(fn ($inner) => $inner->where('reporting_period_id', $periodId)->orWhereNull('reporting_period_id')),
                fn ($query) => $query->whereNull('reporting_period_id'))
            ->get();

        $submitted = 0;
        $problems = [];

        foreach ($entries as $entry) {
            $indicator = $indicators[$entry->indicator_id];
            $label = $entry->location_id
                ? $indicator->name.' ('.AdminLocationLevel::name($entry->location_level, (int) $entry->location_id).')'
                : $indicator->name;
            $missing = $this->missingFields($entry, $indicator);

            if ($missing !== []) {
                $problems[] = $label.': '.implode(', ', $missing);

                continue;
            }

            try {
                $this->reviews->submit($entry, $user);
                $submitted++;
            } catch (ValidationException $exception) {
                $problems[] = $label.': '.collect($exception->errors())->flatten()->implode(' ');
            }
        }

        if ($submitted === 0) {
            return response()->json(['message' => $problems === []
                ? __('Nothing to submit — fill in at least one row first.')
                : __('Nothing submitted — incomplete rows:').' '.implode(' • ', $problems)], 422);
        }

        return response()->json(['data' => ['submitted' => $submitted, 'problems' => $problems]]);
    }

    /** @return array<string, list<string>> */
    private function contextRules(): array
    {
        return [
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'reporting_period_id' => ['nullable', 'integer', 'exists:reporting_periods,id'],
            'entry_id' => ['nullable', 'integer'],
            'location_id' => ['nullable', 'integer'],
        ];
    }

    private function authorizeRowRequest(User $user, ThematicArea $thematicArea, Indicator $indicator): void
    {
        $this->authorizeArea($user, $thematicArea);
        abort_unless($indicator->thematic_area_id === $thematicArea->id, 404);
        abort_unless($user->can('indicator-data.create') && $user->can('indicator-data.update'), 403);
    }

    /**
     * Finds (or creates) the entry a row writes to, then applies the attributes
     * and any evidence files. Creating needs an approved plan and an open period.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<UploadedFile>  $files
     */
    private function obtainEntry(Request $request, User $user, Indicator $indicator, array $attributes, array $files = []): IndicatorDataEntry
    {
        $financialYear = FinancialYear::query()->findOrFail($request->integer('financial_year_id'));
        $period = $request->filled('reporting_period_id') ? ReportingPeriod::query()->findOrFail($request->integer('reporting_period_id')) : null;
        $this->assertCollectionOpen($user, $financialYear, $period);

        $plan = IndicatorPlan::query()->where('indicator_id', $indicator->id)
            ->where('financial_year_id', $financialYear->id)->where('status', 'approved')->first();
        if (! $plan) {
            throw ValidationException::withMessages(['plan' => __('The plan must be opened for data collection first.')]);
        }

        $locationId = $request->filled('location_id') ? $request->integer('location_id') : null;
        $locationLevel = $indicator->requires_location ? $indicator->reporting_location_level : null;
        if ($indicator->requires_location) {
            if (blank($locationLevel)) {
                throw ValidationException::withMessages(['location' => __('This indicator has no reporting location level configured.')]);
            }
            if ($locationId !== null && ! AdminLocationLevel::exists($locationLevel, $locationId)) {
                throw ValidationException::withMessages(['location' => __('The selected location does not exist.')]);
            }
        }

        // A row can outlive its entry (deleted elsewhere, stale page): fall back to
        // the normal find-or-create instead of failing the save.
        $entry = $request->filled('entry_id')
            ? IndicatorDataEntry::query()->where('indicator_id', $indicator->id)
                ->where('financial_year_id', $financialYear->id)->find($request->integer('entry_id'))
            : null;

        if ($indicator->requires_location && $locationId !== null && (! $entry || (int) $entry->location_id !== $locationId)) {
            $taken = $this->findEntry($user, $indicator, $financialYear, $period, $locationId);
            if ($taken && $taken->id !== $entry?->id) {
                throw ValidationException::withMessages(['location' => __('That location already has a row for this period.')]);
            }
        }
        if (! $entry && ! $indicator->requires_location) {
            $entry = $this->findEntry($user, $indicator, $financialYear, $period, null);
        }

        if (array_key_exists('domestic_spent', $attributes) || array_key_exists('external_spent', $attributes)) {
            $domesticSpent = array_key_exists('domestic_spent', $attributes) ? $attributes['domestic_spent'] : $entry?->domestic_spent;
            $externalSpent = array_key_exists('external_spent', $attributes) ? $attributes['external_spent'] : $entry?->external_spent;
            $attributes['budget_used'] = $domesticSpent === null && $externalSpent === null
                ? null
                : (float) $domesticSpent + (float) $externalSpent;
        }
        $attributes['activity_name'] = $this->plannedActivityName($indicator);

        if ($entry === null) {
            if ($indicator->requires_location && $locationId === null) {
                throw ValidationException::withMessages(['location' => __('Choose the location for this row first.')]);
            }

            $organizationId = $user->organization_id;
            $inPlan = $organizationId && ((int) $organizationId === (int) $plan->lead_organization_id
                || in_array((int) $organizationId, array_map('intval', $plan->participating_organization_ids ?? []), true));

            return $this->entries->create($attributes + [
                'indicator_id' => $indicator->id,
                'financial_year_id' => $financialYear->id,
                'reporting_period_id' => $period?->id,
                'entry_date' => $this->entryDate($period)->toDateString(),
                'organization_id' => $inPlan ? $organizationId : null,
                'location_level' => $locationLevel,
                'location_id' => $locationId,
            ], [], [], $files, $user);
        }

        if ($indicator->requires_location && $locationId !== null) {
            $attributes['location_level'] = $locationLevel;
            $attributes['location_id'] = $locationId;
        }

        return $this->entries->update($entry, $attributes, null, null, $files, $user);
    }

    /** @return array<string, mixed> */
    private function rowState(IndicatorDataEntry $entry, Indicator $indicator): array
    {
        $entry->loadCount('media')->load('media');

        return [
            'id' => $entry->id,
            'status' => $entry->status,
            'missing' => $this->missingFields($entry, $indicator),
            'edit_url' => route('indicator-data-entries.edit', $entry),
            'files' => $this->fileList($entry, $indicator->thematic_area_id),
        ];
    }

    /** @return list<array{id: int, name: string, url: string, delete_url: string}> */
    private function fileList(IndicatorDataEntry $entry, int $thematicAreaId): array
    {
        return $entry->getMedia('evidence')->map(fn (Media $media): array => [
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => route('indicator-data-entries.evidence.download', [$entry, $media]),
            'delete_url' => route('collection-sheet.evidence.destroy', [$thematicAreaId, $entry, $media]),
        ])->values()->all();
    }

    /**
     * What a row still lacks before it can be submitted.
     *
     * @return list<string>
     */
    public function missingFields(IndicatorDataEntry $entry, Indicator $indicator): array
    {
        $missing = [];
        $isText = in_array($indicator->measurementType?->code, ['text', 'qualitative'], true);

        if ($indicator->requires_location && blank($entry->location_id)) {
            $missing[] = __('location');
        }
        if ($isText ? blank($entry->actual_text) : $entry->actual_value === null) {
            $missing[] = __('achieved value');
        }
        if (blank($entry->implementation_status)) {
            $missing[] = __('implementation status');
        }
        if ($indicator->has_budget_implication) {
            foreach (['domestic_released', 'external_released', 'domestic_spent', 'external_spent'] as $field) {
                if ($entry->{$field} === null) {
                    $missing[] = __('budget released and spent');

                    break;
                }
            }
            if ((float) $entry->domestic_spent > (float) $entry->domestic_released
                || (float) $entry->external_spent > (float) $entry->external_released) {
                $missing[] = __('spent cannot exceed released');
            }
        }
        if ($indicator->requires_evidence && ($entry->media_count ?? $entry->media()->count()) === 0) {
            $missing[] = __('evidence');
        }

        return $missing;
    }

    private function rowIsEditable(User $user, ?IndicatorPlan $plan, ?IndicatorDataEntry $entry): bool
    {
        if ($plan === null) {
            return false;
        }
        if (! $user->can('indicator-data.create') || ! $user->can('indicator-data.update')) {
            return false;
        }
        if ($entry === null) {
            return true;
        }

        return in_array($entry->status, ['draft', 'rejected'], true)
            && ($entry->entered_by === $user->id || $user->hasRole('Super Admin'));
    }

    private function findEntry(User $user, Indicator $indicator, FinancialYear $financialYear, ?ReportingPeriod $period, ?int $locationId): ?IndicatorDataEntry
    {
        return IndicatorDataEntry::query()
            ->where('indicator_id', $indicator->id)->where('financial_year_id', $financialYear->id)
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId), fn ($query) => $query->whereNull('location_id'))
            ->when($period, fn ($query) => $query->where('reporting_period_id', $period->id), fn ($query) => $query->whereNull('reporting_period_id'))
            ->orderByRaw('entered_by = ? desc', [$user->id])
            ->first();
    }

    /**
     * The location scopes limiting this collector for the indicator, or null when
     * they may report anywhere (admins, or any unrestricted assignment).
     *
     * @return list<IndicatorDataAssignment>|null
     */
    private function locationScopes(User $user, Indicator $indicator): ?array
    {
        if ($user->hasRole('Super Admin') || $user->hasAnyRole(['Project Manager', 'Thematic Manager'])) {
            return null;
        }

        $assignments = IndicatorDataAssignment::query()->where('is_active', true)->where('indicator_id', $indicator->id)
            ->where(fn ($query) => $query->where('user_id', $user->id)
                ->when($user->organization_id, fn ($inner) => $inner->orWhere('organization_id', $user->organization_id)))
            ->get();

        if ($assignments->isEmpty() || $assignments->contains(fn (IndicatorDataAssignment $assignment) => blank($assignment->location_id))) {
            return null;
        }

        return $assignments->all();
    }

    private function assertCollectionOpen(User $user, FinancialYear $financialYear, ?ReportingPeriod $period): void
    {
        if ($user->can('indicator-data.override-period')) {
            return;
        }
        $today = now()->startOfDay();

        if ($financialYear->start_date->gt($today)) {
            throw ValidationException::withMessages(['financial_year_id' => __('You cannot enter data for a future financial year.')]);
        }
        if ($period && $period->start_date->gt($today)) {
            throw ValidationException::withMessages(['reporting_period_id' => __('You cannot enter data for a future reporting period.')]);
        }
        if ($period && (int) $period->financial_year_id !== (int) $financialYear->id) {
            throw ValidationException::withMessages(['reporting_period_id' => __('The reporting period must belong to the selected financial year.')]);
        }
    }

    /** Today, pulled inside the period so the entry-date rule on the full form still holds. */
    private function entryDate(?ReportingPeriod $period): Carbon
    {
        $today = now()->startOfDay();
        if ($period === null) {
            return $today;
        }
        if ($today->lt($period->start_date)) {
            return $period->start_date->copy();
        }
        if ($today->gt($period->end_date)) {
            return $period->end_date->copy();
        }

        return $today;
    }

    private function plannedActivityName(Indicator $indicator): ?string
    {
        $names = $indicator->interventions()->orderBy('interventions.id')->pluck('interventions.name')->implode('; ');

        return $names === '' ? null : mb_substr($names, 0, 255);
    }

    private function selectedPeriod(Request $request, Collection $periods, Collection $indicators): ?ReportingPeriod
    {
        if ($picked = $periods->firstWhere('id', $request->integer('reporting_period_id'))) {
            return $picked;
        }

        $today = now()->startOfDay();
        $current = $periods->filter(fn (ReportingPeriod $period) => $period->start_date->lte($today) && $period->end_date->gte($today));
        $commonType = $indicators->map(fn (Indicator $indicator) => self::PERIOD_TYPES[$indicator->reporting_frequency] ?? null)
            ->filter()->countBy()->sortDesc()->keys()->first();

        return $current->firstWhere('period_type', $commonType) ?? $current->first() ?? $periods->first();
    }

    private function defaultFinancialYear(): ?FinancialYear
    {
        return FinancialYear::query()->where('is_current', true)->first()
            ?? FinancialYear::query()->orderByDesc('start_date')->first();
    }

    /** @return Collection<int, ThematicArea> */
    private function accessibleThematicAreas(User $user): Collection
    {
        if ($user->hasRole('Super Admin')) {
            return ThematicArea::query()->orderBy('name')->get();
        }

        $viaAssignments = Indicator::query()->whereIn('id', $user->assignedIndicatorIds())->pluck('thematic_area_id');
        $ids = collect($user->visibleThematicAreaIds())->merge($viaAssignments)->unique();

        return ThematicArea::query()->whereIn('id', $ids)->orderBy('name')->get();
    }

    private function authorizeArea(User $user, ThematicArea $thematicArea): void
    {
        if (! $this->accessibleThematicAreas($user)->contains('id', $thematicArea->id)) {
            throw new AuthorizationException('You cannot collect data in this thematic area.');
        }
    }

    /** @return Collection<int, Indicator> */
    private function collectableIndicators(User $user, ThematicArea $thematicArea): Collection
    {
        return $thematicArea->indicators()
            ->where('status', 'active')
            ->when(! $user->hasRole('Super Admin'), fn ($query) => $query->whereIn('indicators.id', $user->assignedIndicatorIds()))
            ->with(['unitOfMeasure', 'measurementType', 'interventions' => fn ($query) => $query->orderBy('interventions.id')])
            ->orderBy('name')
            ->get();
    }
}
