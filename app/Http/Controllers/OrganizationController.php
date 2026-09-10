<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $organizations = Organization::query()
            ->with('organizationType')
            ->when($request->integer('organization_type_id'), fn ($query, $typeId) => $query->where('organization_type_id', $typeId))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return view('organizations.index', [
            'organizations' => $organizations,
            'organizationTypes' => OrganizationType::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('organizations.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $organization = Organization::create($data);

        return redirect()->route('organizations.index')->with('success', "Organization \"{$organization->name}\" created.");
    }

    public function edit(Organization $organization): View
    {
        return view('organizations.edit', ['organization' => $organization] + $this->formData());
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $data = $this->validated($request, $organization);

        $organization->update($data);

        return redirect()->route('organizations.index')->with('success', "Organization \"{$organization->name}\" updated.");
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $organization->delete();

        return redirect()->route('organizations.index')->with('success', "Organization \"{$organization->name}\" deleted.");
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'organizationTypes' => OrganizationType::query()->orderBy('name')->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Organization $organization = null): array
    {
        $data = $request->validate([
            'organization_type_id' => ['nullable', 'integer', 'exists:organization_types,id'],
            'code' => ['nullable', 'string', 'max:50', 'unique:organizations,code'.($organization ? ",{$organization->id}" : '')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
