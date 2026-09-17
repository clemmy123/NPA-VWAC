<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_data_entry_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('indicator_data_entry_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('participants_total')->default(0);
            $table->unsignedInteger('women')->default(0);
            $table->unsignedInteger('men')->default(0);
            $table->unsignedInteger('children')->default(0);
            $table->unsignedInteger('other')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_data_entry_activities');
    }
};
