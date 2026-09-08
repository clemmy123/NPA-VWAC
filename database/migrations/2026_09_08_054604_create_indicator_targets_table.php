<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_targets', function (Blueprint $table) { $table->id(); $table->foreignId('indicator_id')->constrained()->cascadeOnDelete(); $table->foreignId('financial_year_id')->constrained()->cascadeOnDelete(); $table->foreignId('reporting_period_id')->nullable()->constrained()->nullOnDelete(); $table->decimal('target_value',20,4); $table->text('remarks')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->unique(['indicator_id','financial_year_id','reporting_period_id'],'indicator_target_period_unique'); });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_targets');
    }
};
