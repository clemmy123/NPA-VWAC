<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicator_data_entries', function (Blueprint $table): void {
            $table->text('actual_text')->nullable()->after('actual_value');
        });
    }

    public function down(): void
    {
        Schema::table('indicator_data_entries', function (Blueprint $table): void {
            $table->dropColumn('actual_text');
        });
    }
};
