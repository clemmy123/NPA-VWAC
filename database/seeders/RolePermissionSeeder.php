<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'project.view', 'project.create', 'project.update', 'project.delete', 'project.assign-manager',
            'thematic-area.view', 'thematic-area.create', 'thematic-area.update', 'thematic-area.delete', 'thematic-area.assign-manager',
            'indicator.view', 'indicator.view-all', 'indicator.create', 'indicator.update', 'indicator.delete', 'indicator.configure',
            'indicator.set-baseline', 'indicator.set-target', 'indicator.assign-user',
            'intervention.view', 'intervention.create', 'intervention.update', 'intervention.delete',
            'indicator-data.view', 'indicator-data.create', 'indicator-data.update', 'indicator-data.submit',
            'indicator-data.review', 'indicator-data.approve', 'indicator-data.return', 'indicator-data.override-period',
            'report.view', 'report.export',
            'user.view', 'user.create', 'user.update', 'user.assign-role',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $super = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $pm = Role::firstOrCreate(['name' => 'Project Manager', 'guard_name' => 'web']);
        $tm = Role::firstOrCreate(['name' => 'Thematic Manager', 'guard_name' => 'web']);
        $de = Role::firstOrCreate(['name' => 'Data Entry User', 'guard_name' => 'web']);
        $approver = Role::firstOrCreate(['name' => 'Data Approver', 'guard_name' => 'web']);

        $super->syncPermissions(Permission::all());

        $pm->syncPermissions([
            'project.view', 'project.update', 'project.assign-manager',
            'thematic-area.view', 'thematic-area.create', 'thematic-area.update', 'thematic-area.assign-manager',
            'indicator.view', 'indicator.view-all', 'intervention.view',
            'indicator-data.view', 'report.view', 'report.export',
        ]);

        $tm->syncPermissions([
            'thematic-area.view', 'thematic-area.update',
            'indicator.view', 'indicator.view-all', 'indicator.create', 'indicator.update', 'indicator.configure',
            'indicator.set-baseline', 'indicator.set-target', 'indicator.assign-user',
            'intervention.view', 'intervention.create', 'intervention.update', 'intervention.delete',
            'indicator-data.view', 'indicator-data.review', 'indicator-data.approve', 'indicator-data.return',
            'report.view', 'report.export',
        ]);

        // Data Entry Users deliberately do NOT get 'indicator.view-all' or
        // 'indicator-data.override-period'. Collection metadata is derived by
        // the system and each user manages entries within their own scope.
        $de->syncPermissions([
            'indicator.view', 'intervention.view',
            'indicator-data.view', 'indicator-data.create', 'indicator-data.update', 'indicator-data.submit',
        ]);

        $approver->syncPermissions([
            'indicator.view', 'indicator-data.view', 'indicator-data.review',
            'indicator-data.approve', 'indicator-data.return', 'report.view', 'report.export',
        ]);
    }
}
