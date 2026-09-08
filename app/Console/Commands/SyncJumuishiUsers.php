<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LocalUserSyncService;
use Illuminate\Console\Command;

class SyncJumuishiUsers extends Command
{
    protected $signature = 'jumuishi:sync-users {--user= : Synchronize one local user ID}';

    protected $description = 'Synchronize pending or failed module identities with Jumuishi';

    public function handle(LocalUserSyncService $service): int
    {
        if (! config('jumuishi.enabled')) {
            $this->error('Jumuishi synchronization is disabled.');

            return self::FAILURE;
        }

        $failed = 0;
        $processed = 0;
        User::query()
            ->when($this->option('user'), fn ($query, $id) => $query->whereKey($id))
            ->when(! $this->option('user'), fn ($query) => $query->whereIn('jumuishi_sync_status', ['pending', 'failed']))
            ->eachById(function (User $user) use ($service, &$failed, &$processed): void {
                $processed++;
                if (! $service->sync($user)) {
                    $failed++;
                }
            });

        $this->info("Processed {$processed} users; {$failed} failed.");

        return $failed > 0 || ($this->option('user') && $processed === 0) ? self::FAILURE : self::SUCCESS;
    }
}
