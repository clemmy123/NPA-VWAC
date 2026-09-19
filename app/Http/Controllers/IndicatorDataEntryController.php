<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorDataEntryRequest;
use App\Http\Requests\UpdateIndicatorDataEntryRequest;
use App\Http\Resources\IndicatorDataEntryResource;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ReportingPeriod;
use App\Models\User;
use App\Services\IndicatorDataEntryService;
use App\Services\IndicatorReviewService;
use App\Support\AdminLocationLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IndicatorDataEntryController extends Controller
{
    private const RELATIONS = ['rows.dimensionOptions', 'activities', 'expenses', 'reviews', 'media'];

    public function __construct(
        private readonly IndicatorDataEntryService $entryService,
        private readonly IndicatorReviewService $reviewService,
    ) {}

    public function index(Request $request): JsonResponse|View
    {
        $user = $request->user();

        $historyIndicatorId = $request->integer('indicator_id');

        $entryQuery = IndicatorDataEntry::query()
            ->with(['indicator', 'financialYear', 'reportingPeriod', 'enteredBy'])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('entered_by'), fn ($query, $enteredBy) => $query->where('entered_by', $enteredBy))
            ->when(! $user->can('indicator.view-all'), fn ($query) => $query->where('entered_by', $user->id))
            ->when($user->can('indicator.view-all'), fn ($query) => $query->where(function ($visible) use ($user): void {
                $visible->where('entered_by', $user->id)
                    ->orWhereNotIn('status', ['draft', 'rejected']);
            }));

        $workspaceEntries = (clone $entryQuery)->get();
        $listedEntries = (clone $entryQuery)
            ->where('status', '!=', 'draft')
            ->when($historyIndicatorId, fn ($query) => $query->where('indicator_id', $historyIndicatorId))
            ->latest('id')
            ->get();

        if ($request->wantsJson()) {
            $entries = $entryQuery
                ->where('status', '!=', 'draft')
                ->when($historyIndicatorId, fn ($query) => $query->where('indicator_id', $historyIndicatorId))
                ->latest('id')
                ->paginate($request->integer('per_page', 15))
                ->withQueryString();

            return response()->json(IndicatorDataEntryResource::collection($entries)->response()->getData(true));
        }

        $entries = $this->paginateGroupedCollectionRecords($request, $listedEntries);

        $viewData = $this->formData($user);

        if ($user->hasRole('Thematic Manager')) {
            $viewData['indicators'] = $this->prioritizeWorkspaceIndicators($viewData['indicators'], $workspaceEntries);
        } elseif ($user->hasRole('Data Entry User')) {
            $viewData['indicators'] = $this->prioritizeCollectorWorkspaceIndicators($viewData['indicators'], $workspaceEntries);
        }

        return view('indicator-data-entries.index', [
            'entries' => $entries,
            'workspaceEntries' => $workspaceEntries,
            'historyIndicator' => $historyIndicatorId
                ? $viewData['indicators']->firstWhere('id', $historyIndicatorId)
                : null,
        ] + $viewData);
    }

    public function create(Request $request): View
    {
        return view('indicator-data-entries.create', $this->formData($request->user()));
    }

    public function start(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')],
            'thematic_area_id' => [
                'required',
                'integer',
                Rule::exists('thematic_areas', 'id')->where(fn ($query) => $query
                    ->where('project_id', $request->integer('project_id'))),
            ],
            'indicator_id' => [
                'required',
                'integer',
                Rule::exists('indicators', 'id')->where(fn ($query) => $query
                    ->where('thematic_area_id', $request->integer('thematic_area_id'))
                    ->where('status', 'active')),
            ],
        ]);
        $indicator = Indicator::query()->where('status', 'active')->findOrFail($data['indicator_id']);
        abort_unless(
            $request->user()->can('indicator.view-all')
                || in_array($indicator->id, $request->user()->assignedIndicatorIds(), true),
            403,
            'This indicator is not assigned to you.'
        );
        $today = now()->startOfDay();
        $financialYear = FinancialYear::query()
            ->where('is_active', true)->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->firstOrFail();
        $periodType = ['weekly' => 'week', 'monthly' => 'month', 'quarterly' => 'quarter', 'biannual' => 'semi_annual', 'annual' => 'annual'][$indicator->reporting_frequency] ?? null;
        $period = $periodType ? ReportingPeriod::query()
            ->where('financial_year_id', $financialYear->id)->where('period_type', $periodType)
            ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first() : null;

        $entry = $this->entryService->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'reporting_period_id' => $period?->id,
            'entry_date' => $today->toDateString(),
            'organization_id' => $request->user()->organization_id,
        ], [], [], [], $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'entry_id' => $entry->id,
                'edit_url' => route('indicator-data-entries.edit', ['indicator_data_entry' => $entry, 'embedded' => 1]),
            ], 201);
        }

        return redirect()->route('indicator-data-entries.edit', $entry)
            ->with('success', __('Collection started. Complete the fields required for this indicator.'));
    }

    public function store(StoreIndicatorDataEntryRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $rows = $data['rows'] ?? [];
        $expenses = $data['expenses'] ?? [];
        $activities = $data['activities'] ?? [];
        unset($data['rows'], $data['activities'], $data['expenses'], $data['evidence']);

        $entry = $this->entryService->create($data, $rows, $expenses, $request->file('evidence', []), $request->user(), $activities);

        if ($request->wantsJson()) {
            return (new IndicatorDataEntryResource($entry->load(self::RELATIONS)))->response()->setStatusCode(201);
        }

        return redirect()->route('indicator-data-entries.index')->with('success', __('Data collection created as a draft.'));
    }

    public function show(Request $request, IndicatorDataEntry $indicatorDataEntry): IndicatorDataEntryResource|View
    {
        $this->authorizeIndicatorAccess($request->user(), $indicatorDataEntry);

        $user = $request->user();
        abort_if(
            $user->can('indicator.view-all')
                && (int) $indicatorDataEntry->entered_by !== (int) $user->id
                && in_array($indicatorDataEntry->status, ['draft', 'rejected'], true),
            403
        );

        $entry = $indicatorDataEntry->load([
            ...self::RELATIONS,
            'indicator.thematicArea',
            'indicator.unitOfMeasure',
            'financialYear',
            'reportingPeriod',
            'enteredBy',
            'organization',
            'approvedBy',
        ]);

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry);
        }

        $relatedReports = $this->visibleEntriesQuery($user)
            ->where('indicator_id', $entry->indicator_id)
            ->where('status', '!=', 'draft')
            ->with([
                ...self::RELATIONS,
                'indicator.thematicArea',
                'indicator.unitOfMeasure',
                'financialYear',
                'reportingPeriod',
                'enteredBy',
                'organization',
            ])
            ->latest('id')
            ->get();

        if ($entry->status === 'draft' && ! $relatedReports->contains(fn (IndicatorDataEntry $report): bool => $report->id === $entry->id)) {
            $relatedReports = $relatedReports->prepend($entry);
        }

        if ($relatedReports->isEmpty()) {
            $relatedReports = collect([$entry]);
        }

        $locationNames = $relatedReports->mapWithKeys(function (IndicatorDataEntry $report): array {
            return [
                $report->id => $report->location_level && $report->location_id
                    ? AdminLocationLevel::name($report->location_level, (int) $report->location_id)
                    : null,
            ];
        });

        return view('indicator-data-entries.show', [
            'entry' => $entry,
            'relatedReports' => $relatedReports,
            'locationNames' => $locationNames,
        ]);
    }

    public function edit(Request $request, IndicatorDataEntry $indicatorDataEntry): View
    {
        $this->authorizeIndicatorAccess($request->user(), $indicatorDataEntry);

        return view('indicator-data-entries.edit', [
            'entry' => $indicatorDataEntry->load(self::RELATIONS),
            'locationAncestorChain' => $indicatorDataEntry->location_level && $indicatorDataEntry->location_id
                ? AdminLocationLevel::ancestorChain($indicatorDataEntry->location_level, (int) $indicatorDataEntry->location_id)
                : [],
        ] + $this->formData($request->user()));
    }

    private function authorizeIndicatorAccess(User $user, IndicatorDataEntry $indicatorDataEntry): void
    {
        abort_if(! $user->can('indicator.view-all') && (int) $indicatorDataEntry->entered_by !== (int) $user->id, 403);
    }

    /**
     * @return Builder<IndicatorDataEntry>
     */
    private function visibleEntriesQuery(User $user)
    {
        return IndicatorDataEntry::query()
            ->when(! $user->can('indicator.view-all'), fn ($query) => $query->where('entered_by', $user->id))
            ->when($user->can('indicator.view-all'), fn ($query) => $query->where(function ($visible) use ($user): void {
                $visible->where('entered_by', $user->id)
                    ->orWhereNotIn('status', ['draft', 'rejected']);
            }));
    }

    /**
     * @param  Collection<int, IndicatorDataEntry>  $listedEntries
     */
    private function paginateGroupedCollectionRecords(Request $request, Collection $listedEntries): LengthAwarePaginator
    {
        $records = $listedEntries
            ->groupBy('indicator_id')
            ->map(function (Collection $group): IndicatorDataEntry {
                return $group->first(fn (IndicatorDataEntry $entry): bool => in_array($entry->status, ['submitted', 'pending_approval'], true))
                    ?? $group->first();
            })
            ->values();

        $perPage = max(1, $request->integer('per_page', 15));
        $total = $records->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $request->integer('page', 1)), $lastPage);

        return (new LengthAwarePaginator(
            $records->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page'],
        ))->withQueryString()->fragment('collection-records');
    }

    public function update(UpdateIndicatorDataEntryRequest $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $data = $request->validated();
        $rows = $data['rows'] ?? null;
        $expenses = $data['expenses'] ?? null;
        $activities = $data['activities'] ?? null;
        unset($data['rows'], $data['activities'], $data['expenses'], $data['evidence']);

        $entry = $this->entryService->update($indicatorDataEntry, $data, $rows, $expenses, $request->file('evidence', []), $request->user(), $activities);

        if ($request->input('intent') === 'submit') {
            abort_unless($request->user()?->can('indicator-data.submit'), 403);
            $entry = $this->reviewService->submit($entry->fresh(), $request->user());
        }

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        if ($request->boolean('embedded')) {
            if ($request->input('intent') === 'submit') {
                return redirect()->route('indicator-data-entries.edit', [
                    'indicator_data_entry' => $entry,
                    'embedded' => 1,
                    'submitted' => 1,
                ]);
            }

            return redirect()->route('indicator-data-entries.edit', ['indicator_data_entry' => $entry, 'embedded' => 1])
                ->with('success', __('Data collection saved. You can review it and submit when ready.'));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', $request->input('intent') === 'submit'
            ? __('Data collection submitted for review.')
            : __('Data collection updated.'));
    }

    public function submit(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $entry = $this->reviewService->submit($indicatorDataEntry, $request->user());

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        if ($request->boolean('embedded')) {
            return redirect()->route('indicator-data-entries.edit', [
                'indicator_data_entry' => $entry,
                'embedded' => 1,
                'submitted' => 1,
            ]);
        }

        return redirect()->route('indicator-data-entries.index')->with('success', __('Data collection submitted for review.'));
    }

    public function approve(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $entry = $this->reviewService->approve($indicatorDataEntry, $request->user(), $request->input('comment'));

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', __('Data collection approved.'));
    }

    public function returnEntry(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $entry = $this->reviewService->returnToSubmitter($indicatorDataEntry, $request->user(), $request->input('comment'));

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', __('Data collection returned to the submitter.'));
    }

    public function downloadEvidence(IndicatorDataEntry $indicatorDataEntry, Media $media): BinaryFileResponse
    {
        abort_unless($media->model_type === IndicatorDataEntry::class && $media->model_id === $indicatorDataEntry->id, 404);

        return response()->download($media->getPath(), $media->file_name);
    }

    public function destroyEvidence(Request $request, IndicatorDataEntry $indicatorDataEntry, Media $media): RedirectResponse
    {
        $this->entryService->removeEvidence($indicatorDataEntry, $media);

        return $this->redirectBackOrTo($request, 'indicator-data-entries.edit', [$indicatorDataEntry])->with('success', __('Evidence file removed.'));
    }

    /** @return array<string, mixed> */
    private function formData(User $user): array
    {
        $assignments = $user->can('indicator.view-all') ? collect() : $this->assignmentsForUser($user);
        $indicators = Indicator::query()
            ->with(['dimensions.options', 'measurementType', 'unitOfMeasure', 'thematicArea.project'])
            ->where('status', 'active')
            ->when(! $user->can('indicator.view-all'), fn ($query) => $query->whereIn('id', $user->assignedIndicatorIds()))
            ->orderBy('name')
            ->get();

        $assignmentScopes = $assignments->groupBy('indicator_id')->map(function ($items): array {
            $organizationIds = $items->pluck('organization_id')->filter()->unique()->values();
            $locations = $items->filter(fn ($item) => $item->location_level !== null && $item->location_id !== null);

            $singleLocation = $locations->pluck('location_level')->unique()->count() === 1
                && $locations->pluck('location_id')->unique()->count() === 1;
            $location = $singleLocation ? $locations->first() : null;

            return [
                'organization_id' => $organizationIds->count() === 1 ? (int) $organizationIds->first() : null,
                'location_level' => $location?->location_level,
                'location_id' => $location ? (int) $location->location_id : null,
                'location_chain' => $location
                    ? AdminLocationLevel::ancestorChain($location->location_level, (int) $location->location_id)
                    : [],
            ];
        });
        $dimensionConfig = $indicators->mapWithKeys(fn (Indicator $indicator): array => [
            $indicator->id => $indicator->dimensions->map(fn ($dimension): array => [
                'id' => $dimension->id,
                'name' => $dimension->name,
                'required' => (bool) $dimension->pivot->is_required,
                'must_reconcile' => (bool) $dimension->pivot->must_reconcile,
                'options' => $dimension->options->where('is_active', true)->sortBy('sort_order')->values()->map(fn ($option): array => [
                    'id' => $option->id,
                    'name' => $option->name,
                ])->all(),
            ])->all(),
        ]);

        $thematicAreas = $indicators->pluck('thematicArea')->filter()->unique('id')->sortBy('name')->values();
        $collectableProjectIds = $thematicAreas->pluck('project_id')->map(fn ($id): int => (int) $id)->unique()->values();
        $projects = Project::query()
            ->whereIn('id', $collectableProjectIds)
            ->when(! $user->can('indicator.view-all'), fn ($query) => $query->whereIn('id', $user->visibleProjectIds()))
            ->orderBy('name')
            ->get();

        return [
            'indicators' => $indicators,
            'thematicAreas' => $thematicAreas,
            'projects' => $projects,
            'collectableProjectIds' => $collectableProjectIds,
            'financialYears' => FinancialYear::query()
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now()->toDateString())
                ->orderByDesc('start_date')->get(),
            'reportingPeriods' => ReportingPeriod::query()
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now()->toDateString())
                ->orderBy('start_date')->get(),
            'organizations' => Organization::query()
                ->orderBy('name')->get(),
            'locationLevels' => AdminLocationLevel::levels(),
            'assignmentScopes' => $assignmentScopes,
            'dimensionConfig' => $dimensionConfig,
        ];
    }

    /**
     * @param  Collection<int, Indicator>  $indicators
     * @param  Collection<int, IndicatorDataEntry>  $workspaceEntries
     * @return Collection<int, Indicator>
     */
    private function prioritizeWorkspaceIndicators(Collection $indicators, Collection $workspaceEntries): Collection
    {
        return $indicators->sortBy(function (Indicator $indicator) use ($workspaceEntries): array {
            $statuses = $workspaceEntries->where('indicator_id', $indicator->id)->pluck('status');
            $rank = match (true) {
                $statuses->contains(fn (string $status): bool => in_array($status, ['submitted', 'pending_approval'], true)) => 0,
                $statuses->contains('approved') => 1,
                default => 2,
            };

            return [$rank, mb_strtolower($indicator->name)];
        })->values();
    }

    /**
     * @param  Collection<int, Indicator>  $indicators
     * @param  Collection<int, IndicatorDataEntry>  $workspaceEntries
     * @return Collection<int, Indicator>
     */
    private function prioritizeCollectorWorkspaceIndicators(Collection $indicators, Collection $workspaceEntries): Collection
    {
        return $indicators->sortBy(function (Indicator $indicator) use ($workspaceEntries): array {
            $latest = $workspaceEntries->where('indicator_id', $indicator->id)->sortByDesc('entry_date')->first();
            $rank = match (true) {
                $latest !== null && in_array($latest->status, ['draft', 'rejected'], true) => 0,
                $latest === null => 1,
                in_array($latest->status, ['submitted', 'pending_approval'], true) => 2,
                default => 3,
            };

            return [$rank, mb_strtolower($indicator->name)];
        })->values();
    }

    private function assignmentsForUser(User $user)
    {
        return IndicatorDataAssignment::query()
            ->where('is_active', true)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id);
                if ($user->organization_id !== null) {
                    $query->orWhere('organization_id', $user->organization_id);
                }
            })
            ->get();
    }
}
