<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('indicator_data_sources');
    }

    public function down(): void
    {
        Schema::create('indicator_data_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('data_source_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('collection_status', 20)->default('not_started');
            $table->timestamps();
            $table->unique(['indicator_id', 'data_source_id']);
        });
    }
};
