<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_data_entries', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 80)->nullable()->unique();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->constrained()->nullOnDelete();
            $table->date('entry_date')->index();
            $table->string('activity_name')->nullable();
            $table->text('activity_description')->nullable();
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
            $table->decimal('actual_value', 20, 4);
            $table->decimal('budget_allocated', 20, 2)->nullable();
            $table->decimal('budget_used', 20, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('region_id')->references('region_id')->on('region')->nullOnDelete();
            $table->foreign('district_id')->references('district_id')->on('district')->nullOnDelete();
            $table->foreign('council_id')->references('council_id')->on('council')->nullOnDelete();
            $table->foreign('division_id')->references('division_id')->on('division')->nullOnDelete();
            $table->foreign('township_id')->references('township_id')->on('township')->nullOnDelete();
            $table->foreign('ward_id')->references('ward_id')->on('ward')->nullOnDelete();
            $table->foreign('village_mtaa_id')->references('village_mtaa_id')->on('village_mtaa')->nullOnDelete();
            $table->foreign('kitongoji_id')->references('kitongoji_id')->on('kitongoji')->nullOnDelete();
            $table->index(
                ['indicator_id', 'financial_year_id', 'status'],
                'indicator_entry_perf_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_data_entries');
    }
};
