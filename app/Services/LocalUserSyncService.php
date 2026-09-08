<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class LocalUserSyncService
{
    public function __construct(private JumuishiClient $client) {}

    public function sync(User $user): bool
    {
        if (! config('jumuishi.enabled')) {
            return false;
        }

        try {
            $response = $this->client->syncUser([
                'local_user_id' => (string) $user->getKey(),
                'first_name' => $user->first_name,
                'second_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'gender' => $user->gender ? strtolower($user->gender) : null,
                'email' => strtolower(trim($user->email)),
                'password_hash' => $user->getRawOriginal('password'),
                'status' => $user->isActive() ? 'active' : 'disabled',
                'sync_password' => false,
            ]);
            $globalId = $response['data']['global_user_id'] ?? null;
            if (($response['status'] ?? null) !== 'success' || ! filter_var($globalId, FILTER_VALIDATE_INT)
                || $globalId < 1 || ($user->global_user_id && (string) $user->global_user_id !== (string) $globalId)) {
                throw new RuntimeException('Jumuishi returned an invalid or conflicting identity.');
            }

            $user->forceFill([
                'global_user_id' => $globalId,
                'jumuishi_sync_status' => 'synced',
                'jumuishi_synced_at' => now(),
                'jumuishi_sync_error' => null,
            ])->saveQuietly();

            return true;
        } catch (Throwable $exception) {
            $user->forceFill([
                'jumuishi_sync_status' => 'failed',
                'jumuishi_sync_error' => 'Synchronization failed. Check module credentials, connectivity, identity conflicts, and required profile fields.',
            ])->saveQuietly();
            Log::warning('Jumuishi user synchronization failed.', [
                'local_user_id' => $user->getKey(), 'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
