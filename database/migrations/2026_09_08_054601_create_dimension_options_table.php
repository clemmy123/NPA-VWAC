<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dimension_options', function (Blueprint $table) { $table->id();
        $table->foreignId('dimension_id')->constrained()->cascadeOnDelete();
        $table->string('code', 50);
        $table->string('name');
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->unique(['dimension_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dimension_options');
    }
};
