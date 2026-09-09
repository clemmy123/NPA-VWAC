<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('baseline_value', 20, 4);
            $table->date('baseline_date')->nullable();
            $table->string('source')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            // financial_year_id is nullable, and SQL unique constraints ignore NULLs (two NULLs are
            // never considered equal), so a plain unique(indicator_id, financial_year_id) would not
            // actually stop duplicate baselines when no year is set. This generated column collapses
            // "no year" to a fixed sentinel (0) so the unique constraint below actually applies.
            $table->unsignedBigInteger('financial_year_key')->storedAs('COALESCE(financial_year_id, 0)');
            $table->unique(['indicator_id', 'financial_year_key'], 'indicator_baseline_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_baselines');
    }
};
