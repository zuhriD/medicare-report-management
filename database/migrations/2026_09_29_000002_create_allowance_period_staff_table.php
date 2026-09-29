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
        Schema::create('allowance_period_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allowance_period_id')->constrained('allowance_periods')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('total_attendance_days')->default(0);
            $table->decimal('total_attendance_amount', 14, 2)->default(0.00);
            $table->integer('total_overtime_minutes')->default(0);
            $table->decimal('total_overtime_amount', 14, 2)->default(0.00);
            $table->decimal('total_allowance', 14, 2)->default(0.00);
            $table->string('payment_status')->default('unpaid'); // unpaid, paid
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method')->nullable(); // Transfer, Cash, Payroll, etc.
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['allowance_period_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowance_period_staff');
    }
};
