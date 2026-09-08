<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\JumuishiUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class JumuishiUserController extends Controller
{
    public function provision(JumuishiUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        [$user, $created] = DB::transaction(function () use ($data): array {
            $user = User::query()->where('global_user_id', $data['global_user_id'])->lockForUpdate()->first();
            $byEmail = User::query()->whereRaw('LOWER(email) = ?', [$data['email']])->lockForUpdate()->first();

            if (($user && $byEmail && ! $user->is($byEmail))
                || ($byEmail?->global_user_id && (string) $byEmail->global_user_id !== (string) $data['global_user_id'])) {
                throw ValidationException::withMessages(['global_user_id' => 'The email is linked to a different identity.']);
            }

            $user ??= $byEmail;
            $created = ! $user;
            $user ??= new User;
            if (! $created && $this->isDuplicate($data)) {
                return [$user, false];
            }

            $user->forceFill($this->identityAttributes($data));
            $this->setPassword($user, $data['password_hash']);
            $user->saveQuietly();

            if ($created) {
                $user->assignRole(Role::findByName(config('jumuishi.default_role'), 'web'));
            } else {
                $this->invalidateSessions($user);
            }

            $this->recordEvent($data, 'user.created');

            return [$user, $created];
        });

        return response()->json(['status' => 'success', 'data' => [
            'local_user_id' => $user->getKey(),
            'global_user_id' => $user->global_user_id,
            'created' => $created,
        ]]);
    }

    public function sync(JumuishiUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $duplicate = DB::transaction(function () use ($data): bool {
            $user = User::query()->where('global_user_id', $data['global_user_id'])->lockForUpdate()->firstOrFail();

            if ($this->isDuplicate($data)) {
                return true;
            }

            if ($data['event_type'] === 'user.updated') {
                if (User::query()->whereKeyNot($user->getKey())->whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
                    throw ValidationException::withMessages(['email' => 'The email belongs to another module account.']);
                }
                $user->forceFill($this->identityAttributes($data));
            } elseif ($data['event_type'] === 'password.changed') {
                $this->setPassword($user, $data['password_hash']);
            } elseif ($data['event_type'] === 'user.disabled') {
                $user->status = 'deactivated';
            } elseif ($data['event_type'] === 'user.enabled') {
                $user->status = 'active';
            }

            $user->forceFill([
                'jumuishi_sync_status' => 'synced',
                'jumuishi_synced_at' => now(),
                'jumuishi_sync_error' => null,
            ]);

            if (! $user->isActive() || $data['event_type'] === 'password.changed') {
                $user->remember_token = Str::random(60);
                $this->invalidateSessions($user);
            }

            $user->saveQuietly();
            $this->recordEvent($data, $data['event_type']);

            return false;
        });

        return response()->json(['status' => 'success', 'data' => $duplicate
            ? ['duplicate' => true]
            : ['processed' => true]]);
    }

    private function identityAttributes(array $data): array
    {
        return [
            'name' => trim(implode(' ', array_filter([
                $data['first_name'], $data['second_name'] ?? null, $data['last_name'],
            ]))),
            'first_name' => $data['first_name'],
            'middle_name' => $data['second_name'] ?? null,
            'last_name' => $data['last_name'],
            'gender' => isset($data['gender']) ? ucfirst($data['gender']) : null,
            'email' => $data['email'],
            'global_user_id' => $data['global_user_id'],
            'status' => $data['status'] === 'active' ? 'active' : 'deactivated',
            'auth_provider' => 'jumuishi',
            'password_login_enabled' => false,
            'jumuishi_sync_status' => 'synced',
            'jumuishi_synced_at' => now(),
            'jumuishi_sync_error' => null,
        ];
    }

    private function setPassword(User $user, string $hash): void
    {
        // The request has validated this central hash; preserve its algorithm and bytes.
        $user->setRawAttributes(array_replace($user->getAttributes(), [
            'password' => $hash,
            'remember_token' => Str::random(60),
        ]));
    }

    private function isDuplicate(array $data): bool
    {
        if (empty($data['event_uuid'])) {
            return false;
        }

        $event = DB::table('jumuishi_events')->where('event_uuid', $data['event_uuid'])->first();
        if ($event && ((string) $event->global_user_id !== (string) $data['global_user_id']
            || $event->event_type !== ($data['event_type'] ?? 'user.created'))) {
            abort(409, 'The event identifier belongs to a different identity or event type.');
        }

        return $event !== null;
    }

    private function recordEvent(array $data, string $type): void
    {
        if (! empty($data['event_uuid'])) {
            DB::table('jumuishi_events')->insert([
                'event_uuid' => $data['event_uuid'],
                'global_user_id' => $data['global_user_id'],
                'event_type' => $type,
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function invalidateSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())->delete();
        }
    }
}
