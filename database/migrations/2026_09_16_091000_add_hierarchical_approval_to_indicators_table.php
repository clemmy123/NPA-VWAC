<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicators', fn (Blueprint $table) => $table->boolean('requires_hierarchical_approval')->default(false)->after('requires_evidence'));
    }

    public function down(): void
    {
        Schema::table('indicators', fn (Blueprint $table) => $table->dropColumn('requires_hierarchical_approval'));
    }
};
