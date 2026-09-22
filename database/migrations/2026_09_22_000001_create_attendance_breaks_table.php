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
        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason');
            $table->dateTime('paused_at');
            $table->decimal('paused_latitude', 10, 7)->nullable();
            $table->decimal('paused_longitude', 10, 7)->nullable();
            $table->decimal('paused_accuracy', 8, 2)->nullable();
            $table->string('paused_selfie')->nullable();

            $table->dateTime('resumed_at')->nullable();
            $table->decimal('resumed_latitude', 10, 7)->nullable();
            $table->decimal('resumed_longitude', 10, 7)->nullable();
            $table->decimal('resumed_accuracy', 8, 2)->nullable();
            $table->string('resumed_selfie')->nullable();

            $table->integer('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['attendance_id', 'resumed_at']);
            $table->index(['user_id', 'paused_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');
    }
};
