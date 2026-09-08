<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_dimensions', function (Blueprint $table) { $table->id(); $table->foreignId('indicator_id')->constrained()->cascadeOnDelete(); $table->foreignId('dimension_id')->constrained()->cascadeOnDelete(); $table->boolean('is_required')->default(false); $table->boolean('must_reconcile')->default(false); $table->timestamps(); $table->unique(['indicator_id','dimension_id']); });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_dimensions');
    }
};
