<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicator_baselines', function (Blueprint $table) {
            $table->dropColumn('source');
            $table->foreignId('organization_id')->nullable()->after('baseline_date')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('indicator_baselines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->string('source')->nullable()->after('baseline_date');
        });
    }
};
