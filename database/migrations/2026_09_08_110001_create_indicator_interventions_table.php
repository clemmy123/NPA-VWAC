<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('intervention_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['indicator_id', 'intervention_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_interventions');
    }
};
