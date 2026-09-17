<?php

namespace Tests\Feature;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class IndicatorDataAssignmentsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_assignment_workflow_is_available_to_authorized_managers(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue(Route::has('indicator-data-assignments.index'));
        $this->assertTrue(Route::has('indicator-data-assignments.store'));
        $this->assertTrue(Permission::where('name', 'indicator.assign-user')->exists());
    }
}
