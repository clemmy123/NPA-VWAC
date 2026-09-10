<?php

namespace App\Http\Controllers;

use App\Models\OrganizationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationTypeController extends Controller
{
    public function index(): View
    {
        return view('organization-types.index', [
            'organizationTypes' => OrganizationType::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('organization-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        OrganizationType::create($data);

        return redirect()->route('organization-types.index')->with('success', "Organization type \"{$data['name']}\" created.");
    }

    public function edit(OrganizationType $organizationType): View
    {
        return view('organization-types.edit', ['organizationType' => $organizationType]);
    }

    public function update(Request $request, OrganizationType $organizationType): RedirectResponse
    {
        $data = $this->validated($request, $organizationType);

        $organizationType->update($data);

        return redirect()->route('organization-types.index')->with('success', "Organization type \"{$data['name']}\" updated.");
    }

    public function destroy(OrganizationType $organizationType): RedirectResponse
    {
        $organizationType->delete();

        return redirect()->route('organization-types.index')->with('success', "Organization type \"{$organizationType->name}\" deleted.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?OrganizationType $organizationType = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:organization_types,code'.($organizationType ? ",{$organizationType->id}" : '')],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
