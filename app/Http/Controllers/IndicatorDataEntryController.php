<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorDataEntryRequest;
use App\Http\Requests\UpdateIndicatorDataEntryRequest;
use App\Http\Resources\IndicatorDataEntryResource;
use App\Models\DataSource;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataEntry;
use App\Models\Organization;
use App\Models\ReportingPeriod;
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
        $entries = IndicatorDataEntry::query()
            ->with(['indicator', 'financialYear', 'reportingPeriod', 'enteredBy'])
            ->when($request->integer('indicator_id'), fn ($query, $indicatorId) => $query->where('indicator_id', $indicatorId))
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('entered_by'), fn ($query, $enteredBy) => $query->where('entered_by', $enteredBy))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json(IndicatorDataEntryResource::collection($entries)->response()->getData(true));
        }

        return view('indicator-data-entries.index', ['entries' => $entries]);
    }

    public function create(): View
    {
        return view('indicator-data-entries.create', $this->formData());
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

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data entry created as a draft.');
    }

    public function show(IndicatorDataEntry $indicatorDataEntry): IndicatorDataEntryResource
    {
        return new IndicatorDataEntryResource($indicatorDataEntry->load(self::RELATIONS));
    }

    public function edit(IndicatorDataEntry $indicatorDataEntry): View
    {
        return view('indicator-data-entries.edit', [
            'entry' => $indicatorDataEntry->load(self::RELATIONS),
            'locationAncestorChain' => $this->locationAncestorChain($indicatorDataEntry->location_level, $indicatorDataEntry->location_id ? (int) $indicatorDataEntry->location_id : null),
        ] + $this->formData());
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

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data entry updated.');
    }

    public function submit(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $entry = $this->reviewService->submit($indicatorDataEntry, $request->user());

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data entry submitted for review.');
    }

    public function approve(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $entry = $this->reviewService->approve($indicatorDataEntry, $request->user(), $request->input('comment'));

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data entry approved.');
    }

    public function returnEntry(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        $entry = $this->reviewService->returnToSubmitter($indicatorDataEntry, $request->user(), $request->input('comment'));

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($entry->load(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data entry returned to the submitter.');
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
    private function formData(): array
    {
        return [
            'indicators' => Indicator::query()->orderBy('name')->get(),
            'financialYears' => FinancialYear::query()->orderBy('name')->get(),
            'reportingPeriods' => ReportingPeriod::query()->orderBy('sequence')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'dataSources' => DataSource::query()->orderBy('name')->get(),
            'locationLevels' => AdminLocationLevel::levels(),
        ];
    }
}
