<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thematic_area_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('measurement_type_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('unit_of_measure_id')
                ->nullable()
                ->constrained('units_of_measure')
                ->nullOnDelete();
            $table->string('collection_mode', 30)->default('progressive');
            $table->string('aggregation_method', 30)->default('sum');
            $table->string('reporting_frequency', 30)->default('quarterly');
            $table->string('collection_scope', 30)->default('national');
            $table->boolean('requires_location')->default(false);
            $table->string('reporting_location_level', 30)->nullable();
            $table->boolean('requires_activity')->default(false);
            $table->boolean('has_budget_implication')->default(false);
            $table->boolean('requires_evidence')->default(false);
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['thematic_area_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
