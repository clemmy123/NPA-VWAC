<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->constrained()->restrictOnDelete();
            // Null = target applies to the indicator as a whole (aggregate/national). Non-null =
            // target set for one specific disaggregation value (e.g. a per-region target), matching
            // how the source data frequently lists a different baseline/target per disaggregation
            // level rather than one shared number.
            $table->foreignId('dimension_option_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('target_value', 20, 4);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            // reporting_period_id and dimension_option_id are both nullable, and SQL unique
            // constraints ignore NULLs, so a plain unique() on the raw columns would not stop
            // duplicate annual (no period) or aggregate (no dimension) targets. These generated
            // columns collapse "not set" to a fixed sentinel (0) so uniqueness actually applies.
            $table->unsignedBigInteger('reporting_period_key')->storedAs('COALESCE(reporting_period_id, 0)');
            $table->unsignedBigInteger('dimension_option_key')->storedAs('COALESCE(dimension_option_id, 0)');
            $table->unique(
                ['indicator_id', 'financial_year_id', 'reporting_period_key', 'dimension_option_key'],
                'indicator_target_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_targets');
    }
};
