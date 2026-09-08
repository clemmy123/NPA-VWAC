<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thematic_areas', function (Blueprint $table) {
            $table->id(); $table->foreignId('project_id')->constrained()->cascadeOnDelete(); $table->string('name');
            $table->text('description')->nullable(); $table->string('status',30)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thematic_areas');
    }
};
