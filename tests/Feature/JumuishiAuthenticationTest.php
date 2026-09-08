<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\JumuishiUrl;
use App\Services\LocalUserSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JumuishiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jumuishi.enabled' => true,
            'jumuishi.url' => 'https://jumuishi.example',
            'jumuishi.module_path' => 'npa-vwac',
            'jumuishi.api_secret' => 'module-test-secret',
            'jumuishi.platform_secret' => 'platform-test-secret',
            'jumuishi.default_role' => 'Data Entry User',
        ]);
        Http::preventStrayRequests();
    }

    private function identity(User $user, int $globalId = 42): array
    {
        return ['status' => 'success', 'data' => [
            'global_user_id' => $globalId, 'email' => $user->email, 'status' => 'active',
        ]];
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'global_user_id' => 42,
            'first_name' => 'Asha',
            'second_name' => 'Juma',
            'last_name' => 'Mushi',
            'gender' => 'female',
            'email' => 'asha@example.test',
            'password_hash' => Hash::make('central-password'),
            'status' => 'active',
        ], $overrides);
    }

    private function centralHeaders(): array
    {
        return ['X-Jumuishi-Platform-Secret' => 'platform-test-secret'];
    }

    public function test_login_password_management_and_logout_use_jumuishi(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertRedirect('https://jumuishi.example/sso/start/npa-vwac');
        $this->get('/forgot-password')->assertRedirect('https://jumuishi.example/forgot-password');
        $this->get('/reset-password/token?email=asha%40example.test')
            ->assertRedirect('https://jumuishi.example/reset-password/token?email=asha%40example.test');
        $user = User::factory()->create();
        $this->actingAs($user)->get('/profile')->assertRedirect('https://jumuishi.example/profile');
        $this->post('/logout')->assertRedirect('https://jumuishi.example/central-logout');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertMethodNotAllowed();
        $this->get('/register')->assertNotFound();
    }

    public function test_sso_links_existing_user_and_regenerates_session(): void
    {
        $user = User::factory()->create();
        Http::fake(['https://jumuishi.example/*' => Http::response($this->identity($user))]);
        $this->withSession(['marker' => 'before']);
        $oldSession = session()->getId();

        $this->get('/jumuishi/sso/consume?ticket='.str_repeat('a', 64))->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertSame(42, $user->fresh()->global_user_id);
        $this->assertNotNull($user->fresh()->external_verified_at);
        Http::assertSent(fn ($request) => $request->url() === 'https://jumuishi.example/api/internal/sso/exchange'
            && $request->hasHeader('X-Jumuishi-Module', 'npa-vwac')
            && $request->hasHeader('X-Jumuishi-Secret', 'module-test-secret')
            && $request['ticket'] === str_repeat('a', 64));
        $this->get('/dashboard')->assertOk()->assertSee($user->name);
    }

    public function test_global_identity_remains_authoritative_after_central_email_changes(): void
    {
        $user = User::factory()->create(['global_user_id' => 42]);
        $identity = $this->identity($user);
        $identity['data']['email'] = 'new-address@example.test';
        Http::fake(['*' => Http::response($identity)]);

        $this->get('/jumuishi/sso/consume?ticket='.str_repeat('a', 64))->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_sso_does_not_create_users_or_relink_another_identity(): void
    {
        $user = User::factory()->create(['global_user_id' => 7]);
        Http::fake(['*' => Http::response($this->identity($user))]);
        $this->get('/jumuishi/sso/consume?ticket='.str_repeat('a', 64))->assertForbidden();
        $this->assertSame(7, $user->fresh()->global_user_id);
        $this->assertGuest();

        $identity = $this->identity($user);
        $identity['data']['email'] = 'missing@example.test';
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response($identity)]);
        $this->get('/jumuishi/sso/consume?ticket='.str_repeat('b', 64))->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_inactive_local_and_central_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->create(['status' => 'deactivated']);
        Http::fake(['*' => Http::response($this->identity($user))]);
        $this->get('/jumuishi/sso/consume?ticket='.str_repeat('a', 64))->assertForbidden();
        $user->update(['status' => 'active']);
        $identity = $this->identity($user);
        $identity['data']['status'] = 'disabled';
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response($identity)]);
        $this->get('/jumuishi/sso/consume?ticket='.str_repeat('b', 64))->assertForbidden();
        $this->assertGuest();
    }

    public function test_sso_failures_render_terminal_pages_without_redirect_loops(): void
    {
        $this->get('/jumuishi/sso/consume')->assertUnprocessable()->assertSee('ticket is invalid');
        Http::assertNothingSent();
        Http::fake(['*' => Http::response(['status' => 'error'], 401)]);
        $this->get('/jumuishi/sso/consume?ticket='.str_repeat('a', 64))->assertUnauthorized()
            ->assertHeader('Cache-Control', 'no-store, private')->assertSee('already used');
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['status' => 'success', 'data' => []])]);
        $this->get('/jumuishi/sso/consume?ticket='.str_repeat('b', 64))->assertForbidden();
    }

    public function test_disabled_integration_has_no_local_password_fallback(): void
    {
        config(['jumuishi.enabled' => false]);
        $this->get('/login')->assertServiceUnavailable();
        $this->get('/jumuishi/sso/consume?ticket='.str_repeat('a', 64))->assertServiceUnavailable();
        $this->postJson('/api/jumuishi/users/provision', $this->payload(), $this->centralHeaders())->assertUnauthorized();
        Http::assertNothingSent();
    }

    #[DataProvider('unsafeReturnPaths')]
    public function test_unsafe_return_paths_fall_back_to_dashboard(string $path): void
    {
        $this->assertSame('/dashboard', JumuishiUrl::safeReturnTo($path));
    }

    public static function unsafeReturnPaths(): array
    {
        return [
            ['https://attacker.example'], ['//attacker.example'], ['/\\attacker.example'],
            ['/%5cattacker.example'], ['/%2fattacker.example'], ["/\n/attacker.example"],
            ['/login'], ['/logout?next=1'], ['/jumuishi/sso/consume?ticket=bad'],
        ];
    }

    public function test_safe_return_path_is_used_after_authentication(): void
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::response($this->identity($user))]);
        $this->get('/jumuishi/sso/consume?'.http_build_query([
            'ticket' => str_repeat('a', 64), 'return_to' => '/dashboard?tab=indicators',
        ]))->assertRedirect('/dashboard?tab=indicators');
    }

    public function test_provisioning_requires_a_configured_matching_platform_secret(): void
    {
        $this->postJson('/api/jumuishi/users/provision', $this->payload())->assertUnauthorized();
        $this->postJson('/api/jumuishi/users/provision', $this->payload(), [
            'X-Jumuishi-Platform-Secret' => 'wrong',
        ])->assertUnauthorized();
        config(['jumuishi.platform_secret' => '']);
        $this->postJson('/api/jumuishi/users/provision', $this->payload())->assertUnauthorized();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_provisioning_assigns_local_role_preserves_hash_and_is_idempotent(): void
    {
        Role::findOrCreate('Data Entry User', 'web');
        $payload = $this->payload([
            'email' => 'ASHA@example.test', 'event_uuid' => (string) Str::uuid(), 'event_type' => 'user.created',
        ]);
        $this->postJson('/api/jumuishi/users/provision', $payload, $this->centralHeaders())
            ->assertOk()->assertJsonPath('data.created', true);

        $user = User::query()->sole();
        $this->assertSame('Asha Juma Mushi', $user->name);
        $this->assertSame('asha@example.test', $user->email);
        $this->assertSame($payload['password_hash'], $user->getRawOriginal('password'));
        $this->assertFalse($user->password_login_enabled);
        $this->assertTrue($user->hasRole('Data Entry User'));

        $this->postJson('/api/jumuishi/users/provision', $payload, $this->centralHeaders())
            ->assertOk()->assertJsonPath('data.created', false)->assertJsonPath('data.local_user_id', $user->id);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('jumuishi_events', 1);
    }

    public function test_provisioning_links_existing_accounts_without_overwriting_local_roles(): void
    {
        $user = User::factory()->create(['email' => 'asha@example.test']);
        $user->assignRole(Role::findOrCreate('Project Manager', 'web'));
        $this->postJson('/api/jumuishi/users/provision', $this->payload(), $this->centralHeaders())
            ->assertOk()->assertJsonPath('data.created', false)->assertJsonPath('data.local_user_id', $user->id);
        $this->assertTrue($user->fresh()->hasRole('Project Manager'));
        $this->assertSame(42, $user->fresh()->global_user_id);
    }

    public function test_identity_conflicts_and_invalid_password_hashes_are_rejected(): void
    {
        $user = User::factory()->create(['global_user_id' => 7, 'email' => 'asha@example.test']);
        $this->postJson('/api/jumuishi/users/provision', $this->payload(), $this->centralHeaders())
            ->assertUnprocessable()->assertJsonValidationErrors('global_user_id');
        $this->assertSame(7, $user->fresh()->global_user_id);
        $this->postJson('/api/jumuishi/users/provision', $this->payload(['password_hash' => 'plaintext']), $this->centralHeaders())
            ->assertUnprocessable()->assertJsonValidationErrors('password_hash');
    }

    public function test_sync_updates_profile_once_and_does_not_echo_back_to_jumuishi(): void
    {
        $user = User::factory()->create(['global_user_id' => 42, 'jumuishi_sync_status' => 'synced']);
        $payload = $this->payload(['event_uuid' => (string) Str::uuid(), 'event_type' => 'user.updated']);
        unset($payload['password_hash']);
        $this->postJson('/api/jumuishi/users/sync', $payload, $this->centralHeaders())->assertOk();
        $this->assertSame('Asha Juma Mushi', $user->fresh()->name);
        $this->assertSame('synced', $user->fresh()->jumuishi_sync_status);

        $payload['first_name'] = 'Duplicate must not change this';
        $this->postJson('/api/jumuishi/users/sync', $payload, $this->centralHeaders())
            ->assertOk()->assertJsonPath('data.duplicate', true);
        $this->assertSame('Asha', $user->fresh()->first_name);
        $this->assertDatabaseCount('jumuishi_events', 1);
        Http::assertNothingSent();
    }

    public function test_password_sync_replaces_hash_and_invalidates_database_sessions(): void
    {
        $user = User::factory()->create(['global_user_id' => 42, 'remember_token' => 'old-token']);
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            'id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time(),
        ]);
        $hash = Hash::make('new-central-password');
        $this->postJson('/api/jumuishi/users/sync', [
            'global_user_id' => 42, 'email' => $user->email,
            'event_uuid' => (string) Str::uuid(), 'event_type' => 'password.changed',
            'password_hash' => $hash,
        ], $this->centralHeaders())->assertOk();
        $this->assertSame($hash, $user->fresh()->getRawOriginal('password'));
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
    }

    public function test_status_sync_blocks_existing_sessions_and_can_reenable_users(): void
    {
        $user = User::factory()->create(['global_user_id' => 42]);
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->postJson('/api/jumuishi/users/sync', [
            'global_user_id' => 42, 'email' => $user->email,
            'event_uuid' => (string) Str::uuid(), 'event_type' => 'user.disabled',
        ], $this->centralHeaders())->assertOk();
        $this->actingAs($user->fresh())->get('/dashboard')->assertForbidden();
        $this->assertGuest();
        $this->postJson('/api/jumuishi/users/sync', [
            'global_user_id' => 42, 'email' => $user->email,
            'event_uuid' => (string) Str::uuid(), 'event_type' => 'user.enabled',
        ], $this->centralHeaders())->assertOk();
        $this->assertTrue($user->fresh()->isActive());
    }

    public function test_password_sync_invalidates_sessions_with_other_session_drivers(): void
    {
        $user = User::factory()->create(['global_user_id' => 42]);
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->postJson('/api/jumuishi/users/sync', [
            'global_user_id' => 42, 'email' => $user->email,
            'event_uuid' => (string) Str::uuid(), 'event_type' => 'password.changed',
            'password_hash' => Hash::make('replacement-password'),
        ], $this->centralHeaders())->assertOk();
        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_event_uuid_cannot_be_reused_for_a_different_user(): void
    {
        $user = User::factory()->create(['global_user_id' => 42]);
        $other = User::factory()->create(['global_user_id' => 43]);
        $event = [
            'global_user_id' => 42, 'email' => $user->email,
            'event_uuid' => (string) Str::uuid(), 'event_type' => 'user.disabled',
        ];
        $this->postJson('/api/jumuishi/users/sync', $event, $this->centralHeaders())->assertOk();
        $this->postJson('/api/jumuishi/users/sync', array_replace($event, [
            'global_user_id' => 43, 'email' => $other->email,
        ]), $this->centralHeaders())->assertConflict();
        $this->assertTrue($other->fresh()->isActive());
    }

    public function test_outbound_sync_cannot_replace_an_existing_global_identity(): void
    {
        $user = User::factory()->create(['global_user_id' => 7]);
        Http::fake(['*' => Http::response(['status' => 'success', 'data' => ['global_user_id' => 42]])]);
        $this->assertFalse(app(LocalUserSyncService::class)->sync($user));
        $this->assertSame(7, $user->fresh()->global_user_id);
        $this->assertSame('failed', $user->fresh()->jumuishi_sync_status);
    }

    public function test_sync_rejects_unknown_events_missing_accounts_and_conflicting_email(): void
    {
        $user = User::factory()->create(['global_user_id' => 42]);
        $other = User::factory()->create();
        $payload = $this->payload(['event_uuid' => (string) Str::uuid(), 'event_type' => 'user.updated', 'email' => $other->email]);
        $this->postJson('/api/jumuishi/users/sync', $payload, $this->centralHeaders())
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/jumuishi/users/sync', array_replace($payload, ['event_type' => 'unknown']), $this->centralHeaders())
            ->assertUnprocessable()->assertJsonValidationErrors('event_type');
        $this->postJson('/api/jumuishi/users/sync', array_replace($payload, ['global_user_id' => 999]), $this->centralHeaders())
            ->assertNotFound();
        $this->assertDatabaseCount('jumuishi_events', 0);
    }

    public function test_local_user_sync_uses_central_contract_and_preserves_local_roles(): void
    {
        $user = User::factory()->create(['name' => 'Asha Juma Mushi', 'gender' => 'Female']);
        Http::fake(['*' => Http::response(['status' => 'success', 'data' => ['global_user_id' => 42]], 201)]);
        $this->assertTrue(app(LocalUserSyncService::class)->sync($user));
        $this->assertSame(42, $user->fresh()->global_user_id);
        $this->assertSame('synced', $user->fresh()->jumuishi_sync_status);
        Http::assertSent(fn ($request) => $request['local_user_id'] === (string) $user->id
            && $request['second_name'] === 'Juma'
            && $request['gender'] === 'female'
            && $request['password_hash'] === $user->getRawOriginal('password')
            && $request['sync_password'] === false);

        $user->update(['first_name' => 'Neema']);
        $this->assertSame('pending', $user->fresh()->jumuishi_sync_status);
        $this->assertSame('Neema Juma Mushi', $user->fresh()->name);
    }

    public function test_failed_outbound_sync_is_retryable_and_never_stores_response_secrets(): void
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::response(['message' => 'sensitive-remote-response'], 500)]);
        $this->artisan('jumuishi:sync-users', ['--user' => $user->id])->assertFailed();
        $this->assertSame('failed', $user->fresh()->jumuishi_sync_status);
        $this->assertStringNotContainsString('sensitive-remote-response', $user->fresh()->jumuishi_sync_error);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['status' => 'success', 'data' => ['global_user_id' => 42]])]);
        $this->artisan('jumuishi:sync-users')->assertSuccessful();
        $this->assertSame('synced', $user->fresh()->jumuishi_sync_status);
    }

    public function test_migration_backfills_existing_names_without_changing_user_ids(): void
    {
        $migration = require database_path('migrations/2026_09_08_104631_add_jumuishi_identity_fields_to_users_table.php');
        $migration->down();
        DB::table('users')->insert([
            'id' => 123, 'name' => 'Asha Juma Mushi', 'email' => 'legacy@example.test',
            'password' => Hash::make('legacy-password'),
        ]);
        $migration->up();
        $user = User::findOrFail(123);
        $this->assertSame('Asha', $user->first_name);
        $this->assertSame('Juma', $user->middle_name);
        $this->assertSame('Mushi', $user->last_name);
        $this->assertSame('pending', $user->jumuishi_sync_status);
        $this->assertFalse($user->password_login_enabled);
        $this->assertTrue(Hash::check('legacy-password', $user->password));
    }
}
