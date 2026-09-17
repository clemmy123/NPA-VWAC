<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_approval_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('location_level', 30);
            $table->unsignedBigInteger('location_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'location_level', 'location_id'], 'approval_assignment_unique');
            $table->index(['location_level', 'location_id']);
        });

        Schema::create('indicator_data_entry_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('indicator_data_entry_id')->constrained()->cascadeOnDelete();
            $table->string('location_level', 30);
            $table->unsignedBigInteger('location_id');
            $table->unsignedTinyInteger('sequence');
            $table->string('status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['indicator_data_entry_id', 'sequence'], 'entry_approval_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_data_entry_approvals');
        Schema::dropIfExists('indicator_approval_assignments');
    }
};
