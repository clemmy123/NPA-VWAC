<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorDataEntryRequest;
use App\Http\Requests\UpdateIndicatorDataEntryRequest;
use App\Http\Resources\IndicatorDataEntryResource;
use App\Models\DataSource;
use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\Organization;
use App\Models\ReportingPeriod;
use App\Models\User;
use App\Support\AdminLocationLevel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IndicatorDataEntryController extends Controller
{
    private const RELATIONS = ['rows.dimensionOptions', 'expenses', 'reviews'];

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
        unset($data['rows'], $data['expenses']);

        $this->assertAssignmentScope($request->user(), (int) $data['indicator_id'], $data['location_level'] ?? null, $data['location_id'] ?? null);

        $entry = DB::transaction(function () use ($data, $rows, $expenses, $request): IndicatorDataEntry {
            $entry = IndicatorDataEntry::create($data + [
                'entered_by' => $request->user()->id,
                'status' => 'draft',
            ]);

            $this->syncRows($entry, $rows);
            $this->syncExpenses($entry, $expenses);

            return $entry;
        });

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
        ] + $this->formData());
    }

    public function update(UpdateIndicatorDataEntryRequest $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        if (! in_array($indicatorDataEntry->status, ['draft', 'rejected'], true)) {
            throw new AuthorizationException('Only draft or rejected entries can be edited.');
        }

        $data = $request->validated();
        $rows = $data['rows'] ?? null;
        $expenses = $data['expenses'] ?? null;
        unset($data['rows'], $data['expenses']);

        $indicatorId = (int) ($data['indicator_id'] ?? $indicatorDataEntry->indicator_id);
        $locationLevel = array_key_exists('location_level', $data) ? $data['location_level'] : $indicatorDataEntry->location_level;
        $locationId = array_key_exists('location_id', $data) ? $data['location_id'] : $indicatorDataEntry->location_id;

        $this->assertAssignmentScope($request->user(), $indicatorId, $locationLevel, $locationId);

        DB::transaction(function () use ($indicatorDataEntry, $data, $rows, $expenses): void {
            $indicatorDataEntry->update($data);

            if ($rows !== null) {
                $this->syncRows($indicatorDataEntry, $rows);
            }

            if ($expenses !== null) {
                $this->syncExpenses($indicatorDataEntry, $expenses);
            }
        });

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($indicatorDataEntry->fresh(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data entry updated.');
    }

    public function submit(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        if (! in_array($indicatorDataEntry->status, ['draft', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => 'Only draft or rejected entries can be submitted.']);
        }

        if ($indicatorDataEntry->entered_by !== $request->user()->id && ! $request->user()->hasRole('Super Admin')) {
            throw new AuthorizationException('You can only submit your own entries.');
        }

        $this->assertReconciliation($indicatorDataEntry);

        $indicatorDataEntry->update(['status' => 'submitted', 'submitted_at' => now()]);

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($indicatorDataEntry->fresh(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data entry submitted for review.');
    }

    public function approve(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        if ($indicatorDataEntry->status !== 'submitted') {
            throw ValidationException::withMessages(['status' => 'Only submitted entries can be approved.']);
        }

        DB::transaction(function () use ($request, $indicatorDataEntry): void {
            $indicatorDataEntry->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
            ]);

            $indicatorDataEntry->reviews()->create([
                'reviewed_by' => $request->user()->id,
                'action' => 'approved',
                'comment' => $request->input('comment'),
            ]);
        });

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($indicatorDataEntry->fresh(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data entry approved.');
    }

    public function returnEntry(Request $request, IndicatorDataEntry $indicatorDataEntry): JsonResponse|RedirectResponse|IndicatorDataEntryResource
    {
        if ($indicatorDataEntry->status !== 'submitted') {
            throw ValidationException::withMessages(['status' => 'Only submitted entries can be returned.']);
        }

        DB::transaction(function () use ($request, $indicatorDataEntry): void {
            $indicatorDataEntry->update(['status' => 'rejected']);

            $indicatorDataEntry->reviews()->create([
                'reviewed_by' => $request->user()->id,
                'action' => 'rejected',
                'comment' => $request->input('comment'),
            ]);
        });

        if ($request->wantsJson()) {
            return new IndicatorDataEntryResource($indicatorDataEntry->fresh(self::RELATIONS));
        }

        return redirect()->route('indicator-data-entries.index')->with('success', 'Data entry returned to the submitter.');
    }

    private function assertAssignmentScope(User $user, int $indicatorId, ?string $locationLevel, ?int $locationId): void
    {
        if ($user->hasRole('Super Admin')) {
            return;
        }

        $assignments = IndicatorDataAssignment::query()
            ->where('indicator_id', $indicatorId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($assignments->isEmpty()) {
            throw new AuthorizationException('You are not assigned to report on this indicator.');
        }

        $unrestricted = $assignments->contains(fn (IndicatorDataAssignment $assignment) => $assignment->location_level === null);

        if ($unrestricted) {
            return;
        }

        $matches = $assignments->contains(
            fn (IndicatorDataAssignment $assignment) => $assignment->location_level === $locationLevel
                && $assignment->location_id === $locationId
        );

        if (! $matches) {
            throw new AuthorizationException('You are not assigned to report on this indicator for the given location.');
        }
    }

    private function assertReconciliation(IndicatorDataEntry $entry): void
    {
        $requiresReconciliation = $entry->indicator->dimensions()->wherePivot('must_reconcile', true)->exists();

        if (! $requiresReconciliation) {
            return;
        }

        $rowsTotal = (float) $entry->rows()->sum('value');
        $actual = (float) ($entry->actual_value ?? 0);

        if (abs($rowsTotal - $actual) > 0.0001) {
            throw ValidationException::withMessages([
                'actual_value' => 'Disaggregated rows must sum to the entry total when reconciliation is required for this indicator.',
            ]);
        }
    }

    private function syncRows(IndicatorDataEntry $entry, array $rows): void
    {
        $entry->rows()->delete();

        foreach ($rows as $rowData) {
            $row = $entry->rows()->create([
                'label' => $rowData['label'] ?? null,
                'value' => $rowData['value'],
            ]);

            if (! empty($rowData['dimension_option_ids'])) {
                $row->dimensionOptions()->sync($rowData['dimension_option_ids']);
            }
        }
    }

    private function syncExpenses(IndicatorDataEntry $entry, array $expenses): void
    {
        $entry->expenses()->delete();

        foreach ($expenses as $expenseData) {
            $entry->expenses()->create($expenseData);
        }
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
