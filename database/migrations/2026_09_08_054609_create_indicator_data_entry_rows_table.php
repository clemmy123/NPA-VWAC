<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_data_entry_rows', function (Blueprint $table) { $table->id();
        $table->foreignId('indicator_data_entry_id')->constrained()->cascadeOnDelete();
        $table->string('label')->nullable();
        $table->decimal('value', 20, 4);
        $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_data_entry_rows');
    }
};
