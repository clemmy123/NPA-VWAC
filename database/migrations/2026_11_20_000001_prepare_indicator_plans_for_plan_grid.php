<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The plan-builder grid autosaves half-filled rows as drafts, so the plan
     * columns that used to be mandatory at insert time become nullable here;
     * completeness is enforced when the plan is submitted instead. Indicator
     * config flags now default to on (except requires_location), which only
     * affects newly created indicators — existing rows keep their values.
     */
    public function up(): void
    {
        Schema::table('indicator_plans', function (Blueprint $table): void {
            $table->text('planned_activity')->nullable()->change();
            $table->foreignId('lead_organization_id')->nullable()->change();
            $table->decimal('domestic_budget', 20, 2)->nullable()->default(null)->change();
            $table->decimal('external_budget', 20, 2)->nullable()->default(null)->change();
            $table->foreignId('baseline_financial_year_id')->nullable()->after('baseline_year')
                ->constrained('financial_years')->nullOnDelete();
        });

        Schema::table('indicators', function (Blueprint $table): void {
            $table->boolean('requires_activity')->default(true)->change();
            $table->boolean('has_budget_implication')->default(true)->change();
            $table->boolean('requires_evidence')->default(true)->change();
            $table->boolean('requires_hierarchical_approval')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('indicators', function (Blueprint $table): void {
            $table->boolean('requires_activity')->default(false)->change();
            $table->boolean('has_budget_implication')->default(false)->change();
            $table->boolean('requires_evidence')->default(false)->change();
            $table->boolean('requires_hierarchical_approval')->default(false)->change();
        });

        Schema::table('indicator_plans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('baseline_financial_year_id');
            $table->decimal('domestic_budget', 20, 2)->default(0)->nullable(false)->change();
            $table->decimal('external_budget', 20, 2)->default(0)->nullable(false)->change();
            $table->foreignId('lead_organization_id')->nullable(false)->change();
            $table->text('planned_activity')->nullable(false)->change();
        });
    }
};
