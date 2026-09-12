<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicator_data_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('data_source_id');
        });
    }

    public function down(): void
    {
        Schema::table('indicator_data_assignments', function (Blueprint $table) {
            $table->foreignId('data_source_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
        });
    }
};
