<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ward', function (Blueprint $table) {
            $table->id('ward_id');
            $table->string('code', 50)->nullable()->unique();
            $table->string('name');
            // division is ward's real, always-present parent in the source boundary dataset
            // (18,357-row TAMISEMI export: division_code populated on 100% of rows, consistent
            // per ward). township is kept as an optional extra classification, not a mandatory
            // link in the chain - it's only populated for ~14% of wards in that dataset. This is
            // the one deliberate exception to the strict single-parent rule, made because the
            // real data doesn't fit a strict council->division->township->ward chain, not by
            // default design.
            $table->unsignedBigInteger('division_id');
            $table->unsignedBigInteger('township_id')->nullable();
            $table->timestamps();
            $table->foreign('division_id')->references('division_id')->on('division')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('township_id')->references('township_id')->on('township')->nullOnDelete()->cascadeOnUpdate();
            $table->index(['division_id', 'name']);
            $table->index(['township_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ward');
    }
};
