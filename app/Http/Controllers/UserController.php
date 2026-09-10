<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
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

        return redirect()->route('users.index')->with('success', "User \"{$user->name}\" created.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user] + $this->formData());
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        $role = $data['role'];
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
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'auth_provider' => ['required', 'string', 'in:jumuishi,local'],
            'password' => $passwordRules,
            'status' => ['required', 'string', 'in:active,inactive'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);
    }
}
