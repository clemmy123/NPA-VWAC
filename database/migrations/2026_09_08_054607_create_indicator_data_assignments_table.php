<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_data_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Single "most specific unit" location reference instead of one nullable FK per
            // admin level. location_level says which table location_id points into; ancestors
            // are derived by joining up the chain (region -> ... -> kitongoji), never stored
            // redundantly here. No DB-level FK is possible since the target table varies by
            // level - validate location_id exists in the level's table at the application layer.
            $table->string('location_level', 20)->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('data_source_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['location_level', 'location_id']);
            $table->index(['indicator_id', 'user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_data_assignments');
    }
};
