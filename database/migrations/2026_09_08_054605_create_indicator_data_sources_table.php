<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_data_sources', function (Blueprint $table) { $table->id(); $table->foreignId('indicator_id')->constrained()->cascadeOnDelete(); $table->foreignId('data_source_id')->constrained()->cascadeOnDelete(); $table->boolean('is_primary')->default(false); $table->timestamps(); $table->unique(['indicator_id','data_source_id']); });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_data_sources');
    }
};
