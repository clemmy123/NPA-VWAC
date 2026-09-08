<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_mtaa', function (Blueprint $table) { $table->id('village_mtaa_id');
        $table->string('code', 50)->nullable()->unique();
        $table->string('name');
        $table->string('type', 30)->nullable();
        $table->unsignedBigInteger('ward_id')->nullable();
        $table->timestamps();
        $table->foreign('ward_id')->references('ward_id')->on('ward')->nullOnDelete()->cascadeOnUpdate();
        $table->index(['ward_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_mtaa');
    }
};
