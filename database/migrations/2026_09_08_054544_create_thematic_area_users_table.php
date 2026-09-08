<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thematic_area_users', function (Blueprint $table) {
            $table->id(); $table->foreignId('thematic_area_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role',50)->default('thematic_manager'); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->unique(['thematic_area_id','user_id','role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thematic_area_users');
    }
};
