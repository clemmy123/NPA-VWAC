<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('township', function (Blueprint $table) {
            $table->id('township_id');
            $table->string('code', 50)->nullable()->unique();
            $table->string('name');
            $table->unsignedBigInteger('division_id')->nullable();
            $table->timestamps();
            $table->foreign('division_id')->references('division_id')->on('division')->nullOnDelete()->cascadeOnUpdate();
            $table->index(['division_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('township');
    }
};
