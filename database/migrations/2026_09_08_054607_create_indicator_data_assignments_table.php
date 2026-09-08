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
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('council_id')->nullable();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->unsignedBigInteger('township_id')->nullable();
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->unsignedBigInteger('village_mtaa_id')->nullable();
            $table->unsignedBigInteger('kitongoji_id')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('data_source_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('region_id')->references('region_id')->on('region')->nullOnDelete();
            $table->foreign('district_id')->references('district_id')->on('district')->nullOnDelete();
            $table->foreign('council_id')->references('council_id')->on('council')->nullOnDelete();
            $table->foreign('division_id')->references('division_id')->on('division')->nullOnDelete();
            $table->foreign('township_id')->references('township_id')->on('township')->nullOnDelete();
            $table->foreign('ward_id')->references('ward_id')->on('ward')->nullOnDelete();
            $table->foreign('village_mtaa_id')->references('village_mtaa_id')->on('village_mtaa')->nullOnDelete();
            $table->foreign('kitongoji_id')->references('kitongoji_id')->on('kitongoji')->nullOnDelete();
            $table->index(['indicator_id', 'user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_data_assignments');
    }
};
