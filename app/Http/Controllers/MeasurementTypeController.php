<?php

namespace App\Http\Controllers;

use App\Models\MeasurementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeasurementTypeController extends Controller
{
    public function index(): View
    {
        return view('measurement-types.index', [
            'measurementTypes' => MeasurementType::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('measurement-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $measurementType = MeasurementType::create($data);

        return redirect()->route('measurement-types.index')->with('success', "Measurement type \"{$measurementType->name}\" created.");
    }

    public function edit(MeasurementType $measurementType): View
    {
        return view('measurement-types.edit', ['measurementType' => $measurementType]);
    }

    public function update(Request $request, MeasurementType $measurementType): RedirectResponse
    {
        $data = $this->validated($request, $measurementType);

        $measurementType->update($data);

        return redirect()->route('measurement-types.index')->with('success', "Measurement type \"{$measurementType->name}\" updated.");
    }

    public function destroy(MeasurementType $measurementType): RedirectResponse
    {
        $measurementType->delete();

        return redirect()->route('measurement-types.index')->with('success', "Measurement type \"{$measurementType->name}\" deleted.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?MeasurementType $measurementType = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:measurement_types,code'.($measurementType ? ",{$measurementType->id}" : '')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
