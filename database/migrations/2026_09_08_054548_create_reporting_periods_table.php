<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_year_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('period_type', 30)->default('quarter');
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['financial_year_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_periods');
    }
};
