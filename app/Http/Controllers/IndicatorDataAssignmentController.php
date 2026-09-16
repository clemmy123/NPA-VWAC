<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\Organization;
use App\Models\User;
use App\Support\AdminLocationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndicatorDataAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = IndicatorDataAssignment::query()
            ->with(['indicator', 'user', 'organization'])
            ->when($request->integer('indicator_id'), fn ($query, $indicatorId) => $query->where('indicator_id', $indicatorId))
            ->when($request->integer('user_id'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return view('indicator-data-assignments.index', ['assignments' => $assignments]);
    }

    public function create(): View
    {
        return view('indicator-data-assignments.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        IndicatorDataAssignment::create($data);

        return $this->redirectBackOrTo($request, 'indicator-data-assignments.index')->with('success', 'Assignment created.');
    }

    public function edit(IndicatorDataAssignment $indicatorDataAssignment): View
    {
        return view('indicator-data-assignments.edit', [
            'assignment' => $indicatorDataAssignment,
            'locationAncestorChain' => $this->locationAncestorChain(
                $indicatorDataAssignment->location_level,
                $indicatorDataAssignment->location_id ? (int) $indicatorDataAssignment->location_id : null,
            ),
        ] + $this->formData());
    }

    public function update(Request $request, IndicatorDataAssignment $indicatorDataAssignment): RedirectResponse
    {
        $data = $this->validated($request, $indicatorDataAssignment);

        $indicatorDataAssignment->update($data);

        return $this->redirectBackOrTo($request, 'indicator-data-assignments.index')->with('success', 'Assignment updated.');
    }

    public function destroy(Request $request, IndicatorDataAssignment $indicatorDataAssignment): RedirectResponse
    {
        $indicatorDataAssignment->delete();

        return $this->redirectBackOrTo($request, 'indicator-data-assignments.index')->with('success', 'Assignment deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'indicators' => Indicator::query()->orderBy('name')->get(),
            'users' => User::query()->assignableDataEntry()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'locationLevels' => AdminLocationLevel::levels(),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?IndicatorDataAssignment $assignment = null): array
    {
        $data = $request->validate([
            'indicator_id' => ['required', 'integer', 'exists:indicators,id'],
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! User::query()->whereKey($value)->role('Data Entry User')->exists()) {
                        $fail('Only data entry users can be assigned to report on an indicator.');
                    }
                },
            ],
            'location_level' => ['nullable', 'string', 'in:'.implode(',', AdminLocationLevel::levels()), 'required_with:location_id'],
            'location_id' => ['nullable', 'integer', 'required_with:location_level'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
