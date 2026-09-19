<?php

namespace App\Http\Controllers;

use App\Models\Dimension;
use App\Models\DimensionOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DimensionOptionController extends Controller
{
    public function create(Dimension $dimension): View
    {
        return view('dimension-options.create', ['dimension' => $dimension]);
    }

    public function store(Request $request, Dimension $dimension): RedirectResponse
    {
        $data = $this->validated($request, $dimension);

        $dimension->options()->create($data);

        return redirect()->route('dimensions.edit', $dimension)->with('success', __('Option ":name" added to ":dimension".', ['name' => $data['name'], 'dimension' => $dimension->name]));
    }

    public function edit(Dimension $dimension, DimensionOption $dimensionOption): View
    {
        return view('dimension-options.edit', ['dimension' => $dimension, 'dimensionOption' => $dimensionOption]);
    }

    public function update(Request $request, Dimension $dimension, DimensionOption $dimensionOption): RedirectResponse
    {
        $data = $this->validated($request, $dimension, $dimensionOption);

        $dimensionOption->update($data);

        return redirect()->route('dimensions.edit', $dimension)->with('success', __('Option ":name" updated.', ['name' => $dimensionOption->name]));
    }

    public function destroy(Dimension $dimension, DimensionOption $dimensionOption): RedirectResponse
    {
        $dimensionOption->delete();

        return redirect()->route('dimensions.edit', $dimension)->with('success', __('Option ":name" deleted.', ['name' => $dimensionOption->name]));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, Dimension $dimension, ?DimensionOption $dimensionOption = null): array
    {
        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                'unique:dimension_options,code,'.($dimensionOption?->id ?? 'NULL').',id,dimension_id,'.$dimension->id,
            ],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
