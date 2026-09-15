<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([ConstantDataSeeder::class, RolePermissionSeeder::class, TamisemiLocationSeeder::class]);

        // Test "Test <Role>" accounts + sample assignment for the /dev-login
        // picker. Never runs outside local — production admins are created
        // via `php artisan make:super-admin` instead (see that command).
        if (app()->environment('local')) {
            $this->call(DevUserSeeder::class);
        }
    }
}
