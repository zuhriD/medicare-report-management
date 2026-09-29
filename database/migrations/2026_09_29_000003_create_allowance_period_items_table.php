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
        Schema::create('allowance_period_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allowance_period_staff_id')->constrained('allowance_period_staff')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->foreignId('overtime_id')->nullable()->constrained('overtimes')->nullOnDelete();
            $table->string('item_type'); // 'attendance', 'overtime'
            $table->date('item_date');
            $table->decimal('amount', 14, 2)->default(0.00);
            $table->integer('duration_minutes')->default(0);
            $table->timestamps();

            $table->index(['item_type', 'item_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowance_period_items');
    }
};
