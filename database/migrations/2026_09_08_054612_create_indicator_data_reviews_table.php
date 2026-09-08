<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_data_reviews', function (Blueprint $table) { $table->id(); $table->foreignId('indicator_data_entry_id')->constrained()->cascadeOnDelete(); $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete(); $table->string('action',30); $table->text('comment')->nullable(); $table->timestamps(); $table->index(['indicator_data_entry_id','action']); });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_data_reviews');
    }
};
