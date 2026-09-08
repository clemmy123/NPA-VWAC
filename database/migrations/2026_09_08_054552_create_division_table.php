<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('division', function (Blueprint $table) {
            $table->id('division_id');
            $table->string('code', 50)->nullable()->unique();
            $table->string('name');
            $table->unsignedBigInteger('council_id')->nullable();
            $table->timestamps();
            $table->foreign('council_id')->references('council_id')->on('council')->nullOnDelete()->cascadeOnUpdate();
            $table->index(['council_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('division');
    }
};
