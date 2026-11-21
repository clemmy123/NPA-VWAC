<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thematic_area_users', function (Blueprint $table) {
            $table->boolean('can_collect')->default(false)->after('is_active');
        });

        // Managers used to collect implicitly everywhere they could see; keep that
        // access explicit so nobody loses it on deploy (it can be narrowed afterwards).
        DB::table('thematic_area_users')->update(['can_collect' => true]);

        $now = now();
        $rows = DB::table('project_users')
            ->join('thematic_areas', 'thematic_areas.project_id', '=', 'project_users.project_id')
            ->where('project_users.is_active', true)
            ->select('thematic_areas.id as thematic_area_id', 'project_users.user_id')
            ->get()
            ->map(fn ($row) => [
                'thematic_area_id' => $row->thematic_area_id,
                'user_id' => $row->user_id,
                'is_active' => true,
                'can_collect' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

        DB::table('thematic_area_users')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        Schema::table('thematic_area_users', function (Blueprint $table) {
            $table->dropColumn('can_collect');
        });
    }
};
