<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district', function (Blueprint $table) {
            $table->id('district_id'); $table->string('code',50)->nullable()->unique(); $table->string('name');
            $table->unsignedBigInteger('region_id')->nullable();
            $table->timestamps();
            $table->foreign('region_id')->references('region_id')->on('region')->nullOnDelete()->cascadeOnUpdate(); $table->index(['region_id','name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district');
    }
};
