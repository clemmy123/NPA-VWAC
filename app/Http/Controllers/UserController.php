<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\IndicatorApprovalAssignment;
use App\Models\User;
use App\Support\AdminLocationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with(['organization', 'roles'])
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return view('users.index', ['users' => $users]);
    }

    public function create(): View
    {
        return view('users.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $role = $data['role'];
        $approval = $this->extractApproval($data);
        unset($data['role']);

        if ($data['auth_provider'] === 'local') {
            $data['password_login_enabled'] = true;
        } else {
            // No local password ever logs in for jumuishi-provider accounts, but the
            // `password` column is NOT NULL with no DB default, so it still needs a
            // value — an unusable random hash reserves the row without granting
            // local sign-in (password_login_enabled stays false).
            $data['password'] = Str::random(40);
            $data['password_login_enabled'] = false;
        }

        $user = User::create($data);
        $user->syncRoles([$role]);
        $this->syncApproval($user, $role, $approval);

        return redirect()->route('users.index')->with('success', "User \"{$user->name}\" created.");
    }

    public function edit(User $user): View
    {
        $approval = $user->approvalAssignments()->where('is_active', true)->first();
        return view('users.edit', [
            'user' => $user,
            'approvalAssignment' => $approval,
            'approvalLocationChain' => $approval ? AdminLocationLevel::ancestorChain($approval->location_level, (int) $approval->location_id) : [],
        ] + $this->formData());
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        $role = $data['role'];
        $approval = $this->extractApproval($data);
        unset($data['role']);

        if ($data['auth_provider'] === 'local') {
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $data['password_login_enabled'] = true;
        } else {
            unset($data['password']);
            $data['password_login_enabled'] = false;
        }

        $user->update($data);
        $user->syncRoles([$role]);
        $this->syncApproval($user, $role, $approval);

        return redirect()->route('users.index')->with('success', "User \"{$user->name}\" updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        $message = $user->status === 'active'
            ? "User \"{$user->name}\" reactivated."
            : "User \"{$user->name}\" deactivated.";

        return redirect()->route('users.index')->with('success', $message);
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'organizations' => Organization::query()->orderBy('name')->get(),
            'roles' => Role::query()->orderBy('name')->get(),
            'locationLevels' => ['region', 'district', 'council', 'ward'],
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?User $user = null): array
    {
        $passwordRules = ['nullable', 'string', 'min:8', 'confirmed'];
        if (! $user) {
            $passwordRules[] = 'required_if:auth_provider,local';
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'.($user ? ",{$user->id}" : '')],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'auth_provider' => ['required', 'string', 'in:jumuishi,local'],
            'password' => $passwordRules,
            'status' => ['required', 'string', 'in:active,inactive'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'approval_location_level' => ['nullable', 'string', 'in:region,district,council,ward', 'required_with:approval_location_id'],
            'approval_location_id' => ['nullable', 'integer', 'required_with:approval_location_level'],
        ]);
    }

    private function extractApproval(array &$data): array
    {
        $approval = [
            'location_level' => $data['approval_location_level'] ?? null,
            'location_id' => isset($data['approval_location_id']) ? (int) $data['approval_location_id'] : null,
        ];
        unset($data['approval_location_level'], $data['approval_location_id']);

        return $approval;
    }

    private function syncApproval(User $user, string $role, array $approval): void
    {
        $user->approvalAssignments()->update(['is_active' => false]);
        if ($role !== 'Data Approver' || ! $approval['location_level'] || ! $approval['location_id']) {
            return;
        }
        abort_unless(AdminLocationLevel::exists($approval['location_level'], $approval['location_id']), 422);
        IndicatorApprovalAssignment::query()->updateOrCreate([
            'user_id' => $user->id,
            'location_level' => $approval['location_level'],
            'location_id' => $approval['location_id'],
        ], ['is_active' => true]);
    }
}
