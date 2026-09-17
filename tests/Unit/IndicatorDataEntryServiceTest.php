<?php

namespace Tests\Unit;

use App\Models\FinancialYear;
use App\Models\Indicator;
use App\Models\IndicatorDataAssignment;
use App\Models\IndicatorDataEntry;
use App\Models\User;
use App\Services\IndicatorDataEntryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorDataEntryServiceTest extends TestCase
{
    use RefreshDatabase;

    private IndicatorDataEntryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->service = new IndicatorDataEntryService;
    }

    private function assignedUser(Indicator $indicator): User
    {
        $indicator->update(['status' => 'active']);
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');
        IndicatorDataAssignment::factory()->create([
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
            'location_level' => null,
            'location_id' => null,
        ]);

        return $user;
    }

    public function test_create_rejects_a_user_without_an_indicator_assignment(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('Data Entry User');

        $this->expectException(AuthorizationException::class);

        $this->service->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
        ], [], [], [], $user);

    }

    public function test_create_saves_a_draft_entry_for_an_assigned_user(): void
    {
        $indicator = Indicator::factory()->create();
        $financialYear = FinancialYear::factory()->create();
        $user = $this->assignedUser($indicator);

        $entry = $this->service->create([
            'indicator_id' => $indicator->id,
            'financial_year_id' => $financialYear->id,
            'entry_date' => now()->toDateString(),
            'actual_value' => 10,
        ], [], [], [], $user);

        $this->assertSame('draft', $entry->status);
        $this->assertSame($user->id, $entry->entered_by);
    }

    public function test_update_rejects_editing_a_submitted_entry(): void
    {
        $indicator = Indicator::factory()->create();
        $user = $this->assignedUser($indicator);
        $entry = IndicatorDataEntry::factory()->submitted()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $user->id,
        ]);

        $this->expectException(AuthorizationException::class);

        $this->service->update($entry, ['remarks' => 'edited'], null, null, [], $user);
    }

    public function test_update_saves_changes_to_a_draft_entry(): void
    {
        $indicator = Indicator::factory()->create();
        $user = $this->assignedUser($indicator);
        $entry = IndicatorDataEntry::factory()->create([
            'indicator_id' => $indicator->id,
            'entered_by' => $user->id,
            'status' => 'draft',
        ]);

        $updated = $this->service->update($entry, ['remarks' => 'edited'], null, null, [], $user);

        $this->assertSame('edited', $updated->remarks);
    }
}
