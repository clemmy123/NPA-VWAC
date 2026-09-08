<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_data_entry_expenses', function (Blueprint $table) { $table->id();
        $table->foreignId('indicator_data_entry_id')->constrained()->cascadeOnDelete();
        $table->string('expense_category')->nullable();
        $table->string('description');
        $table->decimal('amount', 20, 2);
        $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_data_entry_expenses');
    }
};
