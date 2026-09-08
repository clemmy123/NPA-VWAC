<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ward', function (Blueprint $table) { $table->id('ward_id'); $table->string('code',50)->nullable()->unique(); $table->string('name'); $table->unsignedBigInteger('district_id')->nullable(); $table->unsignedBigInteger('council_id')->nullable(); $table->timestamps(); $table->foreign('district_id')->references('district_id')->on('district')->nullOnDelete()->cascadeOnUpdate(); $table->foreign('council_id')->references('council_id')->on('council')->nullOnDelete()->cascadeOnUpdate(); $table->index(['district_id','council_id','name']); });
    }

    public function down(): void
    {
        Schema::dropIfExists('ward');
    }
};
