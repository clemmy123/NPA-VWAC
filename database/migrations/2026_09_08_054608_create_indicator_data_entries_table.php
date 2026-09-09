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
            $table->foreignId('reporting_period_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('entry_date')->index();
            $table->string('activity_name')->nullable();
            $table->text('activity_description')->nullable();
            // Single "most specific unit" location reference - see indicator_data_assignments
            // migration for the reasoning (avoids duplicating all 8 admin levels per table, and
            // avoids the inconsistency risk of storing a level's value that doesn't match its
            // supposed ancestors).
            $table->string('location_level', 20)->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('data_source_id')->nullable()->constrained()->nullOnDelete();
            // Nullable: a draft entry (see status below) may not have a final value yet.
            $table->decimal('actual_value', 20, 4)->nullable();
            $table->decimal('budget_allocated', 20, 2)->nullable();
            $table->decimal('budget_used', 20, 2)->nullable();
            $table->char('currency', 3)->default('TZS');
            $table->text('remarks')->nullable();
            // How this entry's value arrived: a human typed it in, or it was synced from an
            // external system via API integration.
            $table->string('source_type', 20)->default('manual');
            $table->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['location_level', 'location_id']);
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
