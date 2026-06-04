<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_correct_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamp('requested_check_in')->nullable();
            $table->timestamp('requested_check_out')->nullable();
            $table->text('reason');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->index('attendance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_correct_requests');
    }
};
