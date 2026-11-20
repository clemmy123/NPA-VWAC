<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('thematic_areas', function (Blueprint $table): void {
            $table->string('official_code', 12)->nullable();
            $table->string('name_en')->nullable();
            $table->string('name_sw')->nullable();
        });
        Schema::create('indicator_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained()->cascadeOnDelete();
            $table->text('planned_activity');
            $table->unsignedSmallInteger('baseline_year')->nullable();
            $table->decimal('baseline_value', 20, 4)->nullable();
            $table->decimal('annual_target', 20, 4)->nullable();
            $table->decimal('domestic_budget', 20, 2)->default(0);
            $table->decimal('external_budget', 20, 2)->default(0);
            $table->foreignId('lead_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->json('participating_organization_ids')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamps();
            $table->unique(['indicator_id', 'financial_year_id']);
            $table->index(['status', 'financial_year_id']);
        });
        Schema::table('indicator_data_entries', function (Blueprint $table): void {
            $table->foreignId('indicator_plan_id')->nullable()->constrained('indicator_plans')->nullOnDelete();
            $table->json('plan_snapshot')->nullable();
            $table->foreignId('institution_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('institution_confirmed_at')->nullable();
            $table->decimal('domestic_released', 20, 2)->nullable();
            $table->decimal('external_released', 20, 2)->nullable();
            $table->decimal('domestic_spent', 20, 2)->nullable();
            $table->decimal('external_spent', 20, 2)->nullable();
            $table->text('implementation_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('indicator_data_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('indicator_plan_id');
            $table->dropConstrainedForeignId('institution_confirmed_by');
            $table->dropColumn(['plan_snapshot','institution_confirmed_at','domestic_released','external_released','domestic_spent','external_spent','implementation_status']);
        });
        Schema::dropIfExists('indicator_plans');
        Schema::table('thematic_areas', fn (Blueprint $table) => $table->dropColumn(['official_code','name_en','name_sw']));
    }
};
