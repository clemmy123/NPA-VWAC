<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_baselines', function (Blueprint $table) { $table->id(); $table->foreignId('indicator_id')->constrained()->cascadeOnDelete(); $table->foreignId('financial_year_id')->nullable()->constrained()->nullOnDelete(); $table->decimal('baseline_value',20,4); $table->date('baseline_date')->nullable(); $table->string('source')->nullable(); $table->text('remarks')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_baselines');
    }
};
