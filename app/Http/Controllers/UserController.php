<?php

namespace App\Http\Controllers;

use App\Models\IndicatorApprovalAssignment;
use App\Models\Organization;
use App\Models\User;
use App\Services\LocalUserSyncService;
use App\Support\AdminLocationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        $roles = $data['roles'];
        $approval = $this->extractApproval($data);
        unset($data['roles']);

        // All new users sign in via Jumuishi SSO. The `password` column is NOT NULL
        // with no DB default, so it still needs a value — an unusable random hash
        // reserves the row without granting local sign-in.
        $data['auth_provider'] = 'jumuishi';
        $data['password'] = Str::random(40);
        $data['password_login_enabled'] = false;

        $user = User::create($data);
        $user->syncRoles($roles);
        $this->syncApproval($user, $roles, $approval);

        $redirect = redirect()->route('users.index')->with('success', __('User ":name" created.', ['name' => $user->name]));

        if (! app(LocalUserSyncService::class)->sync($user->fresh())) {
            $redirect->with('warning', __('The user was saved locally, but Jumuishi synchronization failed. Please retry sync.'));
        }

        return $redirect;
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
        $roles = $data['roles'];
        $approval = $this->extractApproval($data);
        unset($data['roles']);

        // Sign-in method and password are no longer editable from this form; leave
        // the user's existing auth_provider/password_login_enabled untouched.
        $user->update($data);
        $user->syncRoles($roles);
        $this->syncApproval($user, $roles, $approval);

        $redirect = redirect()->route('users.index')->with('success', __('User ":name" updated.', ['name' => $user->name]));

        if (! app(LocalUserSyncService::class)->sync($user->fresh())) {
            $redirect->with('warning', __('User updated locally, but Jumuishi synchronization failed. Please retry sync.'));
        }

        return $redirect;
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        $message = $user->status === 'active'
            ? __('User ":name" reactivated.', ['name' => $user->name])
            : __('User ":name" deactivated.', ['name' => $user->name]);

        $redirect = redirect()->route('users.index')->with('success', $message);

        if (! app(LocalUserSyncService::class)->sync($user->fresh())) {
            $redirect->with('warning', __('User status changed locally, but Jumuishi synchronization failed. Please retry sync.'));
        }

        return $redirect;
    }

    public function forcePasswordChange(User $user): RedirectResponse
    {
        if ($user->auth_provider !== 'local') {
            return redirect()->route('users.index')
                ->with('warning', __('Only local-password accounts have a password to change. This user signs in via Jumuishi SSO.'));
        }

        $user->update(['force_password_change' => true]);

        return redirect()->route('users.index')
            ->with('success', __('":name" will be required to change their password on next sign-in.', ['name' => $user->name]));
    }

    public function syncJumuishi(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('Super Admin'), 403);

        $processed = 0;
        $failed = 0;
        $service = app(LocalUserSyncService::class);

        User::query()
            ->where('auth_provider', 'jumuishi')
            ->whereIn('jumuishi_sync_status', ['pending', 'failed'])
            ->eachById(function (User $user) use ($service, &$processed, &$failed): void {
                $processed++;

                if (! $service->sync($user)) {
                    $failed++;
                }
            });

        $message = __('Jumuishi sync processed :processed user(s); :failed failed.', [
            'processed' => $processed,
            'failed' => $failed,
        ]);

        return redirect()->route('users.index')
            ->with($failed > 0 ? 'warning' : 'success', $message);
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'.($user ? ",{$user->id}" : '')],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'approval_location_level' => ['nullable', 'string', 'in:region,district,council,ward'],
            'approval_location_id' => ['nullable', 'integer'],
        ]);

        $isApprover = in_array('Data Approver', $data['roles'], true);

        if ($isApprover
            && (! $data['approval_location_level'] || ! $data['approval_location_id']
                || ! AdminLocationLevel::exists($data['approval_location_level'], (int) $data['approval_location_id']))) {
            throw ValidationException::withMessages([
                'approval_location_id' => 'Select a valid location at the chosen approval level.',
            ]);
        }

        if (! $isApprover) {
            $data['approval_location_level'] = null;
            $data['approval_location_id'] = null;
        }

        return $data;
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

    /** @param  list<string>  $roles */
    private function syncApproval(User $user, array $roles, array $approval): void
    {
        $user->approvalAssignments()->update(['is_active' => false]);
        if (! in_array('Data Approver', $roles, true) || ! $approval['location_level'] || ! $approval['location_id']) {
            return;
        }
        IndicatorApprovalAssignment::query()->updateOrCreate([
            'user_id' => $user->id,
            'location_level' => $approval['location_level'],
            'location_id' => $approval['location_id'],
        ], ['is_active' => true]);
    }
}
