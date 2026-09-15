<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorDataEntryRequest;
use App\Http\Requests\UpdateIndicatorDataEntryRequest;
use App\Http\Resources\IndicatorDataEntryResource;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\IndicatorDataAssignment;
use App\Models\Organization;
use App\Models\ReportingPeriod;
use App\Models\User;
use App\Services\IndicatorDataEntryService;
use App\Services\IndicatorReviewService;
use App\Support\AdminLocationLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IndicatorDataEntryController extends Controller
{
    private const RELATIONS = ['rows.dimensionOptions', 'expenses', 'reviews', 'media'];

    public function __construct(
        private readonly IndicatorDataEntryService $entryService,
        private readonly IndicatorReviewService $reviewService,
    ) {}

    public function index(Request $request): JsonResponse|View
    {
        $user = $request->user();

        $entries = IndicatorDataEntry::query()
            ->with(['indicator', 'financialYear', 'reportingPeriod', 'enteredBy'])
            ->when($request->integer('indicator_id'), fn ($query, $indicatorId) => $query->where('indicator_id', $indicatorId))
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('entered_by'), fn ($query, $enteredBy) => $query->where('entered_by', $enteredBy))
            ->when(! $user->can('indicator.view-all'), fn ($query) => $query->whereIn('indicator_id', $user->assignedIndicatorIds()))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json(IndicatorDataEntryResource::collection($entries)->response()->getData(true));
        }

        return view('indicator-data-entries.index', ['entries' => $entries]);
    }

    public function create(Request $request): View
    {
        return view('indicator-data-entries.create', $this->formData($request->user()));
    }

    public function store(StoreIndicatorDataEntryRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $rows = $data['rows'] ?? [];
        $expenses = $data['expenses'] ?? [];
        unset($data['rows'], $data['expenses'], $data['evidence']);

        $entry = $this->entryService->create($data, $rows, $expenses, $request->file('evidence', []), $request->user());

        if ($request->wantsJson()) {
            return (new IndicatorDataEntryResource($entry->load(self::RELATIONS)))->response()->setStatusCode(201);
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data collection created as a draft.');
    }

    public function show(Request $request, IndicatorDataEntry $indicatorDataEntry): IndicatorDataEntryResource
    {
        $this->authorizeIndicatorAccess($request->user(), $indicatorDataEntry);

        return new IndicatorDataEntryResource($indicatorDataEntry->load(self::RELATIONS));
    }

    public function edit(Request $request, IndicatorDataEntry $indicatorDataEntry): View
    {
        $this->authorizeIndicatorAccess($request->user(), $indicatorDataEntry);

        return view('indicator-data-entries.edit', [
            'entry' => $indicatorDataEntry->load(self::RELATIONS),
            'locationAncestorChain' => $this->locationAncestorChain($indicatorDataEntry->location_level, $indicatorDataEntry->location_id ? (int) $indicatorDataEntry->location_id : null),
        ] + $this->formData($request->user()));
    }

    private function authorizeIndicatorAccess(User $user, IndicatorDataEntry $indicatorDataEntry): void
    {
        if (! $user->can('indicator.view-all')) {
            $allowed = $this->assignmentsForUser($user)
                ->where('indicator_id', $indicatorDataEntry->indicator_id)
                ->contains(fn (IndicatorDataAssignment $assignment): bool =>
                    ($assignment->location_level === null
                        || ($assignment->location_level === $indicatorDataEntry->location_level
                            && (int) $assignment->location_id === (int) $indicatorDataEntry->location_id))
                    && ($assignment->organization_id === null
                        || (int) $assignment->organization_id === (int) $indicatorDataEntry->organization_id)
                );

            abort_unless($allowed, 403);
        }
    }

    public function update(UpdateIndicatorDataEntryRequest $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $data = $request->validated();
        $rows = $data['rows'] ?? null;
        $expenses = $data['expenses'] ?? null;
        unset($data['rows'], $data['expenses'], $data['evidence']);

        $entry = $this->entryService->update($indicatorDataEntry, $data, $rows, $expenses, $request->file('evidence', []), $request->user());

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data collection updated.');
    }

    public function submit(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $entry = $this->reviewService->submit($indicatorDataEntry, $request->user());

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data collection submitted for review.');
    }

    public function approve(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $entry = $this->reviewService->approve($indicatorDataEntry, $request->user(), $request->input('comment'));

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data collection approved.');
    }

    public function returnEntry(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $entry = $this->reviewService->returnToSubmitter($indicatorDataEntry, $request->user(), $request->input('comment'));

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data collection returned to the submitter.');
    }

    public function downloadEvidence(IndicatorDataEntry $indicatorDataEntry, Media $media): BinaryFileResponse
    {
        abort_unless($media->model_type === IndicatorDataEntry::class && $media->model_id === $indicatorDataEntry->id, 404);

        return response()->download($media->getPath(), $media->file_name);
    }

    public function destroyEvidence(Request $request, IndicatorDataEntry $indicatorDataEntry, Media $media): RedirectResponse
    {
        $this->entryService->removeEvidence($indicatorDataEntry, $media);

        return $this->redirectBackOrTo($request, 'indicator-data-entries.edit', [$indicatorDataEntry])->with('success', 'Evidence file removed.');
    }

    /** @return array<string, mixed> */
    private function formData(User $user): array
    {
        $assignments = $this->assignmentsForUser($user);
        $indicatorIds = $assignments->pluck('indicator_id')->unique()->values();
        $indicators = Indicator::query()
            ->with(['dimensions.options', 'measurementType', 'unitOfMeasure'])
            ->when(! $user->can('indicator.view-all'), fn ($query) => $query->whereIn('id', $indicatorIds))
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

        $organizationIds = $user->can('indicator.view-all')
            ? null
            : $assignments->pluck('organization_id')->filter()->unique()->values();

        return [
            'indicators' => $indicators,
            'financialYears' => FinancialYear::query()
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now()->toDateString())
                ->orderByDesc('start_date')->get(),
            'reportingPeriods' => ReportingPeriod::query()
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now()->toDateString())
                ->orderBy('start_date')->get(),
            'organizations' => Organization::query()
                ->when($organizationIds !== null && $organizationIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $organizationIds))
                ->when($organizationIds !== null && $organizationIds->isEmpty(), fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('name')->get(),
            'locationLevels' => AdminLocationLevel::levels(),
            'assignmentScopes' => $assignmentScopes,
            'dimensionConfig' => $dimensionConfig,
        ];
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
