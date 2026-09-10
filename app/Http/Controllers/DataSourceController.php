<?php

namespace App\Http\Controllers;

use App\Models\DataSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataSourceController extends Controller
{
    public const array COLLECTION_METHODS = ['manual', 'integration'];

    public function index(): View
    {
        return view('data-sources.index', [
            'dataSources' => DataSource::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('data-sources.create', ['collectionMethods' => self::COLLECTION_METHODS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $dataSource = DataSource::create($data);

        return redirect()->route('data-sources.index')->with('success', "Data source \"{$dataSource->name}\" created.");
    }

    public function edit(DataSource $dataSource): View
    {
        return view('data-sources.edit', ['dataSource' => $dataSource, 'collectionMethods' => self::COLLECTION_METHODS]);
    }

    public function update(Request $request, DataSource $dataSource): RedirectResponse
    {
        $data = $this->validated($request, $dataSource);

        $dataSource->update($data);

        return redirect()->route('data-sources.index')->with('success', "Data source \"{$dataSource->name}\" updated.");
    }

    public function destroy(DataSource $dataSource): RedirectResponse
    {
        $dataSource->delete();

        return redirect()->route('data-sources.index')->with('success', "Data source \"{$dataSource->name}\" deleted.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?DataSource $dataSource = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:data_sources,code'.($dataSource ? ",{$dataSource->id}" : '')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'collection_method' => ['required', 'string', 'in:'.implode(',', self::COLLECTION_METHODS)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
