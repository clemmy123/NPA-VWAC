<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

/**
 * Interactive, production-safe way to create the first Super Admin: the
 * name/email/password are typed at the terminal rather than living in a
 * seeder, so no credential ever sits in source control.
 */
class MakeSuperAdmin extends Command
{
    protected $signature = 'make:super-admin';

    protected $description = 'Interactively create a Super Admin user with a local email/password login';

    public function handle(): int
    {
        if (! Role::where('name', 'Super Admin')->exists()) {
            $this->error("The 'Super Admin' role doesn't exist yet. Run `php artisan db:seed --class=RolePermissionSeeder` first.");

            return self::FAILURE;
        }

        $name = $this->ask('Name');

        $email = $this->askValidated('Email', ['required', 'email', 'unique:users,email']);

        $password = $this->secretValidated('Password (min. 8 characters)', ['required', 'string', 'min:8']);
        $confirmation = $this->secret('Confirm password');

        if ($password !== $confirmation) {
            $this->error('Passwords did not match. Nothing was created.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'status' => 'active',
            'auth_provider' => 'local',
            'password_login_enabled' => true,
        ]);

        $user->assignRole('Super Admin');

        $this->info("Super Admin \"{$user->name}\" <{$user->email}> created. They can sign in at /local-login.");

        return self::SUCCESS;
    }

    /** @param  list<string>  $rules */
    private function askValidated(string $question, array $rules): string
    {
        while (true) {
            $value = (string) $this->ask($question);
            $validator = Validator::make(['value' => $value], ['value' => $rules]);

            if ($validator->passes()) {
                return $value;
            }

            $this->error($validator->errors()->first('value'));
        }
    }

    /** @param  list<string>  $rules */
    private function secretValidated(string $question, array $rules): string
    {
        while (true) {
            $value = (string) $this->secret($question);
            $validator = Validator::make(['value' => $value], ['value' => $rules]);

            if ($validator->passes()) {
                return $value;
            }

            $this->error($validator->errors()->first('value'));
        }
    }
}
