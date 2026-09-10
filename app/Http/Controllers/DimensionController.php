<?php

namespace App\Http\Controllers;

use App\Models\Dimension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DimensionController extends Controller
{
    public function index(): View
    {
        return view('dimensions.index', [
            'dimensions' => Dimension::query()->withCount('options')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('dimensions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $dimension = Dimension::create($data);

        return redirect()->route('dimensions.index')->with('success', "Dimension \"{$dimension->name}\" created.");
    }

    public function edit(Dimension $dimension): View
    {
        return view('dimensions.edit', [
            'dimension' => $dimension,
            'options' => $dimension->options()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Dimension $dimension): RedirectResponse
    {
        $data = $this->validated($request, $dimension);

        $dimension->update($data);

        return redirect()->route('dimensions.index')->with('success', "Dimension \"{$dimension->name}\" updated.");
    }

    public function destroy(Dimension $dimension): RedirectResponse
    {
        $dimension->delete();

        return redirect()->route('dimensions.index')->with('success', "Dimension \"{$dimension->name}\" deleted.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Dimension $dimension = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:dimensions,code'.($dimension ? ",{$dimension->id}" : '')],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
