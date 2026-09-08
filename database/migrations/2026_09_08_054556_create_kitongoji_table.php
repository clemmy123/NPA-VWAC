<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitongoji', function (Blueprint $table) { $table->id('kitongoji_id');
        $table->string('code', 50)->nullable()->unique();
        $table->string('name');
        $table->unsignedBigInteger('village_mtaa_id')->nullable();
        $table->timestamps();
        $table->foreign('village_mtaa_id')->references('village_mtaa_id')->on('village_mtaa')->nullOnDelete()->cascadeOnUpdate();
        $table->index(['village_mtaa_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitongoji');
    }
};
