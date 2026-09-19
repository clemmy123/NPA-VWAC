<?php

namespace App\Http\Controllers;

use App\Models\FinancialYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialYearController extends Controller
{
    public function index(): View
    {
        return view('financial-years.index', [
            'financialYears' => FinancialYear::query()->orderByDesc('start_date')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('financial-years.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $this->applyCurrent($data);

        $financialYear = FinancialYear::create($data);

        return redirect()->route('financial-years.index')->with('success', __('Financial year ":name" created.', ['name' => $financialYear->name]));
    }

    public function edit(FinancialYear $financialYear): View
    {
        return view('financial-years.edit', ['financialYear' => $financialYear]);
    }

    public function update(Request $request, FinancialYear $financialYear): RedirectResponse
    {
        $data = $this->validated($request, $financialYear);

        $this->applyCurrent($data, $financialYear);

        $financialYear->update($data);

        return redirect()->route('financial-years.index')->with('success', __('Financial year ":name" updated.', ['name' => $financialYear->name]));
    }

    public function destroy(FinancialYear $financialYear): RedirectResponse
    {
        $financialYear->delete();

        return redirect()->route('financial-years.index')->with('success', __('Financial year ":name" deleted.', ['name' => $financialYear->name]));
    }

    private function applyCurrent(array &$data, ?FinancialYear $except = null): void
    {
        if (! ($data['is_current'] ?? false)) {
            return;
        }

        FinancialYear::query()
            ->when($except, fn ($query) => $query->where('id', '!=', $except->id))
            ->update(['is_current' => false]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?FinancialYear $financialYear = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:20', 'unique:financial_years,name'.($financialYear ? ",{$financialYear->id}" : '')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_current'] = $request->boolean('is_current');
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
