<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\MeasurementType;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Project;
use App\Models\ThematicArea;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Local-only: one clearly-named "Test ..." user per role, so `/dev-login`
 * can offer a role picker without ever touching a real deployment. Only
 * called from DatabaseSeeder when app()->environment('local') — see there.
 *
 * The Data Entry test user is also given a sample assignment (own indicator
 * + organization) so logging in as them demonstrates the assignment-scoped
 * visibility instead of landing on an empty screen.
 */
class DevUserSeeder extends Seeder
{
    /** @var array<string, string> role name => email, used by both this seeder and the /dev-login picker */
    public const array TEST_USERS = [
        'Super Admin' => 'test.super-admin@npa-vwac.test',
        'Project Manager' => 'test.project-manager@npa-vwac.test',
        'Thematic Manager' => 'test.thematic-manager@npa-vwac.test',
        'Data Entry User' => 'test.data-entry-user@npa-vwac.test',
    ];

    public function run(): void
    {
        foreach (self::TEST_USERS as $role => $email) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                ['name' => "Test {$role}", 'status' => 'active', 'password' => Str::random(32)]
            );

            $user->syncRoles([$role]);
        }

        $this->assignSampleIndicatorToDataEntryTester();
    }

    private function assignSampleIndicatorToDataEntryTester(): void
    {
        $entrant = User::query()->where('email', self::TEST_USERS['Data Entry User'])->first();

        if (! $entrant) {
            return;
        }

        $organizationType = OrganizationType::query()->where('code', 'bank')->first()
            ?? OrganizationType::factory()->create();

        $organization = Organization::query()->firstOrCreate(
            ['code' => 'TEST-BANK'],
            [
                'organization_type_id' => $organizationType->id,
                'name' => 'Test Bank (dev only)',
                'description' => 'Seeded for local dev-login testing.',
                'is_active' => true,
            ],
        );

        if ($entrant->organization_id !== $organization->id) {
            $entrant->update(['organization_id' => $organization->id]);
        }

        $project = Project::query()->firstOrCreate(
            ['code' => 'TEST-PRJ'],
            [
                'name' => 'Test Project (dev only)',
                'description' => 'Seeded for local dev-login testing.',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYears(5)->toDateString(),
                'status' => 'active',
            ],
        );

        $thematicArea = ThematicArea::query()->firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Test Thematic Area (dev only)'],
            ['description' => 'Seeded for local dev-login testing.', 'status' => 'active'],
        );

        $indicator = Indicator::query()->firstOrCreate(
            ['code' => 'TEST-IND'],
            [
                'thematic_area_id' => $thematicArea->id,
                'name' => 'Test Indicator (dev only)',
                'description' => 'Seeded for local dev-login testing.',
                'measurement_type_id' => MeasurementType::query()->value('id') ?? MeasurementType::factory()->create()->id,
                'unit_of_measure_id' => UnitOfMeasure::query()->value('id') ?? UnitOfMeasure::factory()->create()->id,
                'collection_mode' => 'progressive',
                'aggregation_method' => 'sum',
                'reporting_frequency' => 'quarterly',
                'collection_scope' => 'national',
                'requires_location' => false,
                'requires_activity' => false,
                'has_budget_implication' => false,
                'requires_evidence' => false,
                'status' => 'active',
            ],
        );

        IndicatorDataAssignment::query()->firstOrCreate([
            'indicator_id' => $indicator->id,
            'user_id' => $entrant->id,
        ], [
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);
    }
}
