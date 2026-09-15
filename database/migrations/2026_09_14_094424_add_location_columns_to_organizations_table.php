<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('location_level', 20)->nullable()->after('description');
            $table->unsignedBigInteger('location_id')->nullable()->after('location_level');
            $table->index(['location_level', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['location_level', 'location_id']);
            $table->dropColumn(['location_level', 'location_id']);
        });
    }
};
