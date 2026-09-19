<?php

namespace App\Http\Controllers;

use App\Models\UnitOfMeasure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitOfMeasureController extends Controller
{
    public function index(): View
    {
        return view('units-of-measure.index', [
            'unitsOfMeasure' => UnitOfMeasure::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('units-of-measure.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $unitOfMeasure = UnitOfMeasure::create($data);

        return redirect()->route('units-of-measure.index')->with('success', __('Unit of measure ":name" created.', ['name' => $unitOfMeasure->name]));
    }

    public function edit(UnitOfMeasure $unitOfMeasure): View
    {
        return view('units-of-measure.edit', ['unitOfMeasure' => $unitOfMeasure]);
    }

    public function update(Request $request, UnitOfMeasure $unitOfMeasure): RedirectResponse
    {
        $data = $this->validated($request, $unitOfMeasure);

        $unitOfMeasure->update($data);

        return redirect()->route('units-of-measure.index')->with('success', __('Unit of measure ":name" updated.', ['name' => $unitOfMeasure->name]));
    }

    public function destroy(UnitOfMeasure $unitOfMeasure): RedirectResponse
    {
        $unitOfMeasure->delete();

        return redirect()->route('units-of-measure.index')->with('success', __('Unit of measure ":name" deleted.', ['name' => $unitOfMeasure->name]));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?UnitOfMeasure $unitOfMeasure = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:units_of_measure,code'.($unitOfMeasure ? ",{$unitOfMeasure->id}" : '')],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
