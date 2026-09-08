<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_data_entry_row_dimensions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('indicator_data_entry_row_id');
            $table->unsignedBigInteger('dimension_option_id');

            $table->timestamps();

            $table->foreign(
                'indicator_data_entry_row_id',
                'entry_row_dim_entry_fk'
            )
                ->references('id')
                ->on('indicator_data_entry_rows')
                ->cascadeOnDelete();

            $table->foreign(
                'dimension_option_id',
                'entry_row_dim_option_fk'
            )
                ->references('id')
                ->on('dimension_options')
                ->cascadeOnDelete();

            $table->unique(
                [
                    'indicator_data_entry_row_id',
                    'dimension_option_id',
                ],
                'entry_row_dimension_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_data_entry_row_dimensions');
    }
};