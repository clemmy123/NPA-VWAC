<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name', 500)->change();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('region', 'region_id')->nullOnDelete();
            $table->string('auth_provider', 30)->default('jumuishi');
            $table->boolean('password_login_enabled')->default(false);
            $table->timestamp('external_verified_at')->nullable();
            $table->unsignedBigInteger('global_user_id')->nullable()->unique();
            $table->string('jumuishi_sync_status', 20)->default('pending')->index();
            $table->timestamp('jumuishi_synced_at')->nullable();
            $table->text('jumuishi_sync_error')->nullable();
        });

        DB::table('users')->orderBy('id')->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                $parts = preg_split('/\\s+/', trim($user->name), -1, PREG_SPLIT_NO_EMPTY);
                DB::table('users')->where('id', $user->id)->update([
                    'first_name' => $parts[0] ?? '',
                    'middle_name' => count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : null,
                    'last_name' => count($parts) > 1 ? end($parts) : '',
                ]);
            }
        });

        Schema::create('jumuishi_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->unsignedBigInteger('global_user_id');
            $table->string('event_type', 50);
            $table->timestamp('processed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jumuishi_events');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropConstrainedForeignId('region_id');
            $table->dropUnique(['global_user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['jumuishi_sync_status']);
            $table->dropColumn([
                'first_name', 'middle_name', 'last_name', 'phone_number', 'gender',
                'status', 'auth_provider', 'password_login_enabled', 'external_verified_at',
                'global_user_id', 'jumuishi_sync_status', 'jumuishi_synced_at', 'jumuishi_sync_error',
            ]);
        });
    }
};
